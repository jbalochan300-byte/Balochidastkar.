<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Dashboard';
$stats = null;
$recentOrders = [];
$databaseError = false;

try {
    $connection = getPdoConnection();

    $statsStatement = $connection->prepare(
        'SELECT
            (SELECT COUNT(*) FROM products) AS total_products,
            (SELECT COUNT(*) FROM products WHERE is_active = 1) AS active_products,
            (SELECT COUNT(*) FROM orders) AS total_orders,
            (SELECT COUNT(*) FROM orders WHERE status = :pending_status) AS pending_orders,
            (SELECT COUNT(*) FROM orders WHERE status = :delivered_status) AS delivered_orders,
            (SELECT COUNT(*) FROM contact_messages) AS contact_messages,
            (SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1) AS newsletter_subscribers'
    );
    $statsStatement->execute([
        'pending_status' => 'pending',
        'delivered_status' => 'delivered',
    ]);
    $stats = $statsStatement->fetch();

    $ordersStatement = $connection->prepare(
        'SELECT order_number, customer_name, total_amount, status, created_at
         FROM orders
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $ordersStatement->execute();
    $recentOrders = $ordersStatement->fetchAll();
} catch (RuntimeException $exception) {
    $databaseError = true;
}

function dashboardStatusClass(string $status): string
{
    return match ($status) {
        'completed' => 'text-bg-success',
        'pending' => 'text-bg-warning',
        'cancelled' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Overview</p>
            <h1 class="section-title mb-2">Dashboard</h1>
            <p class="text-secondary mb-0">A quick view of your <?= e(APP_NAME) ?> store.</p>
        </div>
        <span class="text-secondary small">Updated <?= e(date('d M Y, H:i')) ?></span>
    </div>

    <?php if ($databaseError): ?>
        <div class="alert alert-warning" role="alert">
            Store statistics are temporarily unavailable. Please check the database connection.
        </div>
    <?php elseif (is_array($stats)): ?>
        <div class="row g-3 mb-5">
            <?php
            $statCards = [
                ['label' => 'Total Products', 'value' => $stats['total_products'], 'accent' => 'clay'],
                ['label' => 'Active Products', 'value' => $stats['active_products'], 'accent' => 'sage'],
                ['label' => 'Total Orders', 'value' => $stats['total_orders'], 'accent' => 'gold'],
                ['label' => 'Pending Orders', 'value' => $stats['pending_orders'], 'accent' => 'orange'],
                ['label' => 'Delivered Orders', 'value' => $stats['delivered_orders'], 'accent' => 'green'],
                ['label' => 'Contact Messages', 'value' => $stats['contact_messages'], 'accent' => 'blue'],
                ['label' => 'Newsletter Subscribers', 'value' => $stats['newsletter_subscribers'], 'accent' => 'purple'],
            ];
            ?>
            <?php foreach ($statCards as $card): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="admin-stat-card admin-stat-<?= e($card['accent']) ?>">
                        <p><?= e($card['label']) ?></p>
                        <strong><?= e((string) $card['value']) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <section class="admin-panel" aria-labelledby="recent-orders-heading">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <p class="eyebrow mb-1">Latest activity</p>
                    <h2 class="h4 mb-0" id="recent-orders-heading">Recent orders</h2>
                </div>
                <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/orders.php')) ?>">View all</a>
            </div>
            <?php if ($recentOrders === []): ?>
                <p class="text-secondary mb-0">No orders have been placed yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($order['order_number']) ?></td>
                                    <td><?= e($order['customer_name']) ?></td>
                                    <td><?= e(formatPrice($order['total_amount'])) ?></td>
                                    <td><span class="badge <?= e(dashboardStatusClass($order['status'])) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                                    <td class="text-secondary"><?= e(date('d M Y', strtotime($order['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
