<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * Ensures an admin is logged in. Redirects to the login page otherwise.
 * Call this at the very top of every protected admin page (before any HTML output).
 *
 * @return array{id:int,name:string,email:string,role:string}
 */
function requireAdminLogin(): array
{
    startSecureSession();

    if (empty($_SESSION['admin_id'])) {
        redirect(url('admin/login.php'));
    }

    return [
        'id' => (int) $_SESSION['admin_id'],
        'name' => (string) ($_SESSION['admin_name'] ?? 'Admin'),
        'email' => (string) ($_SESSION['admin_email'] ?? ''),
        'role' => (string) ($_SESSION['admin_role'] ?? 'admin'),
    ];
}

function isAdminLoggedIn(): bool
{
    startSecureSession();

    return !empty($_SESSION['admin_id']);
}

/**
 * Attempts to log an admin in against the admins table.
 * Returns true on success (session is populated), false on failure.
 */
function attemptAdminLogin(string $email, string $password): bool
{
    require_once __DIR__ . '/../../config/database.php';

    $email = trim($email);
    if ($email === '' || $password === '') {
        return false;
    }

    try {
        $connection = getPdoConnection();
        $statement = $connection->prepare(
            'SELECT id, name, email, password_hash, role, is_active
             FROM admins
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $admin = $statement->fetch();
    } catch (Throwable $exception) {
        return false;
    }

    if (!$admin || (int) $admin['is_active'] !== 1) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    startSecureSession();
    session_regenerate_id(true);

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];

    try {
        $updateStatement = $connection->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
        $updateStatement->execute(['id' => $admin['id']]);
    } catch (Throwable $exception) {
        // Non-fatal: login still succeeds even if last_login_at can't be updated.
    }

    return true;
}

function adminLogout(): void
{
    startSecureSession();
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    session_regenerate_id(true);
}
