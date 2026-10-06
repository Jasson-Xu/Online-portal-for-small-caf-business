<?php
declare(strict_types=1);
require __DIR__ . '/staff_actions.php';

function handle_action(): void {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf(), $token)) { http_response_code(419); throw new DomainException('Your form expired. Refresh the page and try again.'); }
    $action = text_input('action', 40, true);
    if ($action === 'register') {
        $name = text_input('name', 80, true);
        $email = strtolower(text_input('email', 190, true));
        $password = $_POST['password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        if (!is_string($password) || strlen($password) < 10 || strlen($password) > 72) throw new DomainException('Use a password of 10 to 72 bytes.');
        if (text_input('privacy', 3) !== 'yes') throw new DomainException('Please acknowledge the privacy notice.');
        try { query('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)', [$name, $email, password_hash($password, PASSWORD_DEFAULT)]); }
        catch (PDOException $ex) { if ($ex->getCode() === '23000') throw new DomainException('Unable to create this account. Try signing in or use another email.'); throw $ex; }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect('menu');
    }
    if ($action === 'login') {
        $email = strtolower(text_input('email', 190, true));
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) > 72) throw new DomainException('Email or password is incorrect.');
        $identity = hash('sha256', $email);
        // Atomic, database-backed limit survives session/cookie changes.
        query('INSERT INTO login_attempts (identity_hash,attempts,window_started) VALUES (?,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE attempts=IF(window_started < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,1,attempts+1), window_started=IF(window_started < UTC_TIMESTAMP() - INTERVAL 15 MINUTE,UTC_TIMESTAMP(),window_started)', [$identity]);
        $attempt = query('SELECT attempts FROM login_attempts WHERE identity_hash=?', [$identity])->fetch();
        if ((int)$attempt['attempts'] > 10) { http_response_code(429); throw new DomainException('Too many sign-in attempts. Please try again in 15 minutes.'); }
        $user = query('SELECT * FROM users WHERE email=?', [$email])->fetch();
        $fallback = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $user['password_hash'] ?? $fallback);
        if (!$user || !$valid) throw new DomainException('Email or password is incorrect.');
        query('DELETE FROM login_attempts WHERE identity_hash=?', [$identity]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect($user['role'] === 'staff' ? 'staff' : (cart_count() ? 'cart' : 'menu'));
    }
    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('home');
    }
    if ($action === 'add_cart') {
        $id = text_input('item_id', 20, true);
        $item = query('SELECT id FROM menu_items WHERE id=? AND available=1', [$id])->fetch();
        if (!$item) throw new DomainException('This item is unavailable. Please choose another item.');
        $quantity = valid_quantity($_POST['quantity'] ?? null);
        if (count(cart()) >= 30 || cart_count() + $quantity > 80) throw new DomainException('An order can contain up to 30 lines and 80 items. Please contact the café for larger orders.');
        $_SESSION['cart'][bin2hex(random_bytes(8))] = ['item_id' => (int)$item['id'], 'quantity' => $quantity, 'recipient' => text_input('recipient', 60), 'instructions' => text_input('instructions', 160)];
        unset($_SESSION['checkout_key']);
        $_SESSION['flash'] = 'Added to your bag. Keep browsing or review your order.';
        redirect('menu');
    }
    if ($action === 'update_cart' || $action === 'remove_cart') {
        $key = text_input('key', 32, true);
        if (!isset($_SESSION['cart'][$key])) throw new DomainException('This item is no longer in your bag.');
        if ($action === 'remove_cart') unset($_SESSION['cart'][$key]);
        else {
            $quantity = valid_quantity($_POST['quantity'] ?? null);
            if (cart_count() - $_SESSION['cart'][$key]['quantity'] + $quantity > 80) throw new DomainException('An order can contain up to 80 items.');
            $_SESSION['cart'][$key]['quantity'] = $quantity;
        }
        unset($_SESSION['checkout_key']);
        redirect('cart');
    }
    if ($action === 'checkout') {
        $user = require_user();
        $key = text_input('checkout_key', 64, true);
        // A completed request can safely be replayed without a second order or charge.
        $existing = query('SELECT reference FROM orders WHERE checkout_key=? AND user_id=?', [$key, $user['id']])->fetch();
        if ($existing) redirect('confirmation', ['ref' => $existing['reference']]);
        if (!hash_equals(checkout_key(), $key)) throw new DomainException('Your bag changed. Review checkout again.');
        if (!cart()) throw new DomainException('Your bag is empty. Add something from the menu first.');
        $name = text_input('customer_name', 80, true);
        $phone = text_input('phone', 24, true);
        if (!valid_phone($phone)) throw new DomainException('Enter a valid phone number using 8 to 15 digits.');
        $pickup = text_input('pickup_at', 19, true);
        if (!isset(pickup_options()[$pickup])) throw new DomainException('Choose an available collection time at least 15 minutes from now.');
        $group = text_input('group_name', 80);
        $notes = text_input('notes', 500);
        if (text_input('payment_consent', 3) !== 'yes') throw new DomainException('Confirm that this is a simulated payment.');
        $outcome = text_input('payment_result', 12, true);
        if ($outcome === 'declined') throw new DomainException('The demonstration payment was declined. Your bag is saved; choose an approved payment to try again.');
        if ($outcome !== 'approved') throw new DomainException('Select a valid demonstration payment result.');
        $pdo = db();
        $pdo->beginTransaction();
        try {
            query('INSERT INTO pickup_slots (slot_at) VALUES (?) ON DUPLICATE KEY UPDATE slot_at=VALUES(slot_at)', [$pickup]);
            $slot = query('SELECT * FROM pickup_slots WHERE slot_at=? FOR UPDATE', [$pickup])->fetch();
            if ((int)$slot['booked'] >= (int)$slot['capacity']) throw new DomainException('That collection time is full. Please select another time.');
            $lines = [];
            $total = 0;
            // Always price against the locked catalogue, never submitted prices.
            $cart = cart();
            uasort($cart, fn($a, $b) => $a['item_id'] <=> $b['item_id']);
            foreach ($cart as $line) {
                $item = query('SELECT * FROM menu_items WHERE id=? FOR UPDATE', [$line['item_id']])->fetch();
                if (!$item || !$item['available']) throw new DomainException('An item in your bag has sold out. Return to your bag and remove it.');
                $line['quantity'] = valid_quantity($line['quantity']);
                $lines[] = $line + ['item' => $item];
                $total += (int)$item['price_cents'] * $line['quantity'];
            }
            if ((string)$total !== text_input('expected_total', 12, true)) throw new DomainException('Menu prices have changed. Please review the updated checkout total and try again.');
            $reference = 'FG-' . strtoupper(bin2hex(random_bytes(5)));
            query('INSERT INTO orders (reference,user_id,checkout_key,customer_name,phone,pickup_at,group_name,notes,total_cents) VALUES (?,?,?,?,?,?,?,?,?)', [$reference, $user['id'], $key, $name, $phone, $pickup, $group, $notes, $total]);
            $orderId = (int)$pdo->lastInsertId();
            foreach ($lines as $line) query('INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price_cents,quantity,recipient,instructions) VALUES (?,?,?,?,?,?,?)', [$orderId, $line['item_id'], $line['item']['name'], $line['item']['price_cents'], $line['quantity'], $line['recipient'], $line['instructions']]);
            query('INSERT INTO payments (order_id,amount_cents,reference) VALUES (?,?,?)', [$orderId, $total, 'SIM-' . strtoupper(bin2hex(random_bytes(8)))]);
            query('INSERT INTO order_events (order_id,actor_id,status) VALUES (?,?,?)', [$orderId, $user['id'], 'received']);
            query('UPDATE pickup_slots SET booked=booked+1 WHERE slot_at=?', [$pickup]);
            $pdo->commit();
            unset($_SESSION['cart'], $_SESSION['checkout_key']);
            redirect('confirmation', ['ref' => $reference]);
        } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    }
    if (in_array($action, ['order_status', 'menu_save'], true)) handle_staff_action($action);
    throw new DomainException('Unknown action. Refresh the page and try again.');
}
