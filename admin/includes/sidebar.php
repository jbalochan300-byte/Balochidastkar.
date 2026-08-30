<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$navigation = [
    ['label' => 'Dashboard', 'path' => 'admin/dashboard.php', 'page' => 'dashboard.php'],
    ['label' => 'Products', 'path' => 'admin/products.php', 'page' => 'products.php'],
    ['label' => 'Orders', 'path' => 'admin/orders.php', 'page' => 'orders.php'],
    ['label' => 'Messages', 'path' => 'admin/messages.php', 'page' => 'messages.php'],
    ['label' => 'Newsletter', 'path' => 'admin/newsletter.php', 'page' => 'newsletter.php'],
];
?>
<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-label="Admin navigation">
    <div class="offcanvas-header d-lg-none">
        <span class="admin-sidebar-title">Navigation</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close navigation"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div class="admin-sidebar-heading d-none d-lg-block">Workspace</div>
        <nav class="admin-nav" aria-label="Primary admin navigation">
            <?php foreach ($navigation as $item): ?>
                <?php $isActive = $currentPage === $item['page']; ?>
                <a class="admin-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <span class="admin-nav-icon" aria-hidden="true"><?= e(substr($item['label'], 0, 1)) ?></span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="mt-auto">
            <div class="admin-sidebar-divider"></div>
            <a class="admin-nav-link admin-logout-link" href="<?= e(url('admin/logout.php')) ?>">
                <span class="admin-nav-icon" aria-hidden="true">&larr;</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
