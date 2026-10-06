<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/actions.php';
require __DIR__ . '/../app/views.php';
$pages = ['home','menu','login','register','cart','checkout','confirmation','orders','track','staff','manage','privacy'];
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
if (!in_array($page, $pages, true)) { http_response_code(404); $page = 'not-found'; }
$error = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') handle_action();
    if (in_array($page, ['checkout','confirmation','orders','track'], true)) require_user();
    if (in_array($page, ['staff','manage'], true)) require_user(true);
    $user = current_user();
    // Render into a buffer so errors never leak a half-finished private page.
    ob_start();
    render_page($page, $user);
    $content = ob_get_clean();
} catch (Throwable $ex) {
    if (ob_get_level()) ob_end_clean();
    $user = null;
    if ($ex instanceof DomainException) {
        if (http_response_code() < 400) http_response_code(422);
        $error = $ex->getMessage();
    } elseif ($ex instanceof RuntimeException && !($ex instanceof PDOException)) {
        $error = $ex->getMessage();
    } else {
        http_response_code(503);
        error_log('Cafe portal: ' . $ex->getMessage());
        $error = 'We cannot complete that request right now. Please try again shortly. If you placed an order, check My orders before retrying.';
    }
    $content = '<section class="empty panel"><span class="eyebrow">Let’s sort that out</span><h1>Something needs a second look.</h1><p role="alert">' . e($error) . '</p><div class="actions"><a class="button" href="' . e(url(in_array($page, $pages, true) ? $page : 'home')) . '">Return to page</a><a class="button secondary" href="' . e(url('orders')) . '">My orders</a></div></section>';
}
$titles = ['home'=>'Good mornings start here','menu'=>'The menu','login'=>'Welcome back','register'=>'Join the table','cart'=>'Your bag','checkout'=>'Checkout','confirmation'=>'Order confirmed','orders'=>'Your orders','track'=>'Track your order','staff'=>'The order queue','manage'=>'Manage the menu','privacy'=>'Privacy & payment'];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#324b3c"><meta name="description" content="Order coffee, fresh pastries and café favourites ahead. A small café click-and-collect demonstration."><title><?= e($titles[$page] ?? 'Page not found') ?> · Folks & Co.</title><link rel="icon" href="assets/mark.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/styles.css"><script src="assets/app.js" defer></script></head>
<body><a class="skip-link" href="#main">Skip to content</a>
<div class="announcement">YOUR NEIGHBOURHOOD CAFÉ, A LITTLE CLOSER. <span>Open daily · 7 am – 4 pm</span></div>
<header class="header"><a class="brand" href="<?= e(url('home')) ?>" aria-label="Folks and Co home"><img src="assets/mark.svg" width="38" height="38" alt=""><span>folks<span class="brand-small">& co.</span></span></a><nav aria-label="Main navigation"><a <?= $page==='menu'?'aria-current="page"':'' ?> href="<?= e(url('menu')) ?>">Our menu</a><a <?= in_array($page,['orders','track'])?'aria-current="page"':'' ?> href="<?= e(url('orders')) ?>">My orders</a><?php if (($user['role'] ?? '') === 'staff'): ?><a href="<?= e(url('staff')) ?>">Staff desk</a><?php endif; ?></nav><div class="header-actions"><?php if ($user): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="text-button" type="submit">Sign out</button></form><?php else: ?><a class="signin" href="<?= e(url('login')) ?>">Sign in <span aria-hidden="true">↗</span></a><?php endif; ?><a class="bag" href="<?= e(url('cart')) ?>">Bag <span><?= cart_count() ?></span></a></div></header>
<main id="main" tabindex="-1"><?php if (isset($_SESSION['flash'])): ?><div class="notice" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?><?= $content ?></main>
<footer><a class="brand" href="<?= e(url('home')) ?>">folks<span class="brand-small">& co.</span></a><p>Good coffee. Good company. Your kind of place.</p><div><a href="<?= e(url('privacy')) ?>">Privacy & payment</a><span>ICT312 student project · Simulated payments only</span></div></footer>
</body></html>
