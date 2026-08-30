<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$productId = filter_var($_GET['id'] ?? $_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$pageTitle = 'Edit Product';
$errors = [];
$databaseError = false;
$product = null;
$variantRows = [];
$images = [];
$allowedImageTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxImageSize = 5 * 1024 * 1024;
$uploadDirectory = __DIR__ . '/../uploads/products';
$uploadPathPrefix = 'uploads/products/';

if ($productId !== false && $productId !== null && $productId > 0) {
    try {
        $connection = getPdoConnection();
        $productStatement = $connection->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $productStatement->execute(['id' => $productId]);
        $product = $productStatement->fetch();

        if (is_array($product)) {
            $variantStatement = $connection->prepare(
                'SELECT id, variant_name, price, stock_quantity, is_active, image_path
                 FROM product_variants
                 WHERE product_id = :product_id
                 ORDER BY id ASC'
            );
            $variantStatement->execute(['product_id' => $productId]);
            $variantRows = $variantStatement->fetchAll();

            $imageStatement = $connection->prepare(
                'SELECT id, image_path, alt_text, sort_order
                 FROM product_images
                 WHERE product_id = :product_id
                 ORDER BY sort_order ASC, id ASC'
            );
            $imageStatement->execute(['product_id' => $productId]);
            $images = $imageStatement->fetchAll();
        }
    } catch (Throwable $exception) {
        $databaseError = true;
    }
}

if (!is_array($product)) {
    http_response_code($databaseError ? 503 : 404);
    $product = [
        'name' => '', 'sku' => '', 'category' => '', 'short_description' => '',
        'full_description' => '', 'description' => '', 'price' => '', 'sale_price' => null,
        'stock_quantity' => 0, 'is_featured' => 0, 'is_active' => 1,
    ];
    if (!$databaseError) {
        $errors['form'] = 'The requested product could not be found.';
    }
}

$formData = [
    'name' => (string) ($product['name'] ?? ''),
    'sku' => (string) ($product['sku'] ?? ''),
    'category' => (string) ($product['category'] ?? ''),
    'short_description' => (string) ($product['short_description'] ?? ''),
    'full_description' => (string) ($product['full_description'] ?? $product['description'] ?? ''),
    'price' => (string) ($product['price'] ?? ''),
    'sale_price' => $product['sale_price'] === null ? '' : (string) $product['sale_price'],
    'stock_quantity' => (string) ($product['stock_quantity'] ?? '0'),
    'is_featured' => (int) ($product['is_featured'] ?? 0) === 1 ? '1' : '0',
    'status' => (int) ($product['is_active'] ?? 1) === 1 ? 'active' : 'inactive',
];

if ($variantRows === []) {
    $variantRows = [[
        'id' => 0,
        'variant_name' => '',
        'price' => '0',
        'stock_quantity' => '0',
        'is_active' => 1,
        'image_path' => null,
    ]];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $productId > 0 && is_array($product)) {
    $formData = [
        'name' => (string) sanitizeInput($_POST['name'] ?? ''),
        'sku' => strtoupper((string) sanitizeInput($_POST['sku'] ?? '')),
        'category' => (string) sanitizeInput($_POST['category'] ?? ''),
        'short_description' => (string) sanitizeInput($_POST['short_description'] ?? ''),
        'full_description' => (string) sanitizeInput($_POST['full_description'] ?? ''),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'sale_price' => trim((string) ($_POST['sale_price'] ?? '')),
        'stock_quantity' => trim((string) ($_POST['stock_quantity'] ?? '')),
        'is_featured' => isset($_POST['is_featured']) ? '1' : '0',
        'status' => (string) sanitizeInput($_POST['status'] ?? ''),
    ];

    $inspectImage = static function (array $file) use ($allowedImageTypes, $maxImageSize): array|string|null {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
            return 'The uploaded image is invalid.';
        }
        if ((int) $file['size'] > $maxImageSize) {
            return 'Each image must be 5 MB or smaller.';
        }
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo === false ? false : finfo_file($fileInfo, $file['tmp_name']);
        if ($fileInfo !== false) {
            finfo_close($fileInfo);
        }
        if (!is_string($mimeType) || !isset($allowedImageTypes[$mimeType]) || @getimagesize($file['tmp_name']) === false) {
            return 'Only valid JPG, JPEG, PNG, and WEBP images are allowed.';
        }
        return ['tmp_name' => $file['tmp_name'], 'extension' => $allowedImageTypes[$mimeType]];
    };

    $variantRows = [];
    $validVariants = [];
    $variantNames = [];
    $submittedVariants = is_array($_POST['variants'] ?? null) ? $_POST['variants'] : [];
    $variantFiles = $_FILES['variants'] ?? null;
    foreach ($submittedVariants as $submittedVariant) {
        if (!is_array($submittedVariant)) {
            continue;
        }
        $variantRow = [
            'id' => (int) ($submittedVariant['id'] ?? 0),
            'variant_name' => (string) sanitizeInput($submittedVariant['name'] ?? ''),
            'price' => trim((string) ($submittedVariant['additional_price'] ?? '')),
            'stock_quantity' => trim((string) ($submittedVariant['stock_quantity'] ?? '')),
            'is_active' => (string) sanitizeInput($submittedVariant['status'] ?? ''),
            'existing_image' => is_string($submittedVariant['existing_image'] ?? null) ? $submittedVariant['existing_image'] : null,
            'remove_image' => !empty($submittedVariant['remove_image']),
        ];
        $variantRows[] = $variantRow;
        $rowNumber = count($variantRows) - 1;
        $isBlank = $variantRow['variant_name'] === ''
            && in_array($variantRow['price'], ['', '0'], true)
            && in_array($variantRow['stock_quantity'], ['', '0'], true);
        if ($isBlank) {
            continue;
        }

        $nameKey = strtolower($variantRow['variant_name']);
        if ($variantRow['variant_name'] === '' || mb_strlen($variantRow['variant_name']) > 100) {
            $errors['variant_' . $rowNumber . '_name'] = 'Color name is required and must not exceed 100 characters.';
        } elseif (in_array($nameKey, $variantNames, true)) {
            $errors['variant_' . $rowNumber . '_name'] = 'Each color can only be added once.';
        }
        if ($variantRow['price'] === '') {
            $variantRow['price'] = '0';
            $variantRows[$rowNumber]['price'] = '0';
        } elseif (!is_numeric($variantRow['price']) || (float) $variantRow['price'] < 0) {
            $errors['variant_' . $rowNumber . '_price'] = 'Additional price must be a non-negative number.';
        }
        if (!preg_match('/^\d+$/', $variantRow['stock_quantity'])) {
            $errors['variant_' . $rowNumber . '_stock'] = 'Color stock must be a whole number of zero or more.';
        }
        if (!in_array($variantRow['is_active'], ['active', 'inactive'], true)) {
            $errors['variant_' . $rowNumber . '_status'] = 'Color status is invalid.';
        }
        if ($nameKey !== '') {
            $variantNames[] = $nameKey;
        }

        $variantImageFile = null;
        if (isset($variantFiles['name'][$rowNumber]['image'])) {
            $rawVariantImage = [
                'name' => $variantFiles['name'][$rowNumber]['image'],
                'type' => $variantFiles['type'][$rowNumber]['image'] ?? '',
                'tmp_name' => $variantFiles['tmp_name'][$rowNumber]['image'] ?? '',
                'error' => $variantFiles['error'][$rowNumber]['image'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $variantFiles['size'][$rowNumber]['image'] ?? 0,
            ];
            $variantImageResult = $inspectImage($rawVariantImage);
            if (is_string($variantImageResult)) {
                $errors['variant_' . $rowNumber . '_image'] = $variantImageResult;
            } elseif (is_array($variantImageResult)) {
                $variantImageFile = $variantImageResult;
            }
        }

        if (!isset($errors['variant_' . $rowNumber . '_name'])
            && !isset($errors['variant_' . $rowNumber . '_price'])
            && !isset($errors['variant_' . $rowNumber . '_stock'])
            && !isset($errors['variant_' . $rowNumber . '_status'])
            && !isset($errors['variant_' . $rowNumber . '_image'])) {
            $variantRow['image_file'] = $variantImageFile;
            $validVariants[] = $variantRow;
        }
    }
    if ($variantRows === []) {
        $variantRows = [['id' => 0, 'variant_name' => '', 'price' => '0', 'stock_quantity' => '0', 'is_active' => 'active']];
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['csrf_token'] = 'Your form session expired. Please try again.';
    }
    $errors = array_merge($errors, validateInput($formData, [
        'name' => ['label' => 'Product name', 'required' => true, 'max' => 150],
        'sku' => ['label' => 'SKU', 'required' => true, 'max' => 100],
        'category' => ['label' => 'Category', 'required' => true, 'max' => 100],
        'short_description' => ['label' => 'Short description', 'required' => true, 'max' => 255],
        'full_description' => ['label' => 'Full description', 'required' => true, 'min' => 20],
        'status' => ['label' => 'Status', 'required' => true, 'in' => ['active', 'inactive']],
    ]));
    if ($formData['sku'] !== '' && !preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $formData['sku'])) {
        $errors['sku'] = 'SKU may contain only letters, numbers, dots, underscores, and hyphens.';
    }
    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] < 0) {
        $errors['price'] = 'Regular price must be a non-negative number.';
    }
    if ($formData['sale_price'] !== '' && (!is_numeric($formData['sale_price']) || (float) $formData['sale_price'] < 0 || (isset($errors['price']) === false && (float) $formData['sale_price'] >= (float) $formData['price']))) {
        $errors['sale_price'] = 'Sale price must be lower than the regular price.';
    }
    if (!preg_match('/^\d+$/', $formData['stock_quantity'])) {
        $errors['stock_quantity'] = 'Stock quantity must be a whole number of zero or more.';
    }

    $deleteIds = [];
    foreach ((array) ($_POST['delete_image_ids'] ?? []) as $deleteId) {
        $deleteId = filter_var($deleteId, FILTER_VALIDATE_INT);
        if ($deleteId !== false && $deleteId > 0) {
            $deleteIds[] = (int) $deleteId;
        }
    }
    $primaryImageId = filter_var($_POST['primary_image_id'] ?? null, FILTER_VALIDATE_INT);
    $primaryImageId = $primaryImageId === false ? 0 : (int) $primaryImageId;
    $pendingImages = [];
    $mainImage = $_FILES['main_image'] ?? null;
    if (is_array($mainImage)) {
        $result = $inspectImage($mainImage);
        if (is_string($result)) {
            $errors['main_image'] = $result;
        } elseif (is_array($result)) {
            $pendingImages[] = ['file' => $result, 'is_new_primary' => true];
        }
    }
    $galleryImages = $_FILES['gallery_images'] ?? null;
    if (is_array($galleryImages) && is_array($galleryImages['name'] ?? null)) {
        foreach ($galleryImages['name'] as $index => $unusedName) {
            $file = ['tmp_name' => $galleryImages['tmp_name'][$index] ?? '', 'error' => $galleryImages['error'][$index] ?? UPLOAD_ERR_NO_FILE, 'size' => $galleryImages['size'][$index] ?? 0];
            $result = $inspectImage($file);
            if (is_string($result)) {
                $errors['gallery_images'] = $result;
                break;
            }
            if (is_array($result)) {
                $pendingImages[] = ['file' => $result, 'is_new_primary' => false];
            }
        }
    }

    if ($errors === []) {
        $movedFiles = [];
        $deletedPaths = [];
        try {
            $connection = getPdoConnection();
            $connection->beginTransaction();
            $statement = $connection->prepare(
                'UPDATE products SET name = :name, slug = :slug, description = :description,
                 short_description = :short_description, full_description = :full_description,
                 category = :category, price = :price, sale_price = :sale_price,
                 sku = :sku, stock_quantity = :stock_quantity, is_featured = :is_featured,
                 is_active = :is_active WHERE id = :id'
            );
            $statement->execute([
                'name' => $formData['name'], 'slug' => generateSlug($formData['name']) . '-' . strtolower($formData['sku']),
                'description' => $formData['full_description'], 'short_description' => $formData['short_description'],
                'full_description' => $formData['full_description'], 'category' => $formData['category'],
                'price' => number_format((float) $formData['price'], 2, '.', ''),
                'sale_price' => $formData['sale_price'] === '' ? null : number_format((float) $formData['sale_price'], 2, '.', ''),
                'sku' => $formData['sku'], 'stock_quantity' => (int) $formData['stock_quantity'],
                'is_featured' => (int) $formData['is_featured'], 'is_active' => $formData['status'] === 'active' ? 1 : 0, 'id' => $productId,
            ]);

            $existingIds = array_map('intval', array_column($images, 'id'));
            $submittedIds = array_filter(array_map(static fn (array $variant): int => (int) ($variant['id'] ?? 0), $validVariants));
            $existingVariantStatement = $connection->prepare('SELECT id, image_path FROM product_variants WHERE product_id = :product_id');
            $existingVariantStatement->execute(['product_id' => $productId]);
            foreach ($existingVariantStatement->fetchAll() as $existingVariantRow) {
                if (!in_array((int) $existingVariantRow['id'], $submittedIds, true)) {
                    if (is_string($existingVariantRow['image_path']) && $existingVariantRow['image_path'] !== '') {
                        $deletedPaths[] = $existingVariantRow['image_path'];
                    }
                    $deleteVariant = $connection->prepare('DELETE FROM product_variants WHERE id = :id AND product_id = :product_id');
                    $deleteVariant->execute(['id' => $existingVariantRow['id'], 'product_id' => $productId]);
                }
            }
            $updateVariant = $connection->prepare('UPDATE product_variants SET variant_name = :variant_name, sku = :sku, price = :price, stock_quantity = :stock_quantity, is_active = :is_active, image_path = :image_path WHERE id = :id AND product_id = :product_id');
            $insertVariant = $connection->prepare('INSERT INTO product_variants (product_id, variant_name, sku, price, stock_quantity, is_active, image_path) VALUES (:product_id, :variant_name, :sku, :price, :stock_quantity, :is_active, :image_path)');
            foreach ($validVariants as $variant) {
                $variantImagePath = is_string($variant['existing_image']) && $variant['existing_image'] !== '' ? $variant['existing_image'] : null;

                if ($variant['image_file'] !== null) {
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        throw new RuntimeException('Upload directory is unavailable.');
                    }
                    if (!is_writable($uploadDirectory)) {
                        throw new RuntimeException('Upload directory is not writable.');
                    }
                    $variantFilename = 'variant_' . bin2hex(random_bytes(16)) . '.' . $variant['image_file']['extension'];
                    $variantDestination = $uploadDirectory . DIRECTORY_SEPARATOR . $variantFilename;
                    if (!move_uploaded_file($variant['image_file']['tmp_name'], $variantDestination)) {
                        throw new RuntimeException('Color photo could not be stored.');
                    }
                    $movedFiles[] = $variantDestination;
                    if ($variantImagePath !== null) {
                        $deletedPaths[] = $variantImagePath;
                    }
                    $variantImagePath = $uploadPathPrefix . $variantFilename;
                } elseif ($variant['remove_image'] && $variantImagePath !== null) {
                    $deletedPaths[] = $variantImagePath;
                    $variantImagePath = null;
                }

                $variantSku = substr($formData['sku'] . '-' . strtoupper(generateSlug($variant['variant_name'])), 0, 100);
                $parameters = ['product_id' => $productId, 'variant_name' => $variant['variant_name'], 'sku' => $variantSku, 'price' => number_format((float) $variant['price'], 2, '.', ''), 'stock_quantity' => (int) $variant['stock_quantity'], 'is_active' => $variant['is_active'] === 'active' ? 1 : 0, 'image_path' => $variantImagePath];
                if ((int) $variant['id'] > 0) {
                    $updateVariant->execute($parameters + ['id' => (int) $variant['id']]);
                } else {
                    $insertVariant->execute($parameters);
                }
            }

            $deleteIds = array_values(array_intersect($deleteIds, $existingIds));
            if ($deleteIds !== []) {
                $getDeleted = $connection->prepare('SELECT image_path FROM product_images WHERE product_id = :product_id AND id = :id');
                $deleteImage = $connection->prepare('DELETE FROM product_images WHERE product_id = :product_id AND id = :id');
                foreach ($deleteIds as $deleteId) {
                    $getDeleted->execute(['product_id' => $productId, 'id' => $deleteId]);
                    $deletedPath = $getDeleted->fetchColumn();
                    if (is_string($deletedPath)) {
                        $deletedPaths[] = $deletedPath;
                    }
                    $deleteImage->execute(['product_id' => $productId, 'id' => $deleteId]);
                }
            }

            if ($pendingImages !== []) {
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException('Upload directory is unavailable.');
                }
                if (!is_writable($uploadDirectory)) {
                    throw new RuntimeException('Upload directory is not writable.');
                }
                $imageInsert = $connection->prepare('INSERT INTO product_images (product_id, image_path, alt_text, sort_order) VALUES (:product_id, :image_path, :alt_text, :sort_order)');
                foreach ($pendingImages as $pendingImage) {
                    $filename = 'product_' . bin2hex(random_bytes(16)) . '.' . $pendingImage['file']['extension'];
                    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                    if (!move_uploaded_file($pendingImage['file']['tmp_name'], $destination)) {
                        throw new RuntimeException('Image could not be stored.');
                    }
                    $movedFiles[] = $destination;
                    $imageInsert->execute(['product_id' => $productId, 'image_path' => $uploadPathPrefix . $filename, 'alt_text' => $formData['name'], 'sort_order' => 999]);
                    if ($pendingImage['is_new_primary']) {
                        $primaryImageId = (int) $connection->lastInsertId();
                    }
                }
            }

            $remainingImageStatement = $connection->prepare('SELECT id FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
            $remainingImageStatement->execute(['product_id' => $productId]);
            $remainingIds = array_map('intval', $remainingImageStatement->fetchAll(PDO::FETCH_COLUMN));
            $remainingIds = array_values(array_diff($remainingIds, $deleteIds));
            if ($remainingIds !== []) {
                if (!in_array($primaryImageId, $remainingIds, true)) {
                    $primaryImageId = $remainingIds[0];
                }
                $sortImage = $connection->prepare('UPDATE product_images SET sort_order = :sort_order WHERE id = :id AND product_id = :product_id');
                foreach ($remainingIds as $sortOrder => $remainingId) {
                    $sortImage->execute(['sort_order' => $remainingId === $primaryImageId ? 0 : $sortOrder + 1, 'id' => $remainingId, 'product_id' => $productId]);
                }
            }
            $connection->commit();
            $safeUploadDirectory = realpath($uploadDirectory);
            foreach ($deletedPaths as $deletedPath) {
                $candidate = realpath(__DIR__ . '/../' . ltrim($deletedPath, '/\\'));
                if ($candidate !== false && $safeUploadDirectory !== false && str_starts_with($candidate, $safeUploadDirectory . DIRECTORY_SEPARATOR) && is_file($candidate)) {
                    unlink($candidate);
                }
            }
            setFlashMessage('success', 'Product updated successfully.');
            redirect(url('admin/products.php'));
        } catch (PDOException $exception) {
            if (isset($connection) && $connection->inTransaction()) { $connection->rollBack(); }
            foreach ($movedFiles as $movedFile) { if (is_file($movedFile)) { unlink($movedFile); } }
            $errors['form'] = $exception->getCode() === '23000' ? 'The SKU or a color variant SKU already exists.' : 'The product could not be saved. Please try again later.';
        } catch (RuntimeException $exception) {
            if (isset($connection) && $connection->inTransaction()) { $connection->rollBack(); }
            foreach ($movedFiles as $movedFile) { if (is_file($movedFile)) { unlink($movedFile); } }
            $databaseError = true;
        }
    }
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
$fieldError = static fn (string $field): string => isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>' : '';
$variantError = static fn (int $index, string $field): string => isset($errors['variant_' . $index . '_' . $field]) ? '<div class="invalid-feedback d-block">' . e($errors['variant_' . $index . '_' . $field]) . '</div>' : '';
?>
<div class="container-fluid p-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div><p class="eyebrow mb-2">Catalog</p><h1 class="section-title mb-2">Edit product</h1><p class="text-secondary mb-0">Update product details, colors, and images.</p></div>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/products.php')) ?>">Back to products</a>
    </div>
    <?php if ($databaseError): ?><div class="alert alert-warning" role="alert">The database is temporarily unavailable. Please try again later.</div><?php endif; ?>
    <?php if (isset($errors['form'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div><?php endif; ?>
    <?php if (isset($errors['csrf_token'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['csrf_token']) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('admin/product-edit.php?id=' . (int) $productId)) ?>" class="bg-white border rounded p-4" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="product_id" value="<?= (int) $productId ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="row g-4">
            <div class="col-lg-8">
                <h2 class="h5 mb-3">Product information</h2>
                <div class="mb-3"><label class="form-label" for="name">Product name</label><input class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" type="text" id="name" name="name" maxlength="150" value="<?= e($formData['name']) ?>" required><?= $fieldError('name') ?></div>
                <div class="row g-3"><div class="col-md-6"><label class="form-label" for="sku">SKU</label><input class="form-control<?= isset($errors['sku']) ? ' is-invalid' : '' ?>" type="text" id="sku" name="sku" maxlength="100" value="<?= e($formData['sku']) ?>" required><?= $fieldError('sku') ?></div><div class="col-md-6"><label class="form-label" for="category">Category</label><input class="form-control<?= isset($errors['category']) ? ' is-invalid' : '' ?>" type="text" id="category" name="category" maxlength="100" value="<?= e($formData['category']) ?>" required><?= $fieldError('category') ?></div></div>
                <div class="mt-3 mb-3"><label class="form-label" for="short_description">Short description</label><textarea class="form-control<?= isset($errors['short_description']) ? ' is-invalid' : '' ?>" id="short_description" name="short_description" rows="3" maxlength="255" required><?= e($formData['short_description']) ?></textarea><?= $fieldError('short_description') ?></div>
                <div class="mb-3"><label class="form-label" for="full_description">Full description</label><textarea class="form-control<?= isset($errors['full_description']) ? ' is-invalid' : '' ?>" id="full_description" name="full_description" rows="6" required><?= e($formData['full_description']) ?></textarea><?= $fieldError('full_description') ?></div>
                <div class="mb-3"><label class="form-label" for="main_image">Add new primary image</label><input class="form-control<?= isset($errors['main_image']) ? ' is-invalid' : '' ?>" type="file" id="main_image" name="main_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">JPG, JPEG, PNG, or WEBP. Maximum 5 MB. This becomes the primary image.</div><?= $fieldError('main_image') ?></div>
                <div class="mb-3"><label class="form-label" for="gallery_images">Add gallery images</label><input class="form-control<?= isset($errors['gallery_images']) ? ' is-invalid' : '' ?>" type="file" id="gallery_images" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple><div class="form-text">Select multiple files. Maximum 5 MB each.</div><?= $fieldError('gallery_images') ?></div>
            </div>
            <div class="col-lg-4"><h2 class="h5 mb-3">Pricing and inventory</h2><div class="mb-3"><label class="form-label" for="price">Regular price (PKR)</label><input class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>" type="number" id="price" name="price" min="0" step="0.01" value="<?= e($formData['price']) ?>" required><?= $fieldError('price') ?></div><div class="mb-3"><label class="form-label" for="sale_price">Sale price (PKR)</label><input class="form-control<?= isset($errors['sale_price']) ? ' is-invalid' : '' ?>" type="number" id="sale_price" name="sale_price" min="0" step="0.01" value="<?= e($formData['sale_price']) ?>"><?= $fieldError('sale_price') ?></div><div class="mb-3"><label class="form-label" for="stock_quantity">Stock quantity</label><input class="form-control<?= isset($errors['stock_quantity']) ? ' is-invalid' : '' ?>" type="number" id="stock_quantity" name="stock_quantity" min="0" step="1" value="<?= e($formData['stock_quantity']) ?>" required><?= $fieldError('stock_quantity') ?></div><div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select<?= isset($errors['status']) ? ' is-invalid' : '' ?>" id="status" name="status" required><option value="active"<?= $formData['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $formData['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option></select><?= $fieldError('status') ?></div><div class="form-check mt-4"><input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"<?= $formData['is_featured'] === '1' ? ' checked' : '' ?>><label class="form-check-label" for="is_featured">Feature this product</label></div></div>
        </div>
        <hr class="my-4">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1">Colors and variants</h2><p class="text-secondary small mb-0">Update colors, price adjustments, stock, and status.</p></div><button class="btn btn-outline-dark btn-sm" type="button" id="addVariant">+ Add color</button></div>
        <div id="variantRows">
            <?php foreach ($variantRows as $variantIndex => $variantRow): ?>
                <?php $variantImagePath = $variantRow['image_path'] ?? $variantRow['existing_image'] ?? null; ?>
                <div class="variant-row border rounded p-3 mb-3"><input type="hidden" data-variant-field="id" name="variants[<?= $variantIndex ?>][id]" value="<?= (int) ($variantRow['id'] ?? 0) ?>"><input type="hidden" class="variant-existing-image" data-variant-field="existing_image" name="variants[<?= $variantIndex ?>][existing_image]" value="<?= e((string) $variantImagePath) ?>"><div class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label" for="variant_name_<?= $variantIndex ?>">Color name</label><input class="form-control" type="text" id="variant_name_<?= $variantIndex ?>" data-variant-field="name" name="variants[<?= $variantIndex ?>][name]" maxlength="100" value="<?= e($variantRow['variant_name'] ?? '') ?>"><?= $variantError($variantIndex, 'name') ?></div><div class="col-md-3"><label class="form-label" for="variant_price_<?= $variantIndex ?>">Additional price (PKR)</label><input class="form-control" type="number" id="variant_price_<?= $variantIndex ?>" data-variant-field="additional_price" name="variants[<?= $variantIndex ?>][additional_price]" min="0" step="0.01" value="<?= e((string) ($variantRow['price'] ?? '0')) ?>"><?= $variantError($variantIndex, 'price') ?></div><div class="col-md-2"><label class="form-label" for="variant_stock_<?= $variantIndex ?>">Stock</label><input class="form-control" type="number" id="variant_stock_<?= $variantIndex ?>" data-variant-field="stock_quantity" name="variants[<?= $variantIndex ?>][stock_quantity]" min="0" step="1" value="<?= e((string) ($variantRow['stock_quantity'] ?? '0')) ?>"><?= $variantError($variantIndex, 'stock') ?></div><div class="col-md-2"><label class="form-label" for="variant_status_<?= $variantIndex ?>">Status</label><select class="form-select" id="variant_status_<?= $variantIndex ?>" data-variant-field="status" name="variants[<?= $variantIndex ?>][status]"><option value="active"<?= (int) ($variantRow['is_active'] ?? 1) === 1 || ($variantRow['is_active'] ?? '') === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= (int) ($variantRow['is_active'] ?? 1) === 0 || ($variantRow['is_active'] ?? '') === 'inactive' ? ' selected' : '' ?>>Inactive</option></select><?= $variantError($variantIndex, 'status') ?></div><div class="col-md-1 text-md-end"><button class="btn btn-outline-danger btn-sm remove-variant<?= count($variantRows) === 1 ? ' d-none' : '' ?>" type="button">Remove</button></div>
                    <?php if (!empty($variantImagePath)): ?>
                    <div class="col-md-3 variant-image-preview"><label class="form-label d-block">Current photo</label><img src="<?= e(url((string) $variantImagePath)) ?>" alt="<?= e($variantRow['variant_name'] ?? 'Color photo') ?>" class="rounded border object-fit-cover" style="width: 100%; max-width: 140px; height: 100px;"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="variant_remove_image_<?= $variantIndex ?>" data-variant-field="remove_image" name="variants[<?= $variantIndex ?>][remove_image]" value="1"><label class="form-check-label small text-danger" for="variant_remove_image_<?= $variantIndex ?>">Remove photo</label></div></div>
                    <?php endif; ?>
                    <div class="col-md-<?= !empty($variantImagePath) ? '6' : '9' ?>"><label class="form-label" for="variant_image_<?= $variantIndex ?>"><?= !empty($variantImagePath) ? 'Replace photo' : 'Color photo (optional)' ?></label><input class="form-control<?= $variantError($variantIndex, 'image') !== '' ? ' is-invalid' : '' ?>" type="file" id="variant_image_<?= $variantIndex ?>" data-variant-field="image" name="variants[<?= $variantIndex ?>][image]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">Shown to customers when they pick this color. JPG, PNG, or WEBP, max 5 MB.</div><?= $variantError($variantIndex, 'image') ?></div>
                </div></div>
            <?php endforeach; ?>
        </div>
        <?php if ($images !== []): ?><hr class="my-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Product images</h2><p class="text-secondary small mb-0">Choose a primary image or mark images for removal.</p></div></div><div class="row g-3"><?php foreach ($images as $image): ?><div class="col-sm-6 col-lg-3"><div class="border rounded p-2 h-100"><img src="<?= e(url($image['image_path'])) ?>" alt="<?= e($image['alt_text'] ?: $formData['name']) ?>" class="w-100 rounded object-fit-cover" style="height: 150px;"><div class="form-check mt-2"><input class="form-check-input" type="radio" name="primary_image_id" id="primary_image_<?= (int) $image['id'] ?>" value="<?= (int) $image['id'] ?>"<?= (int) $image['sort_order'] === 0 ? ' checked' : '' ?>><label class="form-check-label" for="primary_image_<?= (int) $image['id'] ?>">Primary image</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="delete_image_ids[]" id="delete_image_<?= (int) $image['id'] ?>" value="<?= (int) $image['id'] ?>"><label class="form-check-label text-danger" for="delete_image_<?= (int) $image['id'] ?>">Delete image</label></div></div></div><?php endforeach; ?></div><?php endif; ?>
        <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top"><a class="btn btn-outline-secondary" href="<?= e(url('admin/products.php')) ?>">Cancel</a><button class="btn btn-dark" type="submit">Save changes</button></div>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
