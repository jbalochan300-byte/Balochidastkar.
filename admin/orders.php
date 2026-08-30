<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Manage Orders';
$search = (string) sanitizeInput($_GET['search'] ?? '');
$status = (string) sanitizeInput($_GET['status'] ?? '');
$paymentStatus = (string) sanitizeInput($_GET['payment_status'] ?? '');
$allowedOrderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
$allowedPaymentStatuses = ['pending', 'paid', 'failed', 'cod_pending'];
$orders = [];
$databaseError = false;

try {
    $connection = getPdoConnection();
    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = '(order_number LIKE :order_number OR customer_name LIKE :customer_name OR customer_email LIKE :customer_email)';
        $searchValue = '%' . $search . '%';
        $parameters['order_number'] = $searchValue;
        $parameters['customer_name'] = $searchValue;
        $parameters['customer_email'] = $searchValue;
    }
    if (in_array($status, $allowedOrderStatuses, true)) {
        $conditions[] = 'status = :status';
        $parameters['status'] = $status;
    }
    if (in_array($paymentStatus, $allowedPaymentStatuses, true)) {
        $conditions[] = 'payment_status = :payment_status';
        $parameters['payment_status'] = $paymentStatus;
    }
    $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $statement = $connection->prepare('SELECT id, order_number, customer_name, customer_email, total_amount, status, payment_status, created_at FROM orders ' . $where . ' ORDER BY created_at DESC, id DESC');
    $statement->execute($parameters);
    $orders = $statement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = true;
}

function orderStatusClass(string $status): string
{
    return match ($status) {
        'delivered' => 'text-bg-success',
        'cancelled' => 'text-bg-danger',
        'pending', 'processing' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

function paymentStatusClass(string $status): string
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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Store activity</p><h1 class="section-title mb-2">Orders</h1><p class="text-secondary mb-0">Review customers, items, payments, and delivery progress.</p></div></div>
    <?php if ($databaseError): ?><div class="alert alert-warning">Orders are temporarily unavailable. Please check the database connection.</div><?php else: ?>
        <form class="bg-white border rounded p-3 mb-4" method="get" action="<?= e(url('admin/orders.php')) ?>"><div class="row g-3 align-items-end"><div class="col-lg-5"><label class="form-label" for="search">Search orders</label><input class="form-control" type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="Order number, name, or email"></div><div class="col-sm-6 col-lg-3"><label class="form-label" for="status">Order status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach ($allowedOrderStatuses as $option): ?><option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div><div class="col-sm-6 col-lg-2"><label class="form-label" for="payment_status">Payment</label><select class="form-select" id="payment_status" name="payment_status"><option value="">All payments</option><?php foreach ($allowedPaymentStatuses as $option): ?><option value="<?= e($option) ?>"<?= $paymentStatus === $option ? ' selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option><?php endforeach; ?></select></div><div class="col-lg-2 d-flex gap-2"><button class="btn btn-dark flex-grow-1" type="submit">Search</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/orders.php')) ?>">Clear</a></div></div></form>
        <div class="bg-white border rounded overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th class="text-end">Action</th></tr></thead><tbody><?php if ($orders === []): ?><tr><td colspan="7" class="text-center text-secondary py-5">No orders match your search.</td></tr><?php else: ?><?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e($order['customer_name']) ?><small class="d-block text-secondary"><?= e($order['customer_email']) ?></small></td><td><?= e(formatPrice($order['total_amount'])) ?></td><td><span class="badge <?= e(paymentStatusClass($order['payment_status'])) ?>"><?= e(ucwords(str_replace('_', ' ', $order['payment_status']))) ?></span></td><td><span class="badge <?= e(orderStatusClass($order['status'])) ?>"><?= e(ucfirst($order['status'])) ?></span></td><td class="text-secondary"><?= e(date('d M Y', strtotime($order['created_at']))) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">View</a></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
