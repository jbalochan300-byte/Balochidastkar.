# Balochi Dastkar

A procedural PHP e-commerce store for Balochi handmade dastkars, dresses, shawls, caps and accessories, running on XAMPP.

## Stack

- PHP
- MySQL
- HTML5 and CSS3
- Bootstrap 5 via CDN
- JavaScript and jQuery via CDN

## Local setup

1. Copy this folder to `C:\xampp\htdocs\myproject`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Create/select the `balochi_dastar` database, then import, **in this order**:
   1. `database/database.sql` — creates all tables (products, orders, admins, contact messages, newsletter) and seeds 5 starter products plus one admin account.
   2. `database/products_new.sql` — adds the real product catalog (shawls, dresses, caps, clutch bags) matched to the photos in `uploads/products/`.
5. Open `http://localhost/myproject/`.

Do **not** import `database/schema.sql` — it's an older, incomplete draft kept for reference only and is missing the `admins`, `contact_messages`, and `newsletter_subscribers` tables the site now needs.

The database credentials and application URL are in `config/config.php`.

## Admin panel

Open `http://localhost/myproject/admin/login.php`.

Default seeded login:

- **Email:** `admin@balochidastar.test`
- **Password:** `Balochi@2026`

Change this password directly in the `admins` table (via phpMyAdmin) once you're up and running — there is no in-panel "change password" screen yet. To set a new one, generate a bcrypt hash and update the `password_hash` column for that row.

From the admin panel you can:

- View dashboard stats (products, orders, messages, subscribers)
- Add, edit, delete products — including price, sale price, stock, colors and variants
- View and update order status, and see each order's customer name/email/phone
- View and manage contact form submissions
- View newsletter subscribers

## Current scope

Product management, shopping cart, checkout, and admin screens are built. Customer accounts (login/registration for shoppers) are not implemented — checkout is guest-only, with customer details captured per order.
