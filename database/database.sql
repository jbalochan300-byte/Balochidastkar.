CREATE DATABASE IF NOT EXISTS balochi_dastar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE balochi_dastar;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS admins;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager') NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    KEY idx_admins_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(255) NULL,
    full_description TEXT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10, 2) NULL,
    sku VARCHAR(100) NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    colors VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_category (category),
    KEY idx_products_featured (is_featured),
    KEY idx_products_active (is_active),
    KEY idx_products_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(180) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_images_product (product_id),
    KEY idx_product_images_sort (product_id, sort_order),
    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_variants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_variants_sku (sku),
    KEY idx_product_variants_product (product_id),
    KEY idx_product_variants_active (is_active),
    CONSTRAINT fk_product_variants_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    shipping_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    additional_notes TEXT NULL,
    payment_method ENUM('cash_on_delivery') NOT NULL DEFAULT 'cash_on_delivery',
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'cod_pending') NOT NULL DEFAULT 'cod_pending',
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_number (order_number),
    KEY idx_orders_customer_email (customer_email),
    KEY idx_orders_status (status),
    KEY idx_orders_payment_status (payment_status),
    KEY idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    variant_id INT UNSIGNED NULL,
    product_name VARCHAR(150) NOT NULL,
    variant_name VARCHAR(100) NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    KEY idx_order_items_variant (variant_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_variant
        FOREIGN KEY (variant_id) REFERENCES product_variants (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(180) NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') NOT NULL DEFAULT 'new',
    admin_reply TEXT NULL,
    replied_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_messages_email (email),
    KEY idx_contact_messages_status (status),
    KEY idx_contact_messages_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_subscribers_email (email),
    KEY idx_newsletter_subscribers_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (name, email, password_hash, role, is_active)
VALUES (
    'Balochi Dastkar Administrator',
    'admin@balochidastar.test',
    '$2b$10$oTAnFFwUyXZzEcUkZC9Ki.gfpHJXYGZ3cOCGeWPYDFG9CFHabyA4i',
    'admin',
    1
);

INSERT INTO products (
    name, slug, description, category, price, sale_price, sku,
    stock_quantity, colors, is_featured, is_active
)
VALUES
(
    'Balochi Embroidered Dastar',
    'balochi-embroidered-dastar',
    'A hand-finished cotton dastar featuring traditional Balochi embroidery along the edge. Lightweight, breathable, and suitable for everyday wear or cultural gatherings.',
    'Embroidered Dastars',
    6500.00,
    5850.00,
    'BD-ED-001',
    18,
    'Indigo, Rust, Ivory',
    1,
    1
),
(
    'Makrani White Turban Dastar',
    'makrani-white-turban-dastar',
    'A crisp white dastar inspired by the coastal Makran tradition, woven in soft cotton for a clean drape and comfortable fit during warm weather.',
    'Classic Dastars',
    4800.00,
    NULL,
    'BD-MW-002',
    25,
    'White, Off-white',
    1,
    1
),
(
    'Kech Maroon Patterned Dastar',
    'kech-maroon-patterned-dastar',
    'A deep maroon dastar with a restrained geometric border, inspired by the rich textile colors of Kech. Finished with a soft cotton backing for dependable daily use.',
    'Patterned Dastars',
    7200.00,
    6480.00,
    'BD-KM-003',
    12,
    'Maroon, Gold, Charcoal',
    1,
    1
),
(
    'Gwadar Sandstone Dastar',
    'gwadar-sandstone-dastar',
    'A warm sandstone-colored dastar with a subtle woven texture, made for understated formal styling and inspired by the coastal landscape around Gwadar.',
    'Formal Dastars',
    5900.00,
    NULL,
    'BD-GS-004',
    20,
    'Sandstone, Cream',
    0,
    1
),
(
    'Quetta Winter Wool Dastar',
    'quetta-winter-wool-dastar',
    'A warm wool-blend dastar designed for colder evenings in Quetta and the highlands. Its charcoal base is finished with a muted rust border for a dignified winter look.',
    'Winter Dastars',
    8900.00,
    8010.00,
    'BD-QW-005',
    9,
    'Charcoal, Rust, Black',
    1,
    1
);
