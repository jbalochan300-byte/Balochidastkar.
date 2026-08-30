<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/cart-functions.php';

$productId = filter_var($_GET['id'] ?? $_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$product = null;
$variants = [];
$images = [];
$errors = [];
$databaseError = false;

if ($productId !== false && $productId !== null && $productId > 0) {
    try {
        $connection = getPdoConnection();
        $productStatement = $connection->prepare('SELECT id, name, description, full_description, price, sale_price, sku, stock_quantity, colors FROM products WHERE id = :id AND is_active = 1 LIMIT 1');
        $productStatement->execute(['id' => $productId]);
        $product = $productStatement->fetch();
        if (is_array($product)) {
            $variantStatement = $connection->prepare('SELECT id, variant_name, price, stock_quantity, image_path FROM product_variants WHERE product_id = :product_id AND is_active = 1 ORDER BY id ASC');
            $variantStatement->execute(['product_id' => $productId]);
            $variants = $variantStatement->fetchAll();
            $imageStatement = $connection->prepare('SELECT image_path, alt_text, sort_order FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
            $imageStatement->execute(['product_id' => $productId]);
            $images = $imageStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        $databaseError = true;
    }
}

if (is_array($product) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    }
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $quantity = $quantity === false ? 0 : $quantity;
    $selectedColor = sanitizeInput($_POST['color'] ?? null);
    $selectedColor = is_string($selectedColor) && $selectedColor !== '' ? $selectedColor : null;
    if ($quantity < 1) {
        $errors['quantity'] = 'Please choose a valid quantity.';
    }
    if ($variants !== [] && $selectedColor === null) {
        $errors['color'] = 'Please choose a color.';
    }
    if ($errors === []) {
        try {
            if (!addProductToCart((int) $productId, $quantity, $selectedColor)) {
                $errors['form'] = 'The selected quantity or color is not available.';
            } else {
                setFlashMessage('success', 'Product added to your cart.');
                redirect(url('cart.php'));
            }
        } catch (Throwable $exception) {
            $errors['form'] = 'The product could not be added right now. Please try again later.';
        }
    }
}

if (!is_array($product)) {
    http_response_code($databaseError ? 503 : 404);
}

$regularPrice = is_array($product) ? (float) $product['price'] : 0.00;
$salePrice = is_array($product) && $product['sale_price'] !== null && (float) $product['sale_price'] > 0 && (float) $product['sale_price'] < $regularPrice ? (float) $product['sale_price'] : null;
$basePrice = $salePrice ?? $regularPrice;
$colorOptions = $variants;
if ($colorOptions === [] && is_array($product) && !empty($product['colors'])) {
    foreach (explode(',', (string) $product['colors']) as $color) {
        $color = trim($color);
        if ($color !== '') {
            $colorOptions[] = ['variant_name' => $color, 'price' => 0, 'stock_quantity' => (int) $product['stock_quantity'], 'image_path' => null];
        }
    }
}

$pageTitle = 'Product';
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($databaseError): ?>
    <section class="container py-5"><div class="alert alert-warning">This product is temporarily unavailable. Please try again later.</div></section>
<?php elseif (!is_array($product)): ?>
    <section class="container py-5"><p class="eyebrow">Collection</p><h1 class="section-title">Product not found</h1><p class="text-secondary">This piece may have moved on.</p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Return to shop</a></section>
<?php else: ?>
    <div class="container py-5">
        <?php if (isset($errors['form'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
        <div class="row g-5 align-items-start">
            <div class="col-lg-7">
                <div class="product-gallery-main mb-3">
                    <?php if (!empty($images[0]['image_path'])): ?><button class="gallery-lightbox-trigger" id="productGalleryTrigger" type="button" data-bs-toggle="modal" data-bs-target="#productLightbox" data-image="<?= e(url($images[0]['image_path'])) ?>"><img id="productMainImage" src="<?= e(url($images[0]['image_path'])) ?>" alt="<?= e($images[0]['alt_text'] ?: $product['name']) ?>"></button><?php else: ?><div class="product-image-placeholder"><?= e(APP_NAME) ?></div><?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?><div class="row g-2"><?php foreach ($images as $image): ?><div class="col-3 col-sm-2"><button class="gallery-thumb" type="button" data-bs-toggle="modal" data-bs-target="#productLightbox" data-image="<?= e(url($image['image_path'])) ?>"><img src="<?= e(url($image['image_path'])) ?>" alt="<?= e($image['alt_text'] ?: $product['name']) ?>"></button></div><?php endforeach; ?></div><?php endif; ?>
            </div>
            <div class="col-lg-5">
                <p class="eyebrow mb-2"><?= e(APP_NAME) ?></p><h1 class="section-title mb-3"><?= e($product['name']) ?></h1><p class="text-secondary small mb-4">SKU: <?= e($product['sku']) ?></p>
                <div class="product-detail-price mb-3"><strong id="productPrice" data-base-price="<?= e((string) $basePrice) ?>"><?= e(formatPrice($basePrice)) ?></strong><?php if ($salePrice !== null): ?><del id="productRegularPrice" class="text-secondary ms-2" data-regular-price="<?= e((string) $regularPrice) ?>"><?= e(formatPrice($regularPrice)) ?></del><?php endif; ?></div>
                <p class="product-detail-description mb-4"><?= e($product['full_description'] ?: $product['description']) ?></p>
                <form method="post" action="<?= e(url('product.php?id=' . (int) $product['id'])) ?>" id="productForm"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                    <?php if ($colorOptions !== []): ?><fieldset class="mb-4"><legend class="form-label">Color</legend><div class="d-flex flex-wrap gap-2"><?php foreach ($colorOptions as $colorIndex => $color): ?><input class="btn-check product-color-option" type="radio" name="color" id="color_<?= $colorIndex ?>" value="<?= e($color['variant_name']) ?>" data-additional-price="<?= e((string) ($color['price'] ?? 0)) ?>" data-stock="<?= (int) $color['stock_quantity'] ?>" data-image="<?= e(!empty($color['image_path']) ? url($color['image_path']) : '') ?>"<?= $colorIndex === 0 ? ' checked' : '' ?>><label class="btn btn-outline-dark" for="color_<?= $colorIndex ?>"><?= e($color['variant_name']) ?></label><?php endforeach; ?></div><?php if (isset($errors['color'])): ?><div class="invalid-feedback d-block"><?= e($errors['color']) ?></div><?php endif; ?></fieldset><?php endif; ?>
                    <p class="small mb-3" id="productStock" data-base-stock="<?= (int) $product['stock_quantity'] ?>"><?= (int) $product['stock_quantity'] > 0 ? 'In stock' : 'Out of stock' ?></p>
                    <div class="d-flex align-items-end gap-3 mb-4"><div><label class="form-label" for="quantity">Quantity</label><input class="form-control<?= isset($errors['quantity']) ? ' is-invalid' : '' ?>" type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (int) $product['stock_quantity'] ?>" required><?= isset($errors['quantity']) ? '<div class="invalid-feedback d-block">' . e($errors['quantity']) . '</div>' : '' ?></div><div class="d-flex gap-2"><button class="btn btn-dark btn-lg" type="submit" name="cart_action" value="cart">Add to Cart</button><button class="btn btn-outline-dark btn-lg" type="submit" name="cart_action" value="buy">Buy Now</button></div></div>
                </form>
            </div>
        </div>
        <section class="product-description-section border-top mt-5 pt-5"><p class="eyebrow">The details</p><h2 class="section-title h2">Made to be remembered.</h2><p class="text-secondary detail-copy"><?= e($product['full_description'] ?: $product['description']) ?></p></section>
    </div>
    <?php if ($images !== []): ?><div class="modal fade" id="productLightbox" tabindex="-1" aria-labelledby="productLightboxLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content bg-dark"><div class="modal-header border-0"><h2 class="modal-title visually-hidden" id="productLightboxLabel"><?= e($product['name']) ?> gallery</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close image"></button></div><div class="modal-body text-center"><img id="lightboxImage" class="img-fluid" src="<?= e(url($images[0]['image_path'])) ?>" alt="<?= e($product['name']) ?>"></div></div></div></div><?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
