<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();

// Already logged in? Go straight to the dashboard.
if (isAdminLoggedIn()) {
    redirect(url('admin/dashboard.php'));
}

$pageTitle = 'Admin Login';
$formData = ['email' => ''];
$errors = [];
$justLoggedOut = isset($_GET['logged_out']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $formData['email'] = (string) sanitizeInput($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } elseif ($formData['email'] === '' || $password === '') {
        $errors['form'] = 'Please enter your email and password.';
    } elseif (attemptAdminLogin($formData['email'], $password)) {
        redirect(url('admin/dashboard.php'));
    } else {
        $errors['form'] = 'Incorrect email or password.';
    }
}

$csrfToken = generateCsrfToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-login-shell d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="admin-login-card">
        <div class="text-center mb-4">
            <img class="admin-login-logo" src="<?= e(url('assets/images/logo-icon.png')) ?>" alt="<?= e(APP_NAME) ?> logo">
            <h1 class="admin-login-title mt-3 mb-1"><?= e(APP_NAME) ?></h1>
            <p class="text-secondary mb-0">Admin panel sign in</p>
        </div>
        <?php if ($justLoggedOut): ?>
            <div class="alert alert-success" role="alert">You have been signed out.</div>
        <?php endif; ?>
        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('admin/login.php')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= e($formData['email']) ?>" maxlength="190" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" maxlength="255" required>
            </div>
            <button class="btn btn-dark w-100" type="submit">Sign in</button>
        </form>
        <div class="text-center mt-4">
            <a class="text-link" href="<?= e(url('index.php')) ?>">&larr; Back to the website</a>
        </div>
    </div>
</div>
</body>
</html>
