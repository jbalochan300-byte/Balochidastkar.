<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!function_exists('orderStatusClass')) {
    function orderStatusClass(string $status): string
    {
        return match ($status) {
            'delivered' => 'text-bg-success',
            'cancelled' => 'text-bg-danger',
            'pending', 'processing' => 'text-bg-warning',
            default => 'text-bg-secondary',
        };
    }
}

if (!function_exists('paymentStatusClass')) {
    function paymentStatusClass(string $status): string
    {
        return match ($status) {
            'paid' => 'text-bg-success',
            'failed' => 'text-bg-danger',
            'cod_pending' => 'text-bg-info',
            default => 'text-bg-warning',
        };
    }
}

$pageTitle = $pageTitle ?? 'Admin Panel';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn admin-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="admin-brand" href="<?= e(url('admin/dashboard.php')) ?>">
                <img class="admin-brand-logo" src="<?= e(url('assets/images/logo-icon.png')) ?>" alt="<?= e(APP_NAME) ?> logo">
                <span><?= e(APP_NAME) ?> <small>Admin</small></span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($currentAdmin['name'])): ?>
                <span class="admin-current-user d-none d-sm-inline text-secondary small">Signed in as <strong><?= e($currentAdmin['name']) ?></strong></span>
            <?php endif; ?>
            <a class="btn btn-outline-dark btn-sm" href="<?= e(url('index.php')) ?>">View website</a>
            <a class="btn btn-outline-danger btn-sm" href="<?= e(url('admin/logout.php')) ?>">Logout</a>
        </div>
    </header>
    <div class="admin-layout">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="admin-content">
