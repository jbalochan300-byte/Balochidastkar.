-- Balochi Dastkar — real product catalog from customer photos
-- Import this file via phpMyAdmin (select your database first) OR run:
--   mysql -u root balochi_dastar < products_new.sql
-- Adjust prices/stock as needed before importing.

INSERT INTO products (
    name, slug, description, short_description, full_description, category,
    price, sale_price, sku, stock_quantity, colors, is_featured, is_active
) VALUES
(
    'Navy Blue Balochi Embroidered Shawl',
    'navy-blue-balochi-embroidered-shawl',
    'Hand-embroidered navy blue shawl (chaddar) with dense multicolour Balochi thread work along all four borders.',
    'Hand-embroidered navy shawl with full border work',
    'A generously sized navy blue shawl finished on every edge with traditional Balochi hand embroidery in red, gold, green and white thread. The dense geometric border work is entirely hand-stitched, making each piece slightly unique. Lightweight fabric drapes well over shoulders or head, suitable for everyday wear or special occasions.',
    'Embroidered Shawls',
    7500.00, 6750.00, 'BD-SHW-001', 10, 'Navy Blue', 1, 1
),
(
    'Olive Green Embroidered Balochi Frock',
    'olive-green-embroidered-balochi-frock',
    'Full-length olive green kurta with heavy traditional Balochi hand embroidery on the front panel, collar and cuffs.',
    'Heavily embroidered olive frock with matching dupatta',
    'A statement piece olive-green frock (kurta) featuring dense, multi-coloured hand embroidery across the front yoke, in the classic triangular Balochi panel style, with matching embroidered cuffs. Comes with a coordinating dupatta. Ideal for weddings, festivals and cultural events.',
    'Embroidered Dresses',
    18500.00, 16650.00, 'BD-DRS-002', 6, 'Olive Green', 1, 1
),
(
    'Maroon Mirror-Work Sindhi Cap (Koka Topi)',
    'maroon-mirror-work-sindhi-cap',
    'Traditional maroon cap with dense mirror work and gold-thread diamond embroidery, finished with a scalloped edge.',
    'Hand-embroidered maroon cap with mirror work',
    'A traditional round cap (koka/topi) hand-embroidered in maroon thread with a diamond lattice pattern in red, blue and black, inlaid with mirror work and finished in gold zari trim. Scalloped brim with tassel ties. A classic accessory piece rooted in Sindhi-Balochi craft.',
    'Traditional Caps',
    3200.00, NULL, 'BD-CAP-003', 15, 'Maroon', 1, 1
),
(
    'Grey Mirror-Work Embroidered Fabric Shawl',
    'grey-mirror-work-embroidered-fabric-shawl',
    'Soft grey fabric adorned with scattered mirror-work floral medallions and a red cross-stitch grid pattern.',
    'Grey shawl with allover mirror-work medallions',
    'A soft grey base fabric decorated with an allover grid of red cross-stitch lines and hand-embroidered floral medallions in red, gold and navy, each centred with inlaid mirror work. A versatile, lighter-weight piece that works as a shawl, dupatta or unstitched suit fabric.',
    'Embroidered Shawls',
    6800.00, NULL, 'BD-SHW-004', 12, 'Grey', 0, 1
),
(
    'Maroon Classic Balochi Embroidered Kurta',
    'maroon-classic-balochi-embroidered-kurta',
    'Deep maroon kurta with the classic Balochi triangular embroidered front panel and matching embroidered cuffs.',
    'Classic maroon kurta with gold-thread front panel',
    'A timeless maroon kurta featuring the traditional triangular front embroidery panel in warm gold, rust and cream tones, complemented by matching hand-embroidered floral motifs scattered across the sleeves and hem. A wardrobe staple for those who love understated traditional elegance.',
    'Embroidered Dresses',
    15500.00, 13950.00, 'BD-DRS-005', 8, 'Maroon', 0, 1
),
(
    'Red Sindhi Mirror-Work Topi',
    'red-sindhi-mirror-work-topi',
    'Domed red cap densely embroidered with floral medallions and cutwork mirror inlays in a diagonal lattice.',
    'Dome-shaped red cap with cutwork mirror inlays',
    'A dome-shaped traditional cap hand-embroidered in red thread with green, gold and blue floral medallions, finished with open cutwork mirror inlays arranged in a diagonal lattice. A striking, fully handcrafted piece of headwear.',
    'Traditional Caps',
    3500.00, NULL, 'BD-CAP-006', 14, 'Red', 0, 1
),
(
    'Handcrafted Embroidered Clutch Bag',
    'handcrafted-embroidered-clutch-bag',
    'Structured leather-base clutch bags finished with dense multicolour hand embroidery across the flap.',
    'Embroidered leather-base clutch with fold-over flap',
    'A structured fold-over clutch with a leather base and a densely hand-embroidered flap featuring floral and geometric motifs in a rich, multicolour palette. Each clutch is finished with contrast piping. Available in a small range of colourways — ask us about current stock.',
    'Bags & Accessories',
    4200.00, NULL, 'BD-BAG-007', 9, 'Assorted', 1, 1
);

INSERT INTO product_images (product_id, image_path, alt_text, sort_order) VALUES
((SELECT id FROM products WHERE slug = 'navy-blue-balochi-embroidered-shawl'), 'uploads/products/shawl-navy-embroidered.jpg', 'Navy blue Balochi embroidered shawl with full border work', 0),
((SELECT id FROM products WHERE slug = 'olive-green-embroidered-balochi-frock'), 'uploads/products/dress-olive-heavy-embroidery.jpg', 'Olive green embroidered Balochi frock on mannequin', 0),
((SELECT id FROM products WHERE slug = 'maroon-mirror-work-sindhi-cap'), 'uploads/products/cap-maroon-mirrorwork.jpg', 'Maroon mirror-work Sindhi cap on stand', 0),
((SELECT id FROM products WHERE slug = 'grey-mirror-work-embroidered-fabric-shawl'), 'uploads/products/shawl-grey-mirrorwork.jpg', 'Grey fabric with mirror-work embroidered medallions', 0),
((SELECT id FROM products WHERE slug = 'maroon-classic-balochi-embroidered-kurta'), 'uploads/products/dress-maroon-classic.jpg', 'Maroon classic Balochi embroidered kurta laid flat', 0),
((SELECT id FROM products WHERE slug = 'red-sindhi-mirror-work-topi'), 'uploads/products/cap-red-sindhi-topi.jpg', 'Red Sindhi mirror-work domed cap', 0),
((SELECT id FROM products WHERE slug = 'handcrafted-embroidered-clutch-bag'), 'uploads/products/clutch-bags-embroidered-set.jpg', 'Row of handcrafted embroidered clutch bags', 0);
