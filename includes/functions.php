<?php

declare(strict_types=1);

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);

    session_start();
}

function redirect(string $location): void
{
    header('Location: ' . $location, true, 302);
    exit;
}

function sanitizeInput(mixed $value): mixed
{
    if (is_array($value)) {
        return array_map('sanitizeInput', $value);
    }

    if ($value === null) {
        return null;
    }

    return trim(strip_tags((string) $value));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function setFlashMessage(string $type, string $message): void
{
    startSecureSession();
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlashMessages(): array
{
    startSecureSession();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function validateInput(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $fieldRules) {
        $value = trim((string) ($data[$field] ?? ''));
        $label = $fieldRules['label'] ?? ucfirst(str_replace('_', ' ', $field));

        if (($fieldRules['required'] ?? false) && $value === '') {
            $errors[$field] = $label . ' is required.';
            continue;
        }

        if ($value === '') {
            continue;
        }

        if (($fieldRules['email'] ?? false) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = $label . ' must be a valid email address.';
            continue;
        }

        if (isset($fieldRules['min']) && mb_strlen($value) < (int) $fieldRules['min']) {
            $errors[$field] = $label . ' must be at least ' . (int) $fieldRules['min'] . ' characters.';
            continue;
        }

        if (isset($fieldRules['max']) && mb_strlen($value) > (int) $fieldRules['max']) {
            $errors[$field] = $label . ' must not exceed ' . (int) $fieldRules['max'] . ' characters.';
            continue;
        }

        if (isset($fieldRules['in']) && !in_array($value, $fieldRules['in'], true)) {
            $errors[$field] = $label . ' contains an invalid value.';
        }
    }

    return $errors;
}

function generateSlug(string $value): string
{
    $value = trim($value);

    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted !== false ? $converted : $value;
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

    return trim($value, '-');
}

function formatPrice(float|int|string $amount, ?string $currency = null): string
{
    $currency = $currency ?? (defined('CURRENCY') ? CURRENCY : 'PKR');

    return $currency . ' ' . number_format((float) $amount, 2);
}

function generateOrderNumber(): string
{
    return 'BD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function generateCsrfToken(): string
{
    startSecureSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    startSecureSession();
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($sessionToken)
        && is_string($token)
        && $token !== ''
        && hash_equals($sessionToken, $token);
}
