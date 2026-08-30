-- Balochi Dastkar — two additional products (Chawat sandals + woven shawl)
-- Import this file via phpMyAdmin AFTER database.sql and products_new.sql.
-- Safe to run even though the store already has products: this only INSERTs
-- these two new rows and does not touch anything you already imported.

INSERT INTO products (
    name, slug, description, short_description, full_description, category,
    price, sale_price, sku, stock_quantity, colors, is_featured, is_active
) VALUES
(
    'Chawat',
    'chawat-traditional-leather-sandals',
    'Traditional handmade Chawat sandals with woven leather straps and a durable rubber sole.',
    'Handmade leather Chawat sandals with woven strap detail',
    'A pair of traditional Chawat sandals, handcrafted with woven black leather straps across the front and a sturdy, grip-soled base built for everyday wear. A classic piece of regional footwear finished by hand.',
    'Traditional Shoes',
    3500.00, NULL, 'BD-SHOE-001', 10, 'Black', 1, 1
),
(
    'Balochi Shawl',
    'balochi-handwoven-striped-shawl',
    'Handwoven black shawl with bold multicolour striped bands and a matching fringed side panel.',
    'Handwoven striped shawl with fringed edge',
    'A generously sized handwoven shawl in black, banded with bold multicolour striped patterns in red, green and gold, finished with a coordinating fringed side panel and tasselled edge. A versatile everyday wrap rooted in traditional weaving.',
    'Woven Shawls',
    5500.00, NULL, 'BD-SHW-008', 10, 'Black', 1, 1
);

INSERT INTO product_images (product_id, image_path, alt_text, sort_order) VALUES
((SELECT id FROM products WHERE slug = 'chawat-traditional-leather-sandals'), 'uploads/products/chawat-leather-sandals.jpg', 'Chawat traditional handmade leather sandals', 0),
((SELECT id FROM products WHERE slug = 'balochi-handwoven-striped-shawl'), 'uploads/products/shawl-multicolor-striped.jpg', 'Handwoven black Balochi shawl with multicolour striped bands', 0);
