<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$email = (string) sanitizeInput($_POST['email'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlashMessage('danger', 'Please provide a valid email address.');
    redirect(url('contact.php'));
}

try {
    $statement = getPdoConnection()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (:email) ON DUPLICATE KEY UPDATE is_active = 1, unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP');
    $statement->execute(['email' => $email]);
    setFlashMessage('success', 'You are now subscribed to our newsletter.');
} catch (Throwable $exception) {
    setFlashMessage('danger', 'We could not subscribe you right now. Please try again later.');
}

redirect(url('contact.php'));
