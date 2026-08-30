<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Newsletter';
$search = (string) sanitizeInput($_GET['search'] ?? '');
$activeFilter = (string) sanitizeInput($_GET['active'] ?? '');
$subscribers = [];
$databaseError = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $subscriberId = filter_var($_POST['subscriber_id'] ?? null, FILTER_VALIDATE_INT);
    $action = sanitizeInput($_POST['action'] ?? '');
    if ($subscriberId === false || $subscriberId < 1 || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlashMessage('danger', 'The subscriber action was invalid or expired.');
    } elseif (in_array($action, ['unsubscribe', 'reactivate', 'delete'], true)) {
        try {
            $connection = getPdoConnection();
            if ($action === 'delete') {
                $statement = $connection->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');
                $statement->execute(['id' => $subscriberId]);
            } else {
                $statement = $connection->prepare('UPDATE newsletter_subscribers SET is_active = :is_active, unsubscribed_at = :unsubscribed_at WHERE id = :id');
                $active = $action === 'reactivate' ? 1 : 0;
                $statement->execute(['is_active' => $active, 'unsubscribed_at' => $active === 1 ? null : date('Y-m-d H:i:s'), 'id' => $subscriberId]);
            }
            setFlashMessage('success', 'Subscriber updated successfully.');
        } catch (Throwable $exception) {
            setFlashMessage('danger', 'The subscriber could not be updated.');
        }
    }
    redirect(url('admin/newsletter.php'));
}
try {
    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = 'email LIKE :email';
        $parameters['email'] = '%' . $search . '%';
    }
    if (in_array($activeFilter, ['active', 'inactive'], true)) {
        $conditions[] = 'is_active = :is_active';
        $parameters['is_active'] = $activeFilter === 'active' ? 1 : 0;
    }
    $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $statement = getPdoConnection()->prepare('SELECT id, email, is_active, subscribed_at, unsubscribed_at FROM newsletter_subscribers ' . $where . ' ORDER BY subscribed_at DESC, id DESC');
    $statement->execute($parameters);
    $subscribers = $statement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = true;
}
$flashMessages = getFlashMessages();
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5"><div class="mb-4"><p class="eyebrow mb-2">Audience</p><h1 class="section-title mb-2">Newsletter</h1><p class="text-secondary mb-0">Manage people who want to hear from the collection.</p></div><?php foreach ($flashMessages as $message): ?><div class="alert alert-<?= e($message['type'] ?? 'info') ?>"><?= e($message['message'] ?? '') ?></div><?php endforeach; ?><?php if ($databaseError): ?><div class="alert alert-warning">Subscribers are temporarily unavailable. Please check the database connection.</div><?php else: ?><form class="bg-white border rounded p-3 mb-4" method="get" action="<?= e(url('admin/newsletter.php')) ?>"><div class="row g-3 align-items-end"><div class="col-lg-6"><label class="form-label" for="search">Search email</label><input class="form-control" type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="subscriber@example.com"></div><div class="col-lg-3"><label class="form-label" for="active">Status</label><select class="form-select" id="active" name="active"><option value="">All subscribers</option><option value="active"<?= $activeFilter === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $activeFilter === 'inactive' ? ' selected' : '' ?>>Unsubscribed</option></select></div><div class="col-lg-3 d-flex gap-2"><button class="btn btn-dark flex-grow-1" type="submit">Search</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/newsletter.php')) ?>">Clear</a></div></div></form><div class="bg-white border rounded overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Email</th><th>Status</th><th>Subscribed</th><th>Unsubscribed</th><th class="text-end">Actions</th></tr></thead><tbody><?php if ($subscribers === []): ?><tr><td colspan="5" class="text-center text-secondary py-5">No subscribers found.</td></tr><?php else: ?><?php foreach ($subscribers as $subscriber): ?><tr><td><?= e($subscriber['email']) ?></td><td><span class="badge <?= (int) $subscriber['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $subscriber['is_active'] === 1 ? 'Active' : 'Unsubscribed' ?></span></td><td class="text-secondary"><?= e(date('d M Y', strtotime($subscriber['subscribed_at']))) ?></td><td class="text-secondary"><?= $subscriber['unsubscribed_at'] ? e(date('d M Y', strtotime($subscriber['unsubscribed_at']))) : '-' ?></td><td class="text-end"><form method="post" class="d-inline-flex gap-2" action="<?= e(url('admin/newsletter.php')) ?>"><input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><?php if ((int) $subscriber['is_active'] === 1): ?><button class="btn btn-sm btn-outline-dark" name="action" value="unsubscribe" type="submit">Unsubscribe</button><?php else: ?><button class="btn btn-sm btn-outline-success" name="action" value="reactivate" type="submit">Reactivate</button><?php endif; ?><button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit" onclick="return confirm('Delete this subscriber?');">Delete</button></form></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div><?php endif; ?></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
