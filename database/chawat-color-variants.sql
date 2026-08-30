-- Balochi Dastkar — adds per-color product photos
-- Run this in phpMyAdmin (SQL tab) on your existing balochi_dastar database.
-- Safe to run even though your database already has products/orders in it.

-- 1. Add a column so each color variant can have its own photo.
--    (Only run this ALTER once — if you get "Duplicate column name",
--    it means you already ran this and can skip straight to step 2.)
ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) NULL AFTER stock_quantity;

-- 2. Add the three Chawat colors, each with its own photo.
--    This replaces the single "Black" text option with three real,
--    selectable colors that each swap the product photo when clicked.
INSERT INTO product_variants (product_id, variant_name, sku, price, stock_quantity, image_path, is_active)
VALUES
(
    (SELECT id FROM products WHERE slug = 'chawat-traditional-leather-sandals'),
    'Black', 'BD-SHOE-001-BLK', NULL, 10, 'uploads/products/chawat-leather-sandals.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'chawat-traditional-leather-sandals'),
    'White', 'BD-SHOE-001-WHT', NULL, 10, 'uploads/products/chawat-white.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'chawat-traditional-leather-sandals'),
    'Red', 'BD-SHOE-001-RED', NULL, 10, 'uploads/products/chawat-red.jpg', 1
);
