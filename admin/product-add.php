<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Add Product';
$formData = [
    'name' => '',
    'sku' => '',
    'category' => '',
    'short_description' => '',
    'full_description' => '',
    'price' => '',
    'sale_price' => '',
    'stock_quantity' => '0',
    'is_featured' => '0',
    'status' => 'active',
];
$errors = [];
$databaseError = false;
$variantRows = [[
    'name' => '',
    'additional_price' => '0',
    'stock_quantity' => '0',
    'status' => 'active',
]];
$validVariants = [];
$allowedImageTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxImageSize = 5 * 1024 * 1024;
$uploadDirectory = __DIR__ . '/../uploads/products';
$uploadPathPrefix = 'uploads/products/';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
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

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return 'The image upload could not be completed.';
        }

        if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
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

        return [
            'tmp_name' => $file['tmp_name'],
            'extension' => $allowedImageTypes[$mimeType],
        ];
    };

    $variantRows = [];
    $variantNames = [];
    $submittedVariants = is_array($_POST['variants'] ?? null) ? $_POST['variants'] : [];
    $variantFiles = $_FILES['variants'] ?? null;
    foreach ($submittedVariants as $variantIndex => $submittedVariant) {
        if (!is_array($submittedVariant)) {
            continue;
        }

        $variantRow = [
            'name' => (string) sanitizeInput($submittedVariant['name'] ?? ''),
            'additional_price' => trim((string) ($submittedVariant['additional_price'] ?? '')),
            'stock_quantity' => trim((string) ($submittedVariant['stock_quantity'] ?? '')),
            'status' => (string) sanitizeInput($submittedVariant['status'] ?? ''),
        ];
        $variantRows[] = $variantRow;
        $rowNumber = count($variantRows) - 1;

        if ($variantRow['name'] === ''
            && in_array($variantRow['additional_price'], ['', '0'], true)
            && in_array($variantRow['stock_quantity'], ['', '0'], true)) {
            continue;
        }

        if ($variantRow['name'] === '' || mb_strlen($variantRow['name']) > 100) {
            $errors['variant_' . $rowNumber . '_name'] = 'Color name is required and must not exceed 100 characters.';
        }
        if ($variantRow['additional_price'] === '') {
            $variantRow['additional_price'] = '0';
            $variantRows[$rowNumber]['additional_price'] = '0';
        } elseif (!is_numeric($variantRow['additional_price']) || (float) $variantRow['additional_price'] < 0) {
            $errors['variant_' . $rowNumber . '_additional_price'] = 'Additional price must be a non-negative number.';
        }
        if (!preg_match('/^\d+$/', $variantRow['stock_quantity'])) {
            $errors['variant_' . $rowNumber . '_stock_quantity'] = 'Color stock must be a whole number of zero or more.';
        }
        if (!in_array($variantRow['status'], ['active', 'inactive'], true)) {
            $errors['variant_' . $rowNumber . '_status'] = 'Color status is invalid.';
        }

        $normalizedName = strtolower($variantRow['name']);
        if ($normalizedName !== '' && in_array($normalizedName, $variantNames, true)) {
            $errors['variant_' . $rowNumber . '_name'] = 'Each color can only be added once.';
        }
        if ($normalizedName !== '') {
            $variantNames[] = $normalizedName;
        }

        $variantImageFile = null;
        if (isset($variantFiles['name'][$variantIndex]['image'])) {
            $rawVariantImage = [
                'name' => $variantFiles['name'][$variantIndex]['image'],
                'type' => $variantFiles['type'][$variantIndex]['image'] ?? '',
                'tmp_name' => $variantFiles['tmp_name'][$variantIndex]['image'] ?? '',
                'error' => $variantFiles['error'][$variantIndex]['image'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $variantFiles['size'][$variantIndex]['image'] ?? 0,
            ];
            $variantImageResult = $inspectImage($rawVariantImage);
            if (is_string($variantImageResult)) {
                $errors['variant_' . $rowNumber . '_image'] = $variantImageResult;
            } elseif (is_array($variantImageResult)) {
                $variantImageFile = $variantImageResult;
            }
        }

        if (!isset($errors['variant_' . $rowNumber . '_name'])
            && !isset($errors['variant_' . $rowNumber . '_additional_price'])
            && !isset($errors['variant_' . $rowNumber . '_stock_quantity'])
            && !isset($errors['variant_' . $rowNumber . '_status'])
            && !isset($errors['variant_' . $rowNumber . '_image'])) {
            $validVariants[] = [
                'name' => $variantRow['name'],
                'additional_price' => number_format((float) $variantRow['additional_price'], 2, '.', ''),
                'stock_quantity' => (int) $variantRow['stock_quantity'],
                'status' => $variantRow['status'],
                'image_file' => $variantImageFile,
            ];
        }
    }
    if ($variantRows === []) {
        $variantRows = [[
            'name' => '',
            'additional_price' => '0',
            'stock_quantity' => '0',
            'status' => 'active',
        ]];
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

    if ($formData['sale_price'] !== '') {
        if (!is_numeric($formData['sale_price']) || (float) $formData['sale_price'] < 0) {
            $errors['sale_price'] = 'Sale price must be a non-negative number.';
        } elseif (!isset($errors['price']) && (float) $formData['sale_price'] >= (float) $formData['price']) {
            $errors['sale_price'] = 'Sale price must be lower than the regular price.';
        }
    }

    if (!preg_match('/^\d+$/', $formData['stock_quantity'])) {
        $errors['stock_quantity'] = 'Stock quantity must be a whole number of zero or more.';
    }

    $pendingImages = [];

    $mainImage = $_FILES['main_image'] ?? null;
    if (is_array($mainImage)) {
        $mainResult = $inspectImage($mainImage);
        if (is_string($mainResult)) {
            $errors['main_image'] = $mainResult;
        } elseif (is_array($mainResult)) {
            $pendingImages[] = ['file' => $mainResult, 'sort_order' => 0];
        }
    }

    $galleryImages = $_FILES['gallery_images'] ?? null;
    if (is_array($galleryImages) && is_array($galleryImages['name'] ?? null)) {
        foreach ($galleryImages['name'] as $index => $unusedName) {
            $galleryFile = [
                'name' => $galleryImages['name'][$index] ?? '',
                'type' => $galleryImages['type'][$index] ?? '',
                'tmp_name' => $galleryImages['tmp_name'][$index] ?? '',
                'error' => $galleryImages['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $galleryImages['size'][$index] ?? 0,
            ];
            $galleryResult = $inspectImage($galleryFile);
            if (is_string($galleryResult)) {
                $errors['gallery_images'] = $galleryResult;
                break;
            }
            if (is_array($galleryResult)) {
                $pendingImages[] = [
                    'file' => $galleryResult,
                    'sort_order' => count($pendingImages),
                ];
            }
        }
    }

    if ($errors === []) {
        $movedFiles = [];
        try {
            $connection = getPdoConnection();
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                throw new RuntimeException('Upload directory is unavailable.');
            }
            if (!is_writable($uploadDirectory)) {
                throw new RuntimeException('Upload directory is not writable.');
            }

            $connection->beginTransaction();
            $statement = $connection->prepare(
                'INSERT INTO products (
                    name, slug, description, short_description, full_description,
                    category, price, sale_price, sku, stock_quantity, is_featured, is_active
                ) VALUES (
                    :name, :slug, :description, :short_description, :full_description,
                    :category, :price, :sale_price, :sku, :stock_quantity, :is_featured, :is_active
                )'
            );
            $statement->execute([
                'name' => $formData['name'],
                'slug' => generateSlug($formData['name']) . '-' . strtolower($formData['sku']),
                'description' => $formData['full_description'],
                'short_description' => $formData['short_description'],
                'full_description' => $formData['full_description'],
                'category' => $formData['category'],
                'price' => number_format((float) $formData['price'], 2, '.', ''),
                'sale_price' => $formData['sale_price'] === '' ? null : number_format((float) $formData['sale_price'], 2, '.', ''),
                'sku' => $formData['sku'],
                'stock_quantity' => (int) $formData['stock_quantity'],
                'is_featured' => (int) $formData['is_featured'],
                'is_active' => $formData['status'] === 'active' ? 1 : 0,
            ]);

            $productId = (int) $connection->lastInsertId();

            if ($validVariants !== []) {
                $variantStatement = $connection->prepare(
                    'INSERT INTO product_variants (
                        product_id, variant_name, sku, price, stock_quantity, is_active, image_path
                    ) VALUES (
                        :product_id, :variant_name, :sku, :price, :stock_quantity, :is_active, :image_path
                    )'
                );

                foreach ($validVariants as $variant) {
                    $variantImagePath = null;
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
                        $variantImagePath = $uploadPathPrefix . $variantFilename;
                    }

                    $variantSlug = generateSlug($variant['name']);
                    $variantSku = substr($formData['sku'] . '-' . strtoupper($variantSlug), 0, 100);
                    $variantStatement->execute([
                        'product_id' => $productId,
                        'variant_name' => $variant['name'],
                        'sku' => $variantSku,
                        'price' => $variant['additional_price'],
                        'stock_quantity' => $variant['stock_quantity'],
                        'is_active' => $variant['status'] === 'active' ? 1 : 0,
                        'image_path' => $variantImagePath,
                    ]);
                }
            }

            $imageStatement = $connection->prepare(
                'INSERT INTO product_images (product_id, image_path, alt_text, sort_order)
                 VALUES (:product_id, :image_path, :alt_text, :sort_order)'
            );

            foreach ($pendingImages as $pendingImage) {
                $filename = 'product_' . bin2hex(random_bytes(16)) . '.' . $pendingImage['file']['extension'];
                $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($pendingImage['file']['tmp_name'], $destination)) {
                    throw new RuntimeException('Image could not be stored.');
                }
                $movedFiles[] = $destination;

                $imageStatement->execute([
                    'product_id' => $productId,
                    'image_path' => $uploadPathPrefix . $filename,
                    'alt_text' => $formData['name'],
                    'sort_order' => $pendingImage['sort_order'],
                ]);
            }

            $connection->commit();

            setFlashMessage('success', 'Product added successfully.');
            redirect(url('admin/products.php'));
        } catch (PDOException $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            foreach ($movedFiles as $movedFile) {
                if (is_file($movedFile)) {
                    unlink($movedFile);
                }
            }
            $errors['form'] = $exception->getCode() === '23000'
                ? 'A product with this SKU or generated name already exists.'
                : 'The product could not be saved. Please try again later.';
        } catch (RuntimeException $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            foreach ($movedFiles as $movedFile) {
                if (is_file($movedFile)) {
                    unlink($movedFile);
                }
            }
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
    <div class="mb-4">
        <p class="eyebrow mb-2">Catalog</p>
        <h1 class="section-title mb-2">Add product</h1>
        <p class="text-secondary mb-0">Create a new item for the Dastkar collection.</p>
    </div>

    <?php if ($databaseError): ?>
        <div class="alert alert-warning" role="alert">The database is temporarily unavailable. Please try again later.</div>
    <?php endif; ?>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <?php if (isset($errors['csrf_token'])): ?>
        <div class="alert alert-danger" role="alert"><?= e($errors['csrf_token']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('admin/product-add.php')) ?>" class="bg-white border rounded p-4" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="row g-4">
            <div class="col-lg-8">
                <h2 class="h5 mb-3">Product information</h2>
                <div class="mb-3">
                    <label class="form-label" for="name">Product name</label>
                    <input class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" type="text" id="name" name="name" maxlength="150" value="<?= e($formData['name']) ?>" required>
                    <?= $fieldError('name') ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="sku">SKU</label>
                        <input class="form-control<?= isset($errors['sku']) ? ' is-invalid' : '' ?>" type="text" id="sku" name="sku" maxlength="100" value="<?= e($formData['sku']) ?>" required>
                        <?= $fieldError('sku') ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">Category</label>
                        <input class="form-control<?= isset($errors['category']) ? ' is-invalid' : '' ?>" type="text" id="category" name="category" maxlength="100" value="<?= e($formData['category']) ?>" required>
                        <?= $fieldError('category') ?>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <label class="form-label" for="short_description">Short description</label>
                    <textarea class="form-control<?= isset($errors['short_description']) ? ' is-invalid' : '' ?>" id="short_description" name="short_description" rows="3" maxlength="255" required><?= e($formData['short_description']) ?></textarea>
                    <?= $fieldError('short_description') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="full_description">Full description</label>
                    <textarea class="form-control<?= isset($errors['full_description']) ? ' is-invalid' : '' ?>" id="full_description" name="full_description" rows="7" required><?= e($formData['full_description']) ?></textarea>
                    <?= $fieldError('full_description') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="main_image">Main product image</label>
                    <input class="form-control<?= isset($errors['main_image']) ? ' is-invalid' : '' ?>" type="file" id="main_image" name="main_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <div class="form-text">JPG, JPEG, PNG, or WEBP. Maximum size: 5 MB.</div>
                    <?= $fieldError('main_image') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="gallery_images">Gallery images</label>
                    <input class="form-control<?= isset($errors['gallery_images']) ? ' is-invalid' : '' ?>" type="file" id="gallery_images" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">Select multiple JPG, JPEG, PNG, or WEBP files. Maximum size: 5 MB each.</div>
                    <?= $fieldError('gallery_images') ?>
                </div>
            </div>
            <div class="col-lg-4">
                <h2 class="h5 mb-3">Pricing and inventory</h2>
                <div class="mb-3">
                    <label class="form-label" for="price">Regular price (PKR)</label>
                    <input class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>" type="number" id="price" name="price" min="0" step="0.01" value="<?= e($formData['price']) ?>" required>
                    <?= $fieldError('price') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="sale_price">Sale price (PKR)</label>
                    <input class="form-control<?= isset($errors['sale_price']) ? ' is-invalid' : '' ?>" type="number" id="sale_price" name="sale_price" min="0" step="0.01" value="<?= e($formData['sale_price']) ?>">
                    <?= $fieldError('sale_price') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="stock_quantity">Stock quantity</label>
                    <input class="form-control<?= isset($errors['stock_quantity']) ? ' is-invalid' : '' ?>" type="number" id="stock_quantity" name="stock_quantity" min="0" step="1" value="<?= e($formData['stock_quantity']) ?>" required>
                    <?= $fieldError('stock_quantity') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select<?= isset($errors['status']) ? ' is-invalid' : '' ?>" id="status" name="status" required>
                        <option value="active"<?= $formData['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="inactive"<?= $formData['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                    </select>
                    <?= $fieldError('status') ?>
                </div>
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"<?= $formData['is_featured'] === '1' ? ' checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Feature this product</label>
                </div>
            </div>
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Colors and variants</h2>
                        <p class="text-secondary small mb-0">Add optional color choices with their own price adjustment and stock.</p>
                    </div>
                    <button class="btn btn-outline-dark btn-sm" type="button" id="addVariant">+ Add color</button>
                </div>
                <div id="variantRows">
                    <?php foreach ($variantRows as $variantIndex => $variantRow): ?>
                        <div class="variant-row border rounded p-3 mb-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label" for="variant_name_<?= $variantIndex ?>">Color name</label>
                                    <input class="form-control<?= $variantError($variantIndex, 'name') !== '' ? ' is-invalid' : '' ?>" type="text" id="variant_name_<?= $variantIndex ?>" data-variant-field="name" name="variants[<?= $variantIndex ?>][name]" maxlength="100" value="<?= e($variantRow['name']) ?>">
                                    <?= $variantError($variantIndex, 'name') ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="variant_price_<?= $variantIndex ?>">Additional price (PKR)</label>
                                    <input class="form-control<?= $variantError($variantIndex, 'additional_price') !== '' ? ' is-invalid' : '' ?>" type="number" id="variant_price_<?= $variantIndex ?>" data-variant-field="additional_price" name="variants[<?= $variantIndex ?>][additional_price]" min="0" step="0.01" value="<?= e($variantRow['additional_price']) ?>">
                                    <?= $variantError($variantIndex, 'additional_price') ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="variant_stock_<?= $variantIndex ?>">Stock</label>
                                    <input class="form-control<?= $variantError($variantIndex, 'stock_quantity') !== '' ? ' is-invalid' : '' ?>" type="number" id="variant_stock_<?= $variantIndex ?>" data-variant-field="stock_quantity" name="variants[<?= $variantIndex ?>][stock_quantity]" min="0" step="1" value="<?= e($variantRow['stock_quantity']) ?>">
                                    <?= $variantError($variantIndex, 'stock_quantity') ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="variant_status_<?= $variantIndex ?>">Status</label>
                                    <select class="form-select<?= $variantError($variantIndex, 'status') !== '' ? ' is-invalid' : '' ?>" id="variant_status_<?= $variantIndex ?>" data-variant-field="status" name="variants[<?= $variantIndex ?>][status]">
                                        <option value="active"<?= $variantRow['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                                        <option value="inactive"<?= $variantRow['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                                    </select>
                                    <?= $variantError($variantIndex, 'status') ?>
                                </div>
                                <div class="col-md-1 text-md-end">
                                    <button class="btn btn-outline-danger btn-sm remove-variant<?= count($variantRows) === 1 ? ' d-none' : '' ?>" type="button">Remove</button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="variant_image_<?= $variantIndex ?>">Color photo (optional)</label>
                                    <input class="form-control<?= $variantError($variantIndex, 'image') !== '' ? ' is-invalid' : '' ?>" type="file" id="variant_image_<?= $variantIndex ?>" data-variant-field="image" name="variants[<?= $variantIndex ?>][image]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <div class="form-text">Shown to customers when they pick this color. JPG, PNG, or WEBP, max 5 MB.</div>
                                    <?= $variantError($variantIndex, 'image') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/products.php')) ?>">Cancel</a>
            <button class="btn btn-dark" type="submit">Save product</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
