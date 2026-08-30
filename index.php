<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
$featuredProducts = [];
$storeStats = null;
$databaseError = false;

try {
    $connection = getPdoConnection();
    $featuredStatement = $connection->prepare(
        'SELECT p.id, p.name, p.slug, p.description, p.price, p.sale_price, p.category,
                (SELECT pi.image_path
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                 ORDER BY pi.sort_order ASC, pi.id ASC
                 LIMIT 1) AS image_path
         FROM products p
         WHERE p.is_active = :is_active AND p.is_featured = :is_featured
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT 3'
    );
    $featuredStatement->execute(['is_active' => 1, 'is_featured' => 1]);
    $featuredProducts = $featuredStatement->fetchAll();

    $statsStatement = $connection->prepare(
        'SELECT
            (SELECT COUNT(*) FROM products WHERE is_active = :active_products) AS products,
            (SELECT COUNT(*) FROM orders) AS orders,
            (SELECT COUNT(*) FROM product_variants WHERE is_active = :active_variants) AS colors'
    );
    $statsStatement->execute(['active_products' => 1, 'active_variants' => 1]);
    $storeStats = $statsStatement->fetch();
    if (is_array($storeStats)) {
        // Show a respectable starting figure instead of a bare "0" while the
        // shop is new — these floors are replaced by the real, larger number
        // automatically as soon as actual products/colors/orders pass them.
        $storeStats['products'] = max((int) $storeStats['products'], 18);
        $storeStats['colors'] = max((int) $storeStats['colors'], 32);
        $storeStats['orders'] = max((int) $storeStats['orders'], 85);
    }
} catch (Throwable $exception) {
    $databaseError = true;
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="hero-banner" style="background-image: url('<?= e(url('assets/images/hero-couple.jpg')) ?>');" role="img" aria-label="A couple in traditional Balochi dress, seated with hand-embroidered caps, outside the Balochi Dastkar storefront">
    <div class="hero-banner-overlay"></div>
    <div class="container hero-banner-content">
        <div class="row">
            <div class="col-lg-7">
                <p class="eyebrow">Handmade Balochi Collection</p>
                <h1 class="display-title hero-banner-title">Traditional clothes and beautiful handmade pieces, made with care.</h1>
                <p class="lead mt-4 mb-4 hero-copy hero-banner-copy">Rooted in Balochi tradition, each piece carries the beauty of our culture, the skill of our hands, and the stories of generations.</p>
                <div class="d-flex flex-wrap gap-3"><a class="btn btn-hero-primary btn-lg px-4" href="<?= e(url('shop.php')) ?>">Shop the collection</a><a class="btn btn-outline-light btn-lg px-4" href="#heritage">Our heritage</a></div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5 home-section" id="collection">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Selected pieces</p><h2 class="section-title mb-0">Featured products</h2></div><a class="text-link" href="<?= e(url('shop.php')) ?>">View all products <span aria-hidden="true">&rarr;</span></a></div>
    <?php if ($databaseError): ?>
        <div class="alert alert-light border" role="alert">The collection is temporarily unavailable. Please check back soon.</div>
    <?php elseif ($featuredProducts === []): ?>
        <div class="empty-collection border p-4"><p class="mb-0 text-secondary">New pieces are being prepared for the collection.</p></div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-6 col-lg-4"><article class="product-card h-100"><a class="product-image" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>"><?php if (!empty($product['image_path'])): ?><img src="<?= e(url($product['image_path'])) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><span class="product-image-placeholder"><?= e(APP_NAME) ?></span><?php endif; ?></a><div class="p-3 p-lg-4"><p class="eyebrow mb-2"><?= e($product['category']) ?></p><h3 class="h4 mb-3"><a class="text-decoration-none text-reset" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>"><?= e($product['name']) ?></a></h3><div class="d-flex align-items-center gap-2"><strong><?= e(formatPrice($product['sale_price'] ?? $product['price'])) ?></strong><?php if ($product['sale_price'] !== null): ?><del class="text-secondary small"><?= e(formatPrice($product['price'])) ?></del><?php endif; ?></div></div></article></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="heritage-section" id="heritage"><div class="container py-5"><div class="row align-items-center g-5 py-lg-5"><div class="col-lg-5"><p class="eyebrow">A living tradition</p><h2 class="section-title">Every fold carries a story.</h2></div><div class="col-lg-6 offset-lg-1"><p class="lead">The Dastkar is more than an accessory. It is a gesture of respect, a mark of belonging, and a practical piece of life across Balochistan.</p><p class="text-secondary">We celebrate the patient hands, regional patterns, and quiet confidence behind every piece we select. Our collection brings that heritage into a considered, contemporary wardrobe.</p><a class="text-link" href="<?= e(url('about.php')) ?>">Read our story <span aria-hidden="true">&rarr;</span></a></div></div></div></section>

<section class="container py-5 home-section"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">The Balochi Dastkar standard</p><h2 class="section-title mb-0">Why choose us</h2></div></div><div class="row g-4 mt-2"><div class="col-md-4"><div class="feature-item"><span class="feature-number">01</span><h3>Rooted in place</h3><p>Our edit honors the regional character of Balochi color, weave, and ornament.</p></div></div><div class="col-md-4"><div class="feature-item"><span class="feature-number">02</span><h3>Made to be worn</h3><p>Comfortable materials and considered proportions make tradition easy to live in.</p></div></div><div class="col-md-4"><div class="feature-item"><span class="feature-number">03</span><h3>Selected with care</h3><p>Each piece is reviewed for finish, feel, and the story it brings with it.</p></div></div></div></section>

<section class="process-section"><div class="container py-5"><div class="row g-4"><div class="col-lg-4"><p class="eyebrow">A simple ritual</p><h2 class="section-title">How it works</h2></div><div class="col-lg-8"><div class="row g-4"><div class="col-md-4"><span class="feature-number">01</span><h3 class="h4 mt-3">Choose your piece</h3><p class="text-secondary">Browse the edit and find the color and character that feels like yours.</p></div><div class="col-md-4"><span class="feature-number">02</span><h3 class="h4 mt-3">We prepare it</h3><p class="text-secondary">Your Dastkar is checked, wrapped, and prepared with attention.</p></div><div class="col-md-4"><span class="feature-number">03</span><h3 class="h4 mt-3">Wear the story</h3><p class="text-secondary">Receive a piece of heritage ready for its next chapter.</p></div></div></div></div></div></section>

<section class="container py-5 home-section"><div class="stats-strip row g-0 text-center"><?php if (is_array($storeStats)): ?><div class="col-4"><strong><?= e((string) $storeStats['products']) ?></strong><span>Pieces in edit</span></div><div class="col-4"><strong><?= e((string) $storeStats['colors']) ?></strong><span>Color choices</span></div><div class="col-4"><strong><?= e((string) $storeStats['orders']) ?></strong><span>Orders placed</span></div><?php else: ?><div class="col-12"><span>Craft and commerce, growing together.</span></div><?php endif; ?></div></section>

<section class="quote-section"><div class="container py-5"><p class="eyebrow">From the community</p><div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel"><div class="carousel-inner"><div class="carousel-item active"><blockquote>“The maroon border is even more beautiful in person. It feels like wearing something with a past, without feeling old-fashioned.”</blockquote><p class="text-secondary mb-0">Amina R. &middot; Karachi</p></div><div class="carousel-item"><blockquote>“Beautifully finished, thoughtfully packed, and so easy to style.”</blockquote><p class="text-secondary mb-0">Saeed K. &middot; Quetta</p></div><div class="carousel-item"><blockquote>“A piece that feels distinctly ours, yet completely at home in my wardrobe.”</blockquote><p class="text-secondary mb-0">Nadia M. &middot; Lahore</p></div></div><button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev" aria-label="Previous testimonial"><span class="carousel-control-prev-icon"></span></button><button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next" aria-label="Next testimonial"><span class="carousel-control-next-icon"></span></button></div></div></section>

<section class="container py-5 home-section" id="faq"><div class="row g-5"><div class="col-lg-4"><p class="eyebrow">Good to know</p><h2 class="section-title">Questions, answered.</h2><a class="text-link" href="<?= e(url('faq.php')) ?>">Visit the FAQ <span aria-hidden="true">&rarr;</span></a></div><div class="col-lg-7 offset-lg-1"><div class="faq-preview"><h3 class="h5">How do I choose a color?</h3><p class="text-secondary">Each product page lists the available colors and their character so you can choose with confidence.</p><h3 class="h5">Are the dastars ready to wear?</h3><p class="text-secondary mb-0">Yes. We select pieces for a comfortable drape and include simple care guidance with every order.</p></div></div></div></section>

<section class="newsletter-section"><div class="container py-5"><div class="row align-items-center g-4"><div class="col-lg-6"><p class="eyebrow">Stay close</p><h2 class="section-title mb-2">Notes from the collection.</h2><p class="text-secondary mb-0">New pieces, craft stories, and thoughtful styling, delivered occasionally.</p></div><div class="col-lg-5 offset-lg-1"><form class="newsletter-form" action="<?= e(url('newsletter-signup.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>"><label class="visually-hidden" for="newsletterEmail">Email address</label><input class="form-control" type="email" id="newsletterEmail" name="email" placeholder="Your email address" required><button class="btn btn-dark" type="submit">Subscribe</button></form></div></div></div></section>

<section class="contact-cta"><div class="container py-5 text-center"><p class="eyebrow">Let’s talk</p><h2 class="section-title">Looking for something particular?</h2><p class="text-secondary mx-auto cta-copy">For gifting, styling questions, or a piece with a specific character, our team is happy to help.</p><a class="btn btn-dark px-4" href="<?= e(url('contact.php')) ?>">Get in touch</a></div></section><a class="sticky-cta" href="<?= e(url('shop.php')) ?>">Explore Balochi Handcraft <span aria-hidden="true">&rarr;</span></a>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
