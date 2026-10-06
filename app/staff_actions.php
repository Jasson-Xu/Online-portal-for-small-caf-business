<?php
declare(strict_types=1);

function handle_staff_action(string $action): void {
    if ($action === 'order_status') {
        $user = require_user(true);
        $id = text_input('order_id', 20, true);
        $next = text_input('status', 24, true);
        db()->beginTransaction();
        try {
            $order = query('SELECT * FROM orders WHERE id=? FOR UPDATE', [$id])->fetch();
            if (!$order || !in_array($next, next_statuses($order['status']), true)) throw new DomainException('This order has changed or the status transition is not allowed. Refresh the queue.');
            query('UPDATE orders SET status=? WHERE id=?', [$next, $id]);
            query('INSERT INTO order_events (order_id,actor_id,status) VALUES (?,?,?)', [$id, $user['id'], $next]);
            if ($next === 'cancelled') {
                query("UPDATE payments SET status='simulated_refunded' WHERE order_id=?", [$id]);
                query('UPDATE pickup_slots SET booked=booked-1 WHERE slot_at=? AND booked>0', [$order['pickup_at']]);
            }
            db()->commit();
        } catch (Throwable $error) { if (db()->inTransaction()) db()->rollBack(); throw $error; }
        $_SESSION['flash'] = 'Order updated to ' . status_label($next) . '.';
        redirect('staff');
    }
    if ($action === 'menu_save') {
        require_user(true);
        $id = text_input('item_id', 20, true);
        $name = text_input('name', 100, true);
        $description = text_input('description', 500, true);
        $allergens = text_input('allergens', 190);
        $price = text_input('price', 8, true);
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $price) || (float)$price <= 0) throw new DomainException('Enter a price between $0.01 and $999.99.');
        $category = text_input('category', 40, true);
        if (!in_array($category, ['Coffee','Cold drinks','Bakery','Kitchen'], true)) throw new DomainException('Choose a valid category.');
        query('UPDATE menu_items SET name=?,description=?,allergens=?,price_cents=?,category=?,available=? WHERE id=?', [$name,$description,$allergens,(int)round((float)$price*100),$category,isset($_POST['available']) ? 1 : 0,$id]);
        $_SESSION['flash'] = 'Menu item saved.';
        redirect('manage');
    }
    throw new DomainException('Unknown staff action.');
}
