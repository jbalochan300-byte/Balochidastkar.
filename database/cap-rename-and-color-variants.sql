-- Balochi Dastkar — renames the cap product and adds Red / Black / Blue colors
-- Run this in phpMyAdmin (SQL tab) on your existing balochi_dastar database.

-- If you have NOT already added the image_path column (from the Chawat
-- update), run this first — skip it if you already have (you'll get a
-- "Duplicate column name" error if you try to run it twice):
-- ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) NULL AFTER stock_quantity;

-- 1. Rename the product.
UPDATE products
SET name = 'Balochi Traditional Cap'
WHERE slug = 'maroon-mirror-work-sindhi-cap';

-- 2. Add Red (your original photo), Black, and Blue as selectable colors,
--    each with its own photo that swaps in when clicked.
--
--    A NOTE ON THE BLACK PHOTO: this is a digitally recolored version of
--    the red cap (the red fabric shifted to black), not a separate photo
--    of a physical black cap. Use it only if you actually plan to stock a
--    black version — otherwise a customer could order expecting exactly
--    this and there'd be nothing real to send them. Swap in a genuine
--    photo any time by replacing uploads/products/cap-black-mockup.jpg.
INSERT INTO product_variants (product_id, variant_name, sku, price, stock_quantity, image_path, is_active)
VALUES
(
    (SELECT id FROM products WHERE slug = 'maroon-mirror-work-sindhi-cap'),
    'Red', 'BD-CAP-003-RED', NULL, 15, 'uploads/products/cap-maroon-mirrorwork.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'maroon-mirror-work-sindhi-cap'),
    'Black', 'BD-CAP-003-BLK', NULL, 15, 'uploads/products/cap-black-mockup.jpg', 1
),
(
    (SELECT id FROM products WHERE slug = 'maroon-mirror-work-sindhi-cap'),
    'Blue', 'BD-CAP-003-BLU', NULL, 15, 'uploads/products/cap-blue.jpg', 1
);
