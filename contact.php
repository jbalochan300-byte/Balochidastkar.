<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact';
$formData = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
$errors = [];
$submitted = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $formData = [
        'name' => (string) sanitizeInput($_POST['name'] ?? ''),
        'email' => (string) sanitizeInput($_POST['email'] ?? ''),
        'phone' => (string) sanitizeInput($_POST['phone'] ?? ''),
        'subject' => (string) sanitizeInput($_POST['subject'] ?? ''),
        'message' => (string) sanitizeInput($_POST['message'] ?? ''),
    ];
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    }
    $errors = array_merge($errors, validateInput($formData, [
        'name' => ['label' => 'Name', 'required' => true, 'max' => 120],
        'email' => ['label' => 'Email', 'required' => true, 'email' => true, 'max' => 190],
        'phone' => ['label' => 'Phone', 'required' => true, 'max' => 30],
        'subject' => ['label' => 'Subject', 'required' => true, 'max' => 180],
        'message' => ['label' => 'Message', 'required' => true, 'min' => 10],
    ]));
    if ($errors === []) {
        try {
            $statement = getPdoConnection()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)');
            $statement->execute($formData);
            $submitted = true;
            $formData = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
        } catch (Throwable $exception) {
            $errors['form'] = 'Your message could not be sent. Please try again later.';
        }
    }
}

$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>
<section class="shop-intro"><div class="container py-5"><p class="eyebrow mb-2">Let’s talk</p><h1 class="section-title mb-2">Contact <?= e(APP_NAME) ?></h1><p class="text-secondary mb-0">Questions about a piece, an order, or a gift? Send us a note.</p></div></section>
<div class="container py-5"><div class="row g-5"><div class="col-lg-7"><?php if ($submitted): ?><div class="alert alert-success" role="alert">Thank you. Your message has been received.</div><?php endif; ?><?php if (isset($errors['form'])): ?><div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div><?php endif; ?><form method="post" action="<?= e(url('contact.php')) ?>" class="bg-white border p-4" novalidate><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" type="text" id="name" name="name" value="<?= e($formData['name']) ?>" maxlength="120" required><?= isset($errors['name']) ? '<div class="invalid-feedback d-block">' . e($errors['name']) . '</div>' : '' ?></div><div class="row g-3"><div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" value="<?= e($formData['email']) ?>" maxlength="190" required><?= isset($errors['email']) ? '<div class="invalid-feedback d-block">' . e($errors['email']) . '</div>' : '' ?></div><div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" type="tel" id="phone" name="phone" value="<?= e($formData['phone']) ?>" maxlength="30" required><?= isset($errors['phone']) ? '<div class="invalid-feedback d-block">' . e($errors['phone']) . '</div>' : '' ?></div></div><div class="mb-3 mt-3"><label class="form-label" for="subject">Subject</label><input class="form-control" type="text" id="subject" name="subject" value="<?= e($formData['subject']) ?>" maxlength="180" required><?= isset($errors['subject']) ? '<div class="invalid-feedback d-block">' . e($errors['subject']) . '</div>' : '' ?></div><div class="mb-4"><label class="form-label" for="message">Message</label><textarea class="form-control" id="message" name="message" rows="7" required><?= e($formData['message']) ?></textarea><?= isset($errors['message']) ? '<div class="invalid-feedback d-block">' . e($errors['message']) . '</div>' : '' ?></div><button class="btn btn-dark" type="submit">Send message</button></form></div><aside class="col-lg-4 offset-lg-1"><p class="eyebrow">Stay connected</p><h2 class="section-title h2">A slower kind of service.</h2><p class="text-secondary">We answer questions about fit, color, care, and delivery with the same attention we give the pieces.</p><div class="map-frame mt-4"><a href="https://www.google.com/maps/place/Green+Town/@25.982447,63.0514186,17z/data=!3m1!4b1!4m6!3m5!1s0x3eb8a7000e06db1b:0x1d1605e24675c4ea!8m2!3d25.9824422!4d63.0539935!16s%2Fg%2F11zc3gp23l" target="_blank" rel="noopener" aria-label="Open Green Town location in Google Maps"><iframe title="Map of Green Town" src="https://www.google.com/maps?q=Green+Town,25.9824422,63.0539935&z=17&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="pointer-events: none;"></iframe></a><a class="text-link d-inline-block mt-2" href="https://www.google.com/maps/place/Green+Town/@25.982447,63.0514186,17z/data=!3m1!4b1!4m6!3m5!1s0x3eb8a7000e06db1b:0x1d1605e24675c4ea!8m2!3d25.9824422!4d63.0539935!16s%2Fg%2F11zc3gp23l" target="_blank" rel="noopener">View on Google Maps <span aria-hidden="true">&rarr;</span></a></div><form method="post" action="<?= e(url('newsletter-signup.php')) ?>" class="newsletter-form mt-4"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><label class="visually-hidden" for="contactNewsletterEmail">Email address</label><input class="form-control" type="email" id="contactNewsletterEmail" name="email" placeholder="Your email address" required><button class="btn btn-dark" type="submit">Subscribe</button></form></aside></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
