<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart-functions.php';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$cartCount = 0;
if (function_exists('getCartItemCount')) {
    $cartCount = getCartItemCount();
}
$navItems = [
    ['label' => 'Home', 'path' => 'index.php', 'page' => 'index.php'],
    ['label' => 'Shop', 'path' => 'shop.php', 'page' => 'shop.php'],
    ['label' => 'About', 'path' => 'about.php', 'page' => 'about.php'],
    ['label' => 'FAQ', 'path' => 'faq.php', 'page' => 'faq.php'],
    ['label' => 'Contact', 'path' => 'contact.php', 'page' => 'contact.php'],
];
?>
<nav class="navbar navbar-expand-lg site-navbar border-bottom" aria-label="Main navigation">
    <div class="container py-2">
        <a class="navbar-brand brand-mark d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>" aria-label="<?= e(APP_NAME) ?> home"><img class="site-logo" src="<?= e(url('assets/images/logo-icon.png')) ?>" alt="<?= e(APP_NAME) ?> logo"><span class="brand-wordmark d-none d-sm-flex"><?= e(APP_NAME) ?></span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = $currentPage === $item['page']; ?>
                    <li class="nav-item"><a class="nav-link site-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e(url($item['path'])) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
                <li class="nav-item mt-2 mt-lg-0 ms-lg-2">
                    <form class="site-search" method="get" action="<?= e(url('shop.php')) ?>" role="search">
                            <label class="visually-hidden" for="siteSearch">Search products</label>
                        <input id="siteSearch" name="search" type="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Search" aria-label="Search products">
                        <button type="submit" title="Search products" aria-label="Search products">Search</button>
                    </form>
                </li>
                <li class="nav-item mt-2 mt-lg-0"><a class="nav-link site-nav-link" href="<?= e(url('cart.php')) ?>">Cart <span class="cart-count" aria-label="<?= (int) $cartCount ?> items in cart"><?= (int) $cartCount ?></span></a></li>
                <li class="nav-item mt-2 mt-lg-0"><button class="btn theme-toggle" type="button" id="themeToggle" title="Switch color theme" aria-label="Switch color theme" aria-pressed="false"><span id="themeToggleLabel">Dark</span></button></li>
                <li class="nav-item mt-2 mt-lg-0"><a class="btn btn-dark btn-sm admin-nav-button" href="<?= e(url('admin/login.php')) ?>">Admin</a></li>
            </ul>
        </div>
    </div>
</nav>
