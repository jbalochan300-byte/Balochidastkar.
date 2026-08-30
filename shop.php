<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shop';
$products = [];
$categories = [];
$colors = [];
$databaseError = false;
$filters = [
    'search' => (string) sanitizeInput($_GET['search'] ?? ''),
    'category' => (string) sanitizeInput($_GET['category'] ?? ''),
    'color' => (string) sanitizeInput($_GET['color'] ?? ''),
    'min_price' => trim((string) ($_GET['min_price'] ?? '')),
    'max_price' => trim((string) ($_GET['max_price'] ?? '')),
    'sort' => (string) sanitizeInput($_GET['sort'] ?? 'newest'),
];
$allowedSorts = ['newest', 'price_low', 'price_high', 'featured'];
if (!in_array($filters['sort'], $allowedSorts, true)) {
    $filters['sort'] = 'newest';
}
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page !== false && $page > 0 ? $page : 1;
$perPage = 9;
$totalProducts = 0;
$totalPages = 1;

try {
    $connection = getPdoConnection();
    $categoryStatement = $connection->prepare('SELECT DISTINCT category FROM products WHERE is_active = :active AND category <> :empty_category ORDER BY category ASC');
    $categoryStatement->execute(['active' => 1, 'empty_category' => '']);
    $categories = $categoryStatement->fetchAll(PDO::FETCH_COLUMN);

    $colorStatement = $connection->prepare('SELECT colors FROM products WHERE is_active = :active AND colors IS NOT NULL AND colors <> :empty_colors');
    $colorStatement->execute(['active' => 1, 'empty_colors' => '']);
    foreach ($colorStatement->fetchAll(PDO::FETCH_COLUMN) as $colorList) {
        foreach (explode(',', (string) $colorList) as $color) {
            $color = trim($color);
            if ($color !== '') {
                $colors[strtolower($color)] = $color;
            }
        }
    }
    $variantColorStatement = $connection->prepare('SELECT DISTINCT variant_name FROM product_variants WHERE is_active = :active ORDER BY variant_name ASC');
    $variantColorStatement->execute(['active' => 1]);
    foreach ($variantColorStatement->fetchAll(PDO::FETCH_COLUMN) as $color) {
        $colors[strtolower((string) $color)] = (string) $color;
    }
    natcasesort($colors);

    $conditions = ['p.is_active = :active'];
    $parameters = ['active' => 1];
    if ($filters['search'] !== '') {
        $conditions[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku OR p.category LIKE :search_category)';
        $search = '%' . $filters['search'] . '%';
        $parameters['search_name'] = $search;
        $parameters['search_sku'] = $search;
        $parameters['search_category'] = $search;
    }
    if ($filters['category'] !== '') {
        $conditions[] = 'p.category = :category';
        $parameters['category'] = $filters['category'];
    }
    if ($filters['color'] !== '') {
        $conditions[] = '(FIND_IN_SET(:color_list, REPLACE(p.colors, " ", "")) > 0 OR EXISTS (SELECT 1 FROM product_variants pv_filter WHERE pv_filter.product_id = p.id AND pv_filter.variant_name = :variant_color AND pv_filter.is_active = 1))';
        $parameters['color_list'] = str_replace(' ', '', $filters['color']);
        $parameters['variant_color'] = $filters['color'];
    }
    $priceExpression = 'CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price THEN p.sale_price ELSE p.price END';
    if ($filters['min_price'] !== '' && is_numeric($filters['min_price']) && (float) $filters['min_price'] >= 0) {
        $conditions[] = $priceExpression . ' >= :min_price';
        $parameters['min_price'] = (float) $filters['min_price'];
    }
    if ($filters['max_price'] !== '' && is_numeric($filters['max_price']) && (float) $filters['max_price'] >= 0) {
        $conditions[] = $priceExpression . ' <= :max_price';
        $parameters['max_price'] = (float) $filters['max_price'];
    }
    $where = 'WHERE ' . implode(' AND ', $conditions);
    $countStatement = $connection->prepare('SELECT COUNT(*) FROM products p ' . $where);
    $countStatement->execute($parameters);
    $totalProducts = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $orderBy = match ($filters['sort']) {
        'price_low' => $priceExpression . ' ASC, p.id DESC',
        'price_high' => $priceExpression . ' DESC, p.id DESC',
        'featured' => 'p.is_featured DESC, p.created_at DESC, p.id DESC',
        default => 'p.created_at DESC, p.id DESC',
    };
    $productStatement = $connection->prepare(
        'SELECT p.id, p.name, p.slug, p.description, p.category, p.price, p.sale_price, p.is_featured,
                (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order ASC, pi.id ASC LIMIT 1) AS image_path
         FROM products p ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $name => $value) {
        $productStatement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $productStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $productStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $productStatement->execute();
    $products = $productStatement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = true;
}

function shopQuery(array $filters, int $page = 1): string
{
    $query = array_filter($filters, static fn ($value): bool => $value !== '');
    if ($page > 1) {
        $query['page'] = $page;
    }
    return http_build_query($query);
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="shop-intro"><div class="container py-5"><p class="eyebrow mb-2">The collection</p><h1 class="section-title mb-2">Shop the edit</h1><p class="text-secondary mb-0">Traditional Balochi Dastkars, selected for a contemporary wardrobe.</p></div></section>
<div class="container py-5"><div class="row g-4"><aside class="col-lg-3"><div class="shop-filters bg-white border p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Refine</h2><a class="small text-link" href="<?= e(url('shop.php')) ?>">Clear</a></div><form method="get" action="<?= e(url('shop.php')) ?>"><div class="mb-3"><label class="form-label" for="shopSearch">Search</label><input class="form-control" type="search" id="shopSearch" name="search" value="<?= e($filters['search']) ?>" placeholder="Name or SKU"></div><div class="mb-3"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"<?= $filters['category'] === $category ? ' selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="color">Color</label><select class="form-select" id="color" name="color"><option value="">All colors</option><?php foreach ($colors as $color): ?><option value="<?= e($color) ?>"<?= strtolower($filters['color']) === strtolower($color) ? ' selected' : '' ?>><?= e($color) ?></option><?php endforeach; ?></select></div><div class="row g-2"><div class="col-6"><label class="form-label" for="min_price">Min price</label><input class="form-control" type="number" min="0" step="1" id="min_price" name="min_price" value="<?= e($filters['min_price']) ?>"></div><div class="col-6"><label class="form-label" for="max_price">Max price</label><input class="form-control" type="number" min="0" step="1" id="max_price" name="max_price" value="<?= e($filters['max_price']) ?>"></div></div><button class="btn btn-dark w-100 mt-4" type="submit">Apply filters</button></form></div></aside><section class="col-lg-9"><div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4"><p class="text-secondary small mb-0"><?= $databaseError ? 'Collection unavailable' : e((string) $totalProducts) . ' pieces' ?></p><form method="get" action="<?= e(url('shop.php')) ?>" class="d-flex align-items-center gap-2"><input type="hidden" name="search" value="<?= e($filters['search']) ?>"><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><input type="hidden" name="color" value="<?= e($filters['color']) ?>"><input type="hidden" name="min_price" value="<?= e($filters['min_price']) ?>"><input type="hidden" name="max_price" value="<?= e($filters['max_price']) ?>"><label class="small text-secondary" for="sort">Sort</label><select class="form-select form-select-sm" id="sort" name="sort" onchange="this.form.submit()"><option value="newest"<?= $filters['sort'] === 'newest' ? ' selected' : '' ?>>Newest</option><option value="price_low"<?= $filters['sort'] === 'price_low' ? ' selected' : '' ?>>Price low to high</option><option value="price_high"<?= $filters['sort'] === 'price_high' ? ' selected' : '' ?>>Price high to low</option><option value="featured"<?= $filters['sort'] === 'featured' ? ' selected' : '' ?>>Featured</option></select></form></div><?php if ($databaseError): ?><div class="alert alert-light border">The collection is temporarily unavailable. Please try again soon.</div><?php elseif ($products === []): ?><div class="empty-collection border p-4"><h2 class="h5">No pieces found</h2><p class="text-secondary mb-0">Try broadening your search or clearing a filter.</p></div><?php else: ?><div class="row g-4"><?php foreach ($products as $product): ?><div class="col-md-6 col-xl-4"><article class="product-card h-100"><a class="product-image" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>"><?php if (!empty($product['image_path'])): ?><img src="<?= e(url($product['image_path'])) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><span class="product-image-placeholder"><?= e(APP_NAME) ?></span><?php endif; ?></a><div class="p-3"><p class="eyebrow mb-2"><?= e($product['category']) ?></p><h2 class="h5 mb-3"><a class="text-decoration-none text-reset" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a></h2><div class="d-flex align-items-center gap-2"><strong><?= e(formatPrice($product['sale_price'] ?? $product['price'])) ?></strong><?php if ($product['sale_price'] !== null): ?><del class="text-secondary small"><?= e(formatPrice($product['price'])) ?></del><?php endif; ?></div></div></article></div><?php endforeach; ?></div><?php if ($totalPages > 1): ?><nav class="mt-5" aria-label="Product pages"><ul class="pagination justify-content-center"><?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?><li class="page-item<?= $pageNumber === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e(url('shop.php?' . shopQuery($filters, $pageNumber))) ?>"><?= $pageNumber ?></a></li><?php endfor; ?></ul></nav><?php endif; ?><?php endif; ?></section></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
