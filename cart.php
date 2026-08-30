<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/cart-functions.php';

$pageTitle = 'Cart';
$actionMessage = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) sanitizeInput($_POST['action'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlashMessage('danger', 'Your cart session expired. Please try again.');
        redirect(url('cart.php'));
    }

    $itemKey = (string) sanitizeInput($_POST['item_key'] ?? '');
    if ($action === 'update' && $itemKey !== '') {
        $requestedQuantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        $requestedQuantity = $requestedQuantity === false ? 1 : max(1, $requestedQuantity);
        $cartBeforeUpdate = getCart();
        $item = $cartBeforeUpdate[$itemKey] ?? null;
        if (is_array($item) && $requestedQuantity > (int) ($item['stock_quantity'] ?? CART_MAX_QUANTITY)) {
            $actionMessage = 'Quantity adjusted to the available stock.';
        }
        if (!updateCartItem($itemKey, $requestedQuantity)) {
            $actionMessage = 'That cart item is no longer available.';
        }
    } elseif ($action === 'remove' && $itemKey !== '') {
        removeCartItem($itemKey);
    } elseif ($action === 'clear') {
        clearCart();
    }

    if ($actionMessage !== null) {
        setFlashMessage('warning', $actionMessage);
    }
    redirect(url('cart.php'));
}

$cart = getCart();
$totals = getCartTotals();
$flashMessages = getFlashMessages();
require_once __DIR__ . '/includes/header.php';
?>
<section class="shop-intro"><div class="container py-5"><p class="eyebrow mb-2">Your selection</p><h1 class="section-title mb-2">Shopping cart</h1><p class="text-secondary mb-0"><?= $totals['item_count'] ?> item<?= $totals['item_count'] === 1 ? '' : 's' ?> ready for their next chapter.</p></div></section>
<div class="container py-5">
    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?= e($message['type'] ?? 'info') ?>" role="alert"><?= e($message['message'] ?? '') ?></div>
    <?php endforeach; ?>
    <?php if ($cart === []): ?>
        <div class="empty-cart border text-center p-5"><p class="eyebrow">A quiet beginning</p><h2 class="section-title h1">Your cart is empty.</h2><p class="text-secondary">Find a piece that speaks to you from the collection.</p><a class="btn btn-dark mt-2" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div>
    <?php else: ?>
        <div class="row g-5 align-items-start">
            <div class="col-lg-8">
                <div class="cart-list border-top">
                    <?php foreach ($cart as $itemKey => $item): ?>
                        <?php $itemSubtotal = (float) $item['price'] * (int) $item['quantity']; ?>
                        <article class="cart-item border-bottom py-4">
                            <div class="row align-items-center g-3">
                                <div class="col-4 col-sm-3 col-md-2">
                                    <?php if (!empty($item['image_path'])): ?><img class="cart-item-image" src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>"><?php else: ?><div class="cart-item-image cart-item-placeholder">BD</div><?php endif; ?>
                                </div>
                                <div class="col-8 col-sm-9 col-md-4"><p class="eyebrow mb-1">Dastkar</p><h2 class="h5 mb-1"><?= e($item['name']) ?></h2><p class="small text-secondary mb-0">SKU: <?= e($item['sku']) ?></p><?php if (!empty($item['color'])): ?><p class="small text-secondary mb-0">Color: <?= e($item['color']) ?></p><?php endif; ?></div>
                                <div class="col-6 col-md-2"><span class="small text-secondary d-block d-md-none">Price</span><strong><?= e(formatPrice($item['price'])) ?></strong></div>
                                <div class="col-6 col-md-2"><form method="post" action="<?= e(url('cart.php')) ?>" class="cart-quantity-form"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="item_key" value="<?= e($itemKey) ?>"><button class="quantity-button" type="submit" name="quantity" value="<?= max(1, (int) $item['quantity'] - 1) ?>" aria-label="Decrease quantity">-</button><span><?= (int) $item['quantity'] ?></span><button class="quantity-button" type="submit" name="quantity" value="<?= min((int) $item['stock_quantity'], (int) $item['quantity'] + 1) ?>" aria-label="Increase quantity"<?= (int) $item['quantity'] >= (int) $item['stock_quantity'] ? ' disabled' : '' ?>>+</button></form><small class="text-secondary">Stock: <?= (int) $item['stock_quantity'] ?></small></div>
                                <div class="col-6 col-md-2 text-md-end"><span class="small text-secondary d-block d-md-none">Subtotal</span><strong><?= e(formatPrice($itemSubtotal)) ?></strong><form method="post" action="<?= e(url('cart.php')) ?>" class="mt-2"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_key" value="<?= e($itemKey) ?>"><button class="btn btn-link btn-sm text-danger p-0" type="submit">Remove</button></form></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex flex-wrap justify-content-between gap-3 mt-4"><a class="text-link" href="<?= e(url('shop.php')) ?>">&larr; Continue shopping</a><form method="post" action="<?= e(url('cart.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><input type="hidden" name="action" value="clear"><button class="btn btn-link text-secondary p-0" type="submit">Clear cart</button></form></div>
            </div>
            <aside class="col-lg-4"><div class="cart-summary border p-4"><p class="eyebrow mb-2">Order summary</p><h2 class="h4 mb-4">Your total</h2><div class="d-flex justify-content-between mb-3"><span class="text-secondary">Subtotal</span><strong><?= e(formatPrice($totals['subtotal'])) ?></strong></div><div class="d-flex justify-content-between mb-3"><span class="text-secondary">Shipping</span><strong><?= $totals['shipping'] > 0 ? e(formatPrice($totals['shipping'])) : 'Free' ?></strong></div><hr><div class="d-flex justify-content-between mb-4"><span>Total</span><strong><?= e(formatPrice($totals['total'])) ?></strong></div><a class="btn btn-dark w-100" href="<?= e(url('checkout.php')) ?>">Checkout</a><p class="small text-secondary mt-3 mb-0">Shipping is free on orders over PKR 15,000.</p></div></aside>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
