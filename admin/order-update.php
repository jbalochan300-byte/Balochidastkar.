<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$returnTo = $_POST['return_to'] ?? 'list';
$returnUrl = $returnTo === 'view' && $orderId !== false && $orderId > 0
    ? url('admin/order-view.php?id=' . (int) $orderId)
    : url('admin/orders.php');
$paymentStatuses = ['pending', 'paid', 'failed', 'cod_pending'];
$orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(url('admin/orders.php'));
}

if ($orderId === false || $orderId < 1 || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('danger', 'The order update request was invalid or expired.');
    redirect($returnUrl);
}

$paymentStatus = sanitizeInput($_POST['payment_status'] ?? '');
$orderStatus = sanitizeInput($_POST['status'] ?? '');
if (!is_string($paymentStatus) || !in_array($paymentStatus, $paymentStatuses, true)
    || !is_string($orderStatus) || !in_array($orderStatus, $orderStatuses, true)) {
    setFlashMessage('danger', 'Please select valid payment and order statuses.');
    redirect($returnUrl);
}

try {
    $statement = getPdoConnection()->prepare(
        'UPDATE orders
         SET payment_status = :payment_status, status = :status
         WHERE id = :id'
    );
    $statement->execute([
        'payment_status' => $paymentStatus,
        'status' => $orderStatus,
        'id' => $orderId,
    ]);
    setFlashMessage('success', 'Order status updated successfully.');
} catch (Throwable $exception) {
    setFlashMessage('danger', 'The order could not be updated. Please try again later.');
}

redirect($returnUrl);
