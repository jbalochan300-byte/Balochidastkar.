<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Manage Products';
$filters = [
    'search' => sanitizeInput($_GET['search'] ?? ''),
    'status' => sanitizeInput($_GET['status'] ?? ''),
    'featured' => sanitizeInput($_GET['featured'] ?? ''),
    'category' => sanitizeInput($_GET['category'] ?? ''),
];
$products = [];
$categories = [];
$databaseError = false;

try {
    $connection = getPdoConnection();

    $categoryStatement = $connection->prepare(
        'SELECT DISTINCT category
         FROM products
         WHERE category <> :empty_category
         ORDER BY category ASC'
    );
    $categoryStatement->execute(['empty_category' => '']);
    $categories = $categoryStatement->fetchAll(PDO::FETCH_COLUMN);

    $conditions = [];
    $parameters = [];

    if ($filters['search'] !== '') {
        $conditions[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku)';
        $searchValue = '%' . $filters['search'] . '%';
        $parameters['search_name'] = $searchValue;
        $parameters['search_sku'] = $searchValue;
    }

    if (in_array($filters['status'], ['active', 'inactive'], true)) {
        $conditions[] = 'p.is_active = :is_active';
        $parameters['is_active'] = $filters['status'] === 'active' ? 1 : 0;
    }

    if (in_array($filters['featured'], ['yes', 'no'], true)) {
        $conditions[] = 'p.is_featured = :is_featured';
        $parameters['is_featured'] = $filters['featured'] === 'yes' ? 1 : 0;
    }

    if ($filters['category'] !== '') {
        $conditions[] = 'p.category = :category';
        $parameters['category'] = $filters['category'];
    }

    $whereClause = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $productStatement = $connection->prepare(
        'SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.stock_quantity,
                p.category, p.is_active, p.is_featured,
                (SELECT pi.image_path
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                 ORDER BY pi.sort_order ASC, pi.id ASC
                 LIMIT 1) AS image_path
         FROM products p
         ' . $whereClause . '
         ORDER BY p.created_at DESC, p.id DESC'
    );
    $productStatement->execute($parameters);
    $products = $productStatement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = true;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Catalog</p>
            <h1 class="section-title mb-2">Products</h1>
            <p class="text-secondary mb-0">Manage your Dastkar collection and stock.</p>
        </div>
        <a class="btn btn-dark" href="<?= e(url('admin/product-add.php')) ?>">+ Add Product</a>
    </div>

    <?php if ($databaseError): ?>
        <div class="alert alert-warning" role="alert">Products are temporarily unavailable. Please check the database connection.</div>
    <?php else: ?>
        <form class="bg-white border rounded p-3 mb-4" method="get" action="<?= e(url('admin/products.php')) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="search">Search</label>
                    <input class="form-control" type="search" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Name or SKU">
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All statuses</option>
                        <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="inactive"<?= $filters['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="featured">Featured</label>
                    <select class="form-select" id="featured" name="featured">
                        <option value="">All products</option>
                        <option value="yes"<?= $filters['featured'] === 'yes' ? ' selected' : '' ?>>Featured</option>
                        <option value="no"<?= $filters['featured'] === 'no' ? ' selected' : '' ?>>Not featured</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="category">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category) ?>"<?= $filters['category'] === $category ? ' selected' : '' ?>><?= e($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2 d-flex gap-2">
                    <button class="btn btn-dark flex-grow-1" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('admin/products.php')) ?>">Clear</a>
                </div>
            </div>
        </form>

        <div class="bg-white border rounded overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Image</th>
                            <th scope="col">Name</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Price</th>
                            <th scope="col">Sale price</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col">Featured</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products === []): ?>
                            <tr><td class="text-center text-secondary py-5" colspan="9">No products match the selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_path'])): ?>
                                            <img src="<?= e(url($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" width="52" height="52" class="rounded object-fit-cover">
                                        <?php else: ?>
                                            <span class="d-inline-flex align-items-center justify-content-center bg-light text-secondary rounded" style="width: 52px; height: 52px;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($product['name']) ?></strong>
                                        <small class="d-block text-secondary"><?= e($product['category']) ?></small>
                                    </td>
                                    <td><?= e($product['sku']) ?></td>
                                    <td><?= e(formatPrice($product['price'])) ?></td>
                                    <td><?= $product['sale_price'] !== null ? e(formatPrice($product['sale_price'])) : '<span class="text-secondary">-</span>' ?></td>
                                    <td><?= e((string) $product['stock_quantity']) ?></td>
                                    <td><span class="badge <?= (int) $product['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $product['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                                    <td><?= (int) $product['is_featured'] === 1 ? '<span class="badge text-bg-warning">Yes</span>' : '<span class="text-secondary">No</span>' ?></td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/product-edit.php?id=' . (int) $product['id'])) ?>">Edit</a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= e(url('admin/product-delete.php?id=' . (int) $product['id'])) ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
