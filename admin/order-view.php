<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$order = null;
$orderItems = [];
$databaseError = false;
if ($orderId !== false && $orderId !== null && $orderId > 0) {
    try {
        $connection = getPdoConnection();
        $statement = $connection->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();
        if (is_array($order)) {
            $itemStatement = $connection->prepare('SELECT product_name, variant_name, quantity, unit_price FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
            $itemStatement->execute(['order_id' => $orderId]);
            $orderItems = $itemStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        $databaseError = true;
    }
}
$pageTitle = 'Order Details';
$csrfToken = generateCsrfToken();

function orderViewStatusClass(string $status): string
{
    return match ($status) {
        'delivered' => 'text-bg-success',
        'cancelled' => 'text-bg-danger',
        'pending', 'processing' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

function orderViewPaymentClass(string $status): string
{
    return match ($status) {
        'paid' => 'text-bg-success',
        'failed' => 'text-bg-danger',
        'cod_pending' => 'text-bg-info',
        default => 'text-bg-warning',
    };
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Order review</p><h1 class="section-title mb-2">Order details</h1><p class="text-secondary mb-0">Inspect the customer purchase and update its progress.</p></div><a class="btn btn-outline-secondary" href="<?= e(url('admin/orders.php')) ?>">Back to orders</a></div>
    <?php if ($databaseError): ?><div class="alert alert-warning">Order details are temporarily unavailable. Please check the database connection.</div><?php elseif (!is_array($order)): ?><div class="alert alert-danger">The requested order could not be found.</div><?php else: ?>
        <div class="row g-4"><div class="col-lg-8"><section class="bg-white border rounded p-4 mb-4"><div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-4"><div><p class="eyebrow mb-1">Order number</p><h2 class="h3 mb-0"><?= e($order['order_number']) ?></h2></div><div class="text-sm-end"><span class="badge <?= e(orderStatusClass($order['status'])) ?>"><?= e(ucfirst($order['status'])) ?></span><span class="badge <?= e(paymentStatusClass($order['payment_status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $order['payment_status']))) ?></span></div></div><h3 class="h5">Products</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product</th><th>Color</th><th>Quantity</th><th>Unit price</th><th class="text-end">Subtotal</th></tr></thead><tbody><?php foreach ($orderItems as $item): ?><tr><td><?= e($item['product_name']) ?></td><td><?= e($item['variant_name'] ?: '-') ?></td><td><?= (int) $item['quantity'] ?></td><td><?= e(formatPrice($item['unit_price'])) ?></td><td class="text-end"><?= e(formatPrice((float) $item['unit_price'] * (int) $item['quantity'])) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="d-flex justify-content-end"><strong>Total: <?= e(formatPrice($order['total_amount'])) ?></strong></div></section><section class="bg-white border rounded p-4"><h2 class="h5 mb-3">Order status</h2><form method="post" action="<?= e(url('admin/order-update.php')) ?>"><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="return_to" value="view"><div class="row g-3"><div class="col-md-6"><label class="form-label" for="payment_status">Payment</label><select class="form-select" id="payment_status" name="payment_status"><?php foreach (['pending', 'paid', 'failed', 'cod_pending'] as $option): ?><option value="<?= e($option) ?>"<?= $order['payment_status'] === $option ? ' selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label" for="status">Order</label><select class="form-select" id="status" name="status"><?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $option): ?><option value="<?= e($option) ?>"<?= $order['status'] === $option ? ' selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div></div><button class="btn btn-dark mt-3" type="submit">Update order</button></form></section></div><aside class="col-lg-4"><section class="bg-white border rounded p-4 mb-4"><h2 class="h5 mb-3">Customer information</h2><p class="mb-2"><strong><?= e($order['customer_name']) ?></strong></p><p class="small text-secondary mb-2"><?= e($order['customer_email']) ?></p><p class="small text-secondary mb-2"><?= e($order['customer_phone'] ?: 'No phone provided') ?></p><p class="small mb-0"><?= nl2br(e($order['shipping_address'])) ?><br><?= e($order['city']) ?></p></section><section class="bg-white border rounded p-4"><h2 class="h5 mb-3">Payment and notes</h2><p class="small mb-2"><span class="text-secondary">Method:</span> <?= e(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></p><p class="small text-secondary mb-0"><?= e($order['additional_notes'] ?: 'No additional notes.') ?></p></section></aside></div>
+    <?php endif; ?>
+</div>
+<?php require_once __DIR__ . '/includes/footer.php'; ?>
*** End Patch