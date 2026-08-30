<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart-functions.php';

$pageTitle = 'Checkout';
$formData = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'city' => '', 'additional_notes' => ''];
$errors = [];
$orderNumber = null;
$checkoutItems = [];
$checkoutTotals = ['item_count' => 0, 'subtotal' => 0.00, 'shipping' => 0.00, 'total' => 0.00];
$databaseError = false;

function loadCheckoutItems(PDO $connection, array $cart, bool $lockRows = false): array
{
    $items = [];
    foreach ($cart as $itemKey => $cartItem) {
        if (!is_array($cartItem)) {
            continue;
        }
        $productId = filter_var($cartItem['product_id'] ?? null, FILTER_VALIDATE_INT);
        $variantId = filter_var($cartItem['variant_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($cartItem['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
            throw new RuntimeException('One of the cart items is invalid.');
        }
        $productSql = 'SELECT id, name, sku, price, sale_price, stock_quantity FROM products WHERE id = :product_id AND is_active = 1 LIMIT 1' . ($lockRows ? ' FOR UPDATE' : '');
        $productStatement = $connection->prepare($productSql);
        $productStatement->execute(['product_id' => $productId]);
        $product = $productStatement->fetch();
        if (!is_array($product)) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }
        $variant = null;
        if ($variantId !== false && $variantId > 0) {
            $variantSql = 'SELECT id, variant_name, sku, price, stock_quantity FROM product_variants WHERE id = :variant_id AND product_id = :product_id AND is_active = 1 LIMIT 1' . ($lockRows ? ' FOR UPDATE' : '');
            $variantStatement = $connection->prepare($variantSql);
            $variantStatement->execute(['variant_id' => $variantId, 'product_id' => $productId]);
            $variant = $variantStatement->fetch();
            if (!is_array($variant)) {
                throw new RuntimeException('A selected color in your cart is no longer available.');
            }
        }
        $regularPrice = (float) $product['price'];
        $basePrice = $product['sale_price'] !== null && (float) $product['sale_price'] > 0 && (float) $product['sale_price'] < $regularPrice ? (float) $product['sale_price'] : $regularPrice;
        $unitPrice = $basePrice + (is_array($variant) ? (float) ($variant['price'] ?? 0) : 0);
        $stockQuantity = is_array($variant) ? (int) $variant['stock_quantity'] : (int) $product['stock_quantity'];
        if ($quantity > $stockQuantity) {
            throw new RuntimeException('Stock changed for ' . $product['name'] . '. Please adjust your cart.');
        }
        $items[$itemKey] = ['product_id' => (int) $product['id'], 'variant_id' => is_array($variant) ? (int) $variant['id'] : null, 'name' => (string) $product['name'], 'sku' => is_array($variant) ? (string) $variant['sku'] : (string) $product['sku'], 'variant_name' => is_array($variant) ? (string) $variant['variant_name'] : ($cartItem['color'] ?? null), 'image_path' => (string) ($cartItem['image_path'] ?? ''), 'quantity' => $quantity, 'unit_price' => $unitPrice, 'subtotal' => round($unitPrice * $quantity, 2), 'stock_quantity' => $stockQuantity];
    }
    return $items;
}

function calculateCheckoutTotals(array $items): array
{
    $subtotal = 0.00;
    $itemCount = 0;
    foreach ($items as $item) { $subtotal += (float) $item['subtotal']; $itemCount += (int) $item['quantity']; }
    $subtotal = round($subtotal, 2);
    $shipping = $subtotal > 0 && $subtotal < CART_FREE_SHIPPING_THRESHOLD ? CART_SHIPPING_FEE : 0.00;
    return ['item_count' => $itemCount, 'subtotal' => $subtotal, 'shipping' => $shipping, 'total' => round($subtotal + $shipping, 2)];
}

try {
    $checkoutItems = loadCheckoutItems(getPdoConnection(), getCart());
    $checkoutTotals = calculateCheckoutTotals($checkoutItems);
} catch (Throwable $exception) {
    $databaseError = true;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$databaseError) {
    $formData = ['full_name' => (string) sanitizeInput($_POST['full_name'] ?? ''), 'email' => (string) sanitizeInput($_POST['email'] ?? ''), 'phone' => (string) sanitizeInput($_POST['phone'] ?? ''), 'address' => (string) sanitizeInput($_POST['address'] ?? ''), 'city' => (string) sanitizeInput($_POST['city'] ?? ''), 'additional_notes' => (string) sanitizeInput($_POST['additional_notes'] ?? '')];
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) { $errors['form'] = 'Your checkout session expired. Please try again.'; }
    $errors = array_merge($errors, validateInput($formData, ['full_name' => ['label' => 'Full name', 'required' => true, 'max' => 120], 'email' => ['label' => 'Email', 'required' => true, 'email' => true, 'max' => 190], 'phone' => ['label' => 'Phone', 'required' => true, 'max' => 30], 'address' => ['label' => 'Address', 'required' => true, 'min' => 5], 'city' => ['label' => 'City', 'required' => true, 'max' => 100], 'additional_notes' => ['label' => 'Additional notes', 'max' => 1000]]));
    if ($checkoutItems === []) { $errors['form'] = 'Your cart is empty.'; }
    if ($errors === []) {
        try {
            $connection = getPdoConnection();
            $connection->beginTransaction();
            $checkoutItems = loadCheckoutItems($connection, getCart(), true);
            $checkoutTotals = calculateCheckoutTotals($checkoutItems);
            if ($checkoutItems === []) { throw new RuntimeException('Your cart is empty.'); }
            $temporaryOrderNumber = 'PENDING-' . bin2hex(random_bytes(12));
            $orderStatement = $connection->prepare('INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, city, additional_notes, payment_method, status, payment_status, total_amount) VALUES (:order_number, :customer_name, :customer_email, :customer_phone, :shipping_address, :city, :additional_notes, :payment_method, :status, :payment_status, :total_amount)');
            $orderStatement->execute(['order_number' => $temporaryOrderNumber, 'customer_name' => $formData['full_name'], 'customer_email' => $formData['email'], 'customer_phone' => $formData['phone'], 'shipping_address' => $formData['address'], 'city' => $formData['city'], 'additional_notes' => $formData['additional_notes'] === '' ? null : $formData['additional_notes'], 'payment_method' => 'cash_on_delivery', 'status' => 'pending', 'payment_status' => 'cod_pending', 'total_amount' => number_format($checkoutTotals['total'], 2, '.', '')]);
            $orderId = (int) $connection->lastInsertId();
            $orderNumber = 'BD-' . date('Y') . '-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
            $numberStatement = $connection->prepare('UPDATE orders SET order_number = :order_number WHERE id = :id');
            $numberStatement->execute(['order_number' => $orderNumber, 'id' => $orderId]);
            $itemStatement = $connection->prepare('INSERT INTO order_items (order_id, product_id, variant_id, product_name, variant_name, quantity, unit_price) VALUES (:order_id, :product_id, :variant_id, :product_name, :variant_name, :quantity, :unit_price)');
            foreach ($checkoutItems as $item) {
                $itemStatement->execute(['order_id' => $orderId, 'product_id' => $item['product_id'], 'variant_id' => $item['variant_id'], 'product_name' => $item['name'], 'variant_name' => $item['variant_name'], 'quantity' => $item['quantity'], 'unit_price' => number_format($item['unit_price'], 2, '.', '')]);
                $table = $item['variant_id'] !== null ? 'product_variants' : 'products';
                $where = $item['variant_id'] !== null ? 'id = :stock_id AND product_id = :product_id' : 'id = :stock_id';
                $stockStatement = $connection->prepare("UPDATE {$table} SET stock_quantity = stock_quantity - :decrement WHERE {$where} AND stock_quantity >= :minimum_stock");
                $stockStatement->execute(['decrement' => $item['quantity'], 'minimum_stock' => $item['quantity'], 'stock_id' => $item['variant_id'] ?? $item['product_id'], 'product_id' => $item['product_id']]);
                if ($stockStatement->rowCount() !== 1) { throw new RuntimeException('Stock changed while placing your order. Please try again.'); }
            }
            $connection->commit();
            clearCart();
            startSecureSession();
            $_SESSION['last_order_id'] = $orderId;
            redirect(url('order-success.php'));
        } catch (Throwable $exception) {
            if (isset($connection) && $connection->inTransaction()) { $connection->rollBack(); }
            $orderNumber = null;
            $errors['form'] = $exception instanceof RuntimeException ? $exception->getMessage() : 'The order could not be created. Please try again later.';
        }
    }
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($orderNumber !== null): ?><section class="container py-5"><div class="checkout-success border p-5 text-center"><p class="eyebrow">Thank you</p><h1 class="section-title">Your order is on its way.</h1><p class="text-secondary">Order number: <strong><?= e($orderNumber) ?></strong>. We will contact you to confirm delivery.</p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div></section><?php else: ?><section class="shop-intro"><div class="container py-5"><p class="eyebrow mb-2">Almost yours</p><h1 class="section-title mb-2">Checkout</h1><p class="text-secondary mb-0">Complete your details for Cash on Delivery.</p></div></section><div class="container py-5"><?php if ($databaseError): ?><div class="alert alert-warning">Checkout is temporarily unavailable. Please try again later.</div><?php endif; ?><?php if (isset($errors['form'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div><?php endif; ?><?php if ($checkoutItems === [] && !$databaseError): ?><div class="empty-cart border text-center p-5"><h2 class="section-title h1">Your cart is empty.</h2><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div><?php else: ?><div class="row g-5 align-items-start"><div class="col-lg-7"><form method="post" action="<?= e(url('checkout.php')) ?>" class="bg-white border p-4" novalidate><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><h2 class="h4 mb-4">Delivery details</h2><div class="mb-3"><label class="form-label" for="full_name">Full Name</label><input class="form-control" type="text" id="full_name" name="full_name" value="<?= e($formData['full_name']) ?>" maxlength="120" required><?= isset($errors['full_name']) ? '<div class="invalid-feedback d-block">' . e($errors['full_name']) . '</div>' : '' ?></div><div class="row g-3"><div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" value="<?= e($formData['email']) ?>" maxlength="190" required><?= isset($errors['email']) ? '<div class="invalid-feedback d-block">' . e($errors['email']) . '</div>' : '' ?></div><div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" type="tel" id="phone" name="phone" value="<?= e($formData['phone']) ?>" maxlength="30" required><?= isset($errors['phone']) ? '<div class="invalid-feedback d-block">' . e($errors['phone']) . '</div>' : '' ?></div></div><div class="mb-3 mt-3"><label class="form-label" for="address">Address</label><textarea class="form-control" id="address" name="address" rows="3" required><?= e($formData['address']) ?></textarea><?= isset($errors['address']) ? '<div class="invalid-feedback d-block">' . e($errors['address']) . '</div>' : '' ?></div><div class="mb-3"><label class="form-label" for="city">City</label><input class="form-control" type="text" id="city" name="city" value="<?= e($formData['city']) ?>" maxlength="100" required><?= isset($errors['city']) ? '<div class="invalid-feedback d-block">' . e($errors['city']) . '</div>' : '' ?></div><div class="mb-4"><label class="form-label" for="additional_notes">Additional Notes</label><textarea class="form-control" id="additional_notes" name="additional_notes" rows="3" maxlength="1000"><?= e($formData['additional_notes']) ?></textarea><?= isset($errors['additional_notes']) ? '<div class="invalid-feedback d-block">' . e($errors['additional_notes']) . '</div>' : '' ?></div><div class="border p-3 mb-4"><strong>Payment method</strong><p class="text-secondary small mb-0 mt-1">Cash on Delivery</p></div><button class="btn btn-dark btn-lg w-100" type="submit">Place order</button></form></div><aside class="col-lg-5"><div class="cart-summary border p-4"><p class="eyebrow mb-2">Order summary</p><h2 class="h4 mb-4"><?= (int) $checkoutTotals['item_count'] ?> item<?= $checkoutTotals['item_count'] === 1 ? '' : 's' ?></h2><?php foreach ($checkoutItems as $item): ?><div class="d-flex justify-content-between gap-3 mb-3"><span class="small"><?= e($item['name']) ?><?php if (!empty($item['variant_name'])): ?><span class="d-block text-secondary">Color: <?= e($item['variant_name']) ?></span><?php endif; ?><span class="d-block text-secondary">Qty: <?= (int) $item['quantity'] ?></span></span><strong class="small"><?= e(formatPrice($item['subtotal'])) ?></strong></div><?php endforeach; ?><hr><div class="d-flex justify-content-between mb-2"><span class="text-secondary">Subtotal</span><strong><?= e(formatPrice($checkoutTotals['subtotal'])) ?></strong></div><div class="d-flex justify-content-between mb-3"><span class="text-secondary">Shipping</span><strong><?= $checkoutTotals['shipping'] > 0 ? e(formatPrice($checkoutTotals['shipping'])) : 'Free' ?></strong></div><div class="d-flex justify-content-between"><span>Total</span><strong><?= e(formatPrice($checkoutTotals['total'])) ?></strong></div></div></aside></div><?php endif; ?></div><?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
