<?php

declare(strict_types=1);

// ONE-TIME USE TOOL — fixes an admin account whose password_hash column
// was set to plain text instead of a real hash. Delete this file once
// you've used it; it is NOT protected by admin login (you can't log in
// yet, that's the whole problem) and should never stay on a public site.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$message = null;
$error = null;
$admins = [];

try {
    $connection = getPdoConnection();
    $admins = $connection->query('SELECT id, name, email FROM admins ORDER BY id ASC')->fetchAll();
} catch (Throwable $exception) {
    $error = 'Could not connect to the database: ' . $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $adminId = (int) ($_POST['admin_id'] ?? 0);
    $newPassword = (string) ($_POST['password'] ?? '');

    if ($adminId <= 0) {
        $error = 'Please choose an admin account.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password should be at least 6 characters.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        try {
            $statement = $connection->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
            $statement->execute(['hash' => $hash, 'id' => $adminId]);

            if ($statement->rowCount() > 0) {
                $message = 'Password updated. You can log in now — then delete this file.';
            } else {
                $error = 'That admin ID no longer exists — refresh the page and try again.';
            }
        } catch (Throwable $exception) {
            $error = 'Database error: ' . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fix admin password</title>
<style>
    body { font-family: sans-serif; max-width: 460px; margin: 60px auto; padding: 0 20px; }
    select, input { display: block; width: 100%; padding: 8px; margin: 6px 0 16px; box-sizing: border-box; }
    button { padding: 10px 20px; background: #1c130d; color: #fff; border: none; cursor: pointer; }
    .ok { background: #d7f5df; padding: 12px; border-radius: 4px; }
    .err { background: #fbdede; padding: 12px; border-radius: 4px; }
    .warn { color: #a33; font-size: .85rem; margin-top: 30px; }
    .empty { background: #fff3cd; padding: 12px; border-radius: 4px; }
</style>
</head>
<body>
<h2>Fix admin password</h2>
<p>Pick the admin account and the password you want to use. This hashes it correctly and updates the database — no typing the email required.</p>

<?php if ($message): ?><p class="ok"><?= $message ?></p><?php endif; ?>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<?php if ($error === null && $admins === []): ?>
    <p class="empty">No rows found in the <code>admins</code> table at all. Double check you're editing the same database this site actually connects to (check <code>config/config.php</code> for <code>DB_NAME</code>) — it's easy to have two similarly-named databases in phpMyAdmin by mistake.</p>
<?php elseif ($admins !== []): ?>
<form method="post">
    <label>Admin account</label>
    <select name="admin_id" required>
        <?php foreach ($admins as $admin): ?>
            <option value="<?= (int) $admin['id'] ?>">#<?= (int) $admin['id'] ?> — <?= htmlspecialchars($admin['name']) ?> (<?= htmlspecialchars($admin['email']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <label>New password</label>
    <input type="text" name="password" placeholder="e.g. baloch211" required>
    <button type="submit">Fix password</button>
</form>
<?php endif; ?>

<p class="warn">⚠️ Delete this file (admin/reset-admin-password.php) as soon as you're done — anyone who finds this URL could reset your admin password.</p>
</body>
</html>
