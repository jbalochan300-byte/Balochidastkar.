<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$productId = filter_var($_GET['id'] ?? $_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$product = null;
$errors = [];
$databaseError = false;

if ($productId !== false && $productId !== null && $productId > 0) {
    try {
        $connection = getPdoConnection();
        $statement = $connection->prepare('SELECT id, name, sku FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch();
    } catch (Throwable $exception) {
        $databaseError = true;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && is_array($product)) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your confirmation session expired. Please try again.';
    } else {
        $deletedPaths = [];
        try {
            $connection = getPdoConnection();
            $connection->beginTransaction();
            $imageStatement = $connection->prepare('SELECT image_path FROM product_images WHERE product_id = :product_id');
            $imageStatement->execute(['product_id' => $productId]);
            $deletedPaths = array_filter($imageStatement->fetchAll(PDO::FETCH_COLUMN), 'is_string');

            $deleteStatement = $connection->prepare('DELETE FROM products WHERE id = :id');
            $deleteStatement->execute(['id' => $productId]);
            $connection->commit();

            $uploadDirectory = realpath(__DIR__ . '/../uploads/products');
            foreach ($deletedPaths as $deletedPath) {
                $candidate = realpath(__DIR__ . '/../' . ltrim($deletedPath, '/\\'));
                if ($candidate !== false && $uploadDirectory !== false && str_starts_with($candidate, $uploadDirectory . DIRECTORY_SEPARATOR) && is_file($candidate)) {
                    unlink($candidate);
                }
            }
            setFlashMessage('success', 'Product deleted successfully.');
            redirect(url('admin/products.php'));
        } catch (Throwable $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            $errors['form'] = 'The product could not be deleted. Please try again later.';
        }
    }
}

$pageTitle = 'Delete Product';
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5">
    <div class="mb-4"><p class="eyebrow mb-2">Catalog</p><h1 class="section-title mb-2">Delete product</h1><p class="text-secondary mb-0">This action permanently removes the product, variants, and image records.</p></div>
    <?php if ($databaseError): ?><div class="alert alert-warning" role="alert">The database is temporarily unavailable. Please try again later.</div><?php elseif (!is_array($product)): ?><div class="alert alert-danger" role="alert">The requested product could not be found.</div><?php else: ?>
        <?php if (isset($errors['form'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
        <div class="bg-white border rounded p-4" style="max-width: 620px;">
            <p class="text-secondary mb-2">You are about to permanently delete:</p>
            <h2 class="h4 mb-1"><?= e($product['name']) ?></h2>
            <p class="text-secondary mb-4">SKU: <?= e($product['sku']) ?></p>
            <form method="post" action="<?= e(url('admin/product-delete.php?id=' . (int) $productId)) ?>">
                <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= e(url('admin/products.php')) ?>">Cancel</a><button class="btn btn-danger" type="submit" onclick="return confirm('Delete this product and all its variants and images?');">Confirm deletion</button></div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
