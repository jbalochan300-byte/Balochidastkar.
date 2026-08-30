-- Balochi Dastkar — adds per-color photos to the Balochi Shawl product
-- Run this in phpMyAdmin (SQL tab) on your existing balochi_dastar database.
-- Safe to run even though your database already has products/orders in it.

-- If you have NOT already run database/chawat-color-variants.sql, run this
-- first (skip it if you already have — you'll get a "Duplicate column
-- name" error if you try to run it twice, which just means it's already there):
-- ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) NULL AFTER stock_quantity;

-- Adds Black (your original photo), Blue, and Green as real, selectable
-- colors that each swap the product photo when clicked.
INSERT INTO product_variants (product_id, variant_name, sku, price, stock_quantity, image_path, is_active)
VALUES
(
    (SELECT id FROM products WHERE slug = 'balochi-handwoven-striped-shawl'),
    'Black', 'BD-SHW-008-BLK', NULL, 10, 'uploads/products/shawl-multicolor-striped.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'balochi-handwoven-striped-shawl'),
    'Blue', 'BD-SHW-008-BLU', NULL, 10, 'uploads/products/shawl-blue.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'balochi-handwoven-striped-shawl'),
    'Green', 'BD-SHW-008-GRN', NULL, 10, 'uploads/products/shawl-green.jpg', 1
);
