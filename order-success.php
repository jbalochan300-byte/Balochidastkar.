<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Order Confirmed';
$order = null;
$orderItems = [];
$databaseError = false;
startSecureSession();
$orderId = filter_var($_SESSION['last_order_id'] ?? null, FILTER_VALIDATE_INT);

if ($orderId !== false && $orderId !== null && $orderId > 0) {
    try {
        $connection = getPdoConnection();
        $orderStatement = $connection->prepare(
            'SELECT order_number, customer_name, total_amount, payment_method, status
             FROM orders
             WHERE id = :id
             LIMIT 1'
        );
        $orderStatement->execute(['id' => $orderId]);
        $order = $orderStatement->fetch();

        if (is_array($order)) {
            $itemsStatement = $connection->prepare(
                'SELECT product_name, variant_name, quantity, unit_price
                 FROM order_items
                 WHERE order_id = :order_id
                 ORDER BY id ASC'
            );
            $itemsStatement->execute(['order_id' => $orderId]);
            $orderItems = $itemsStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        $databaseError = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <?php if ($databaseError): ?>
        <div class="alert alert-warning" role="alert">Your order details are temporarily unavailable. Please try again later.</div>
    <?php elseif (!is_array($order)): ?>
        <div class="empty-cart border text-center p-5"><p class="eyebrow">Order details</p><h1 class="section-title h1">No recent order found.</h1><p class="text-secondary">Your order confirmation may have expired.</p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div>
    <?php else: ?>
        <div class="checkout-success border p-4 p-lg-5">
            <div class="text-center mb-5"><p class="eyebrow">Order confirmed</p><h1 class="section-title">Thank you, <?= e($order['customer_name']) ?>.</h1><p class="lead text-secondary mb-0">Your order has been received successfully.</p></div>
            <div class="row g-4 mb-5"><div class="col-sm-6 col-lg-3"><span class="small text-secondary d-block">Order number</span><strong><?= e($order['order_number']) ?></strong></div><div class="col-sm-6 col-lg-3"><span class="small text-secondary d-block">Payment method</span><strong><?= e(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></strong></div><div class="col-sm-6 col-lg-3"><span class="small text-secondary d-block">Order status</span><strong><?= e(ucfirst($order['status'])) ?></strong></div><div class="col-sm-6 col-lg-3"><span class="small text-secondary d-block">Total</span><strong><?= e(formatPrice($order['total_amount'])) ?></strong></div></div>
            <h2 class="h4 mb-3">Products</h2>
            <div class="border-top">
                <?php foreach ($orderItems as $item): ?><div class="d-flex justify-content-between gap-3 border-bottom py-3"><span><?= e($item['product_name']) ?><?php if (!empty($item['variant_name'])): ?><small class="d-block text-secondary">Color: <?= e($item['variant_name']) ?></small><?php endif; ?><small class="d-block text-secondary">Qty: <?= (int) $item['quantity'] ?></small></span><strong><?= e(formatPrice((float) $item['unit_price'] * (int) $item['quantity'])) ?></strong></div><?php endforeach; ?>
            </div>
            <div class="text-center mt-5"><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
