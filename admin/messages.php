<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$pageTitle = 'Messages';
$search = (string) sanitizeInput($_GET['search'] ?? '');
$status = (string) sanitizeInput($_GET['status'] ?? '');
$statuses = ['new', 'read', 'replied', 'archived'];
$messages = [];
$databaseError = false;
try {
    $conditions = [];
    $parameters = [];
    if ($search !== '') {
        $conditions[] = '(name LIKE :name OR email LIKE :email OR subject LIKE :subject)';
        $value = '%' . $search . '%';
        $parameters = ['name' => $value, 'email' => $value, 'subject' => $value];
    }
    if (in_array($status, $statuses, true)) {
        $conditions[] = 'status = :status';
        $parameters['status'] = $status;
    }
    $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $statement = getPdoConnection()->prepare('SELECT id, name, email, phone, subject, status, created_at FROM contact_messages ' . $where . ' ORDER BY created_at DESC, id DESC');
    $statement->execute($parameters);
    $messages = $statement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = true;
}
$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5"><div class="mb-4"><p class="eyebrow mb-2">Inbox</p><h1 class="section-title mb-2">Messages</h1><p class="text-secondary mb-0">Read and manage customer enquiries.</p></div><?php if ($databaseError): ?><div class="alert alert-warning">Messages are temporarily unavailable. Please check the database connection.</div><?php else: ?><form class="bg-white border rounded p-3 mb-4" method="get" action="<?= e(url('admin/messages.php')) ?>"><div class="row g-3 align-items-end"><div class="col-lg-7"><label class="form-label" for="search">Search messages</label><input class="form-control" type="search" id="search" name="search" value="<?= e($search) ?>" placeholder="Name, email, or subject"></div><div class="col-lg-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach ($statuses as $option): ?><option value="<?= e($option) ?>"<?= $status === $option ? ' selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div><div class="col-lg-2 d-flex gap-2"><button class="btn btn-dark flex-grow-1" type="submit">Search</button><a class="btn btn-outline-secondary" href="<?= e(url('admin/messages.php')) ?>">Clear</a></div></div></form><div class="bg-white border rounded overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>From</th><th>Subject</th><th>Status</th><th>Date</th><th class="text-end">Action</th></tr></thead><tbody><?php if ($messages === []): ?><tr><td colspan="5" class="text-center text-secondary py-5">No messages found.</td></tr><?php else: ?><?php foreach ($messages as $message): ?><tr><td><strong><?= e($message['name']) ?></strong><small class="d-block text-secondary"><?= e($message['email']) ?></small><small class="d-block text-secondary"><?= e($message['phone'] ?: '—') ?></small></td><td><?= e($message['subject']) ?></td><td><span class="badge <?= $message['status'] === 'replied' ? 'text-bg-success' : ($message['status'] === 'new' ? 'text-bg-warning' : 'text-bg-secondary') ?>"><?= e(ucfirst($message['status'])) ?></span></td><td class="text-secondary"><?= e(date('d M Y', strtotime($message['created_at']))) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(url('admin/message-view.php?id=' . (int) $message['id'])) ?>">View</a></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div><?php endif; ?></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
