<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

const CART_MAX_QUANTITY = 99;
const CART_SHIPPING_FEE = 500.00;
const CART_FREE_SHIPPING_THRESHOLD = 15000.00;

function getCart(): array
{
    startSecureSession();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    return $_SESSION['cart'];
}

function addProductToCart(int $productId, int $quantity = 1, ?string $selectedColor = null): bool
{
    if ($productId < 1 || $quantity < 1) {
        return false;
    }

    $statement = getPdoConnection()->prepare(
        'SELECT id, name, price, sale_price, sku, stock_quantity, colors,
            (SELECT pi.image_path
             FROM product_images pi
             WHERE pi.product_id = products.id
             ORDER BY pi.sort_order ASC, pi.id ASC
             LIMIT 1) AS image_path
         FROM products
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $statement->execute(['id' => $productId]);
    $product = $statement->fetch();

    if (!is_array($product) || (int) $product['stock_quantity'] < 1) {
        return false;
    }

    $color = sanitizeInput($selectedColor);
    $color = is_string($color) && $color !== '' ? $color : null;

    $variant = null;
    if ($color !== null) {
        $variantStatement = getPdoConnection()->prepare(
            'SELECT id, variant_name, sku, price, stock_quantity
             FROM product_variants
             WHERE product_id = :product_id
               AND variant_name = :variant_name
               AND is_active = 1
             LIMIT 1'
        );
        $variantStatement->execute(['product_id' => $productId, 'variant_name' => $color]);
        $variant = $variantStatement->fetch();
        if (!is_array($variant) && !productHasColor((string) ($product['colors'] ?? ''), $color)) {
            return false;
        }
    }

    $stockQuantity = is_array($variant) ? (int) $variant['stock_quantity'] : (int) $product['stock_quantity'];
    if ($stockQuantity < 1) {
        return false;
    }
    $itemQuantity = min($quantity, CART_MAX_QUANTITY, $stockQuantity);
    $itemKey = getCartItemKey($productId, $color);
    $cart = getCart();

    if (isset($cart[$itemKey])) {
        $itemQuantity = min(
            (int) $cart[$itemKey]['quantity'] + $itemQuantity,
            CART_MAX_QUANTITY,
            $stockQuantity
        );
    }

    $cart[$itemKey] = [
        'product_id' => $productId,
        'name' => (string) $product['name'],
        'sku' => is_array($variant) ? (string) $variant['sku'] : (string) $product['sku'],
        'price' => getProductPrice($product) + (is_array($variant) ? (float) ($variant['price'] ?? 0) : 0),
        'quantity' => $itemQuantity,
        'color' => $color,
        'variant_id' => is_array($variant) ? (int) $variant['id'] : null,
        'image_path' => $product['image_path'] ?? null,
        'stock_quantity' => $stockQuantity,
    ];

    saveCart($cart);
    return true;
}

function updateCartItem(string $itemKey, int $quantity): bool
{
    $cart = getCart();

    if (!isset($cart[$itemKey])) {
        return false;
    }

    if ($quantity < 1) {
        return removeCartItem($itemKey);
    }

    $maxQuantity = min(CART_MAX_QUANTITY, (int) ($cart[$itemKey]['stock_quantity'] ?? CART_MAX_QUANTITY));
    $cart[$itemKey]['quantity'] = min($quantity, $maxQuantity);
    saveCart($cart);

    return true;
}

function removeCartItem(string $itemKey): bool
{
    $cart = getCart();

    if (!isset($cart[$itemKey])) {
        return false;
    }

    unset($cart[$itemKey]);
    saveCart($cart);

    return true;
}

function clearCart(): void
{
    startSecureSession();
    $_SESSION['cart'] = [];
}

function getCartItemCount(): int
{
    $count = 0;

    foreach (getCart() as $item) {
        $count += (int) ($item['quantity'] ?? 0);
    }

    return $count;
}

function getCartSubtotal(): float
{
    $subtotal = 0.00;

    foreach (getCart() as $item) {
        $subtotal += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
    }

    return round($subtotal, 2);
}

function getCartShipping(): float
{
    $subtotal = getCartSubtotal();

    if ($subtotal <= 0 || $subtotal >= CART_FREE_SHIPPING_THRESHOLD) {
        return 0.00;
    }

    return CART_SHIPPING_FEE;
}

function getCartTotal(): float
{
    return round(getCartSubtotal() + getCartShipping(), 2);
}

function getCartTotals(): array
{
    $subtotal = getCartSubtotal();
    $shipping = getCartShipping();

    return [
        'item_count' => getCartItemCount(),
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => round($subtotal + $shipping, 2),
    ];
}

function getCartItemKey(int $productId, ?string $color = null): string
{
    return hash('sha256', $productId . '|' . strtolower((string) $color));
}

function productHasColor(string $availableColors, string $selectedColor): bool
{
    $colors = array_map(
        static fn (string $color): string => strtolower(trim($color)),
        explode(',', $availableColors)
    );

    return in_array(strtolower(trim($selectedColor)), $colors, true);
}

function getProductPrice(array $product): float
{
    $regularPrice = (float) ($product['price'] ?? 0);
    $salePrice = (float) ($product['sale_price'] ?? 0);

    if ($salePrice > 0 && $salePrice < $regularPrice) {
        return $salePrice;
    }

    return $regularPrice;
}

function saveCart(array $cart): void
{
    startSecureSession();
    $_SESSION['cart'] = $cart;
}
