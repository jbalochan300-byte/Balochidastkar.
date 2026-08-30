<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdmin = requireAdminLogin();

$messageId = filter_var($_GET['id'] ?? $_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
$message = null;
$errors = [];
$databaseError = false;
try {
    if ($messageId !== false && $messageId > 0) {
        $statement = getPdoConnection()->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $messageId]);
        $message = $statement->fetch();
    }
} catch (Throwable $exception) {
    $databaseError = true;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && is_array($message)) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        if ($action === 'delete') {
            try {
                $statement = getPdoConnection()->prepare('DELETE FROM contact_messages WHERE id = :id');
                $statement->execute(['id' => $messageId]);
                setFlashMessage('success', 'Message deleted.');
                redirect(url('admin/messages.php'));
            } catch (Throwable $exception) {
                $errors['form'] = 'The message could not be deleted.';
            }
        } elseif (in_array($action, ['read', 'replied'], true)) {
            try {
                $statement = getPdoConnection()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
                $statement->execute(['status' => $action, 'id' => $messageId]);
                setFlashMessage('success', 'Message marked as ' . $action . '.');
                redirect(url('admin/message-view.php?id=' . (int) $messageId));
            } catch (Throwable $exception) {
                $errors['form'] = 'The message status could not be updated.';
            }
        } elseif ($action === 'reply') {
            $replyText = trim((string) ($_POST['admin_reply'] ?? ''));
            if ($replyText === '') {
                $errors['form'] = 'Please write a reply before saving it.';
            } else {
                try {
                    $statement = getPdoConnection()->prepare(
                        "UPDATE contact_messages SET admin_reply = :reply, replied_at = NOW(), status = 'replied' WHERE id = :id"
                    );
                    $statement->execute(['reply' => $replyText, 'id' => $messageId]);
                    setFlashMessage('success', 'Reply saved. Use the button below to send it from your own email.');
                    redirect(url('admin/message-view.php?id=' . (int) $messageId));
                } catch (Throwable $exception) {
                    $errors['form'] = 'The reply could not be saved.';
                }
            }
        }
    }
}
$pageTitle = 'Message Details';
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>
<div class="container-fluid p-4 p-lg-5"><div class="d-flex justify-content-between align-items-end gap-3 mb-4"><div><p class="eyebrow mb-2">Inbox</p><h1 class="section-title mb-2">Message details</h1></div><a class="btn btn-outline-secondary" href="<?= e(url('admin/messages.php')) ?>">Back to messages</a></div><?php if ($databaseError): ?><div class="alert alert-warning">Message details are temporarily unavailable.</div><?php elseif (!is_array($message)): ?><div class="alert alert-danger">The requested message could not be found.</div><?php else: ?><?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?><div class="row g-4"><div class="col-lg-8"><section class="bg-white border rounded p-4"><div class="d-flex justify-content-between gap-3 mb-4"><div><p class="eyebrow mb-1">Subject</p><h2 class="h3"><?= e($message['subject']) ?></h2></div><span class="badge text-bg-secondary align-self-start"><?= e(ucfirst($message['status'])) ?></span></div><p class="mb-0" style="white-space: pre-wrap;"><?= e($message['message']) ?></p></section>
<section class="bg-white border rounded p-4 mt-4"><h2 class="h5 mb-3">Your reply</h2><?php if (!empty($message['admin_reply'])): ?><div class="alert alert-light border mb-3"><p class="small text-secondary mb-1">Saved reply<?= !empty($message['replied_at']) ? ' — ' . e(date('M j, Y g:ia', strtotime((string) $message['replied_at']))) : '' ?></p><p class="mb-0" style="white-space: pre-wrap;"><?= e($message['admin_reply']) ?></p></div><?php endif; ?><form method="post" action="<?= e(url('admin/message-view.php?id=' . (int) $messageId)) ?>"><input type="hidden" name="message_id" value="<?= (int) $messageId ?>"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="reply"><div class="mb-3"><label class="form-label" for="admin_reply">Write your reply</label><textarea class="form-control" id="admin_reply" name="admin_reply" rows="5" placeholder="Type what you want to say back to this customer..."><?= e($message['admin_reply'] ?? '') ?></textarea></div><div class="d-flex flex-wrap gap-2"><button class="btn btn-dark" type="submit">Save reply</button><button class="btn btn-outline-dark" type="button" id="openEmailAppBtn" data-to="<?= e($message['email']) ?>" data-subject="<?= e('Re: ' . ($message['subject'] ?: 'Your message to ' . APP_NAME)) ?>">Send by email</button></div><p class="small text-secondary mt-2 mb-0">"Save reply" keeps a record here. "Send by email" opens your own email app (Gmail, Outlook, etc.) with the customer's address, subject, and your reply already filled in — just hit send from there.</p></form></section>
</div><aside class="col-lg-4"><section class="bg-white border rounded p-4 mb-4"><h2 class="h5 mb-3">Sender</h2><p class="mb-1"><strong><?= e($message['name']) ?></strong></p><p class="small text-secondary mb-1"><?= e($message['email']) ?></p><p class="small text-secondary mb-0"><?= e($message['phone'] ?: 'No phone provided') ?></p></section><section class="bg-white border rounded p-4"><h2 class="h5 mb-3">Actions</h2><form method="post" action="<?= e(url('admin/message-view.php?id=' . (int) $messageId)) ?>" class="d-grid gap-2"><input type="hidden" name="message_id" value="<?= (int) $messageId ?>"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-outline-dark" name="action" value="read" type="submit">Mark read</button><button class="btn btn-outline-success" name="action" value="replied" type="submit">Mark replied</button><button class="btn btn-outline-danger" name="action" value="delete" type="submit" onclick="return confirm('Delete this message?');">Delete message</button></form></section></aside></div><?php endif; ?></div>
<script>
(function () {
    var openEmailBtn = document.getElementById('openEmailAppBtn');
    if (!openEmailBtn) { return; }
    openEmailBtn.addEventListener('click', function () {
        var to = openEmailBtn.getAttribute('data-to') || '';
        var subject = openEmailBtn.getAttribute('data-subject') || '';
        var body = document.getElementById('admin_reply') ? document.getElementById('admin_reply').value : '';
        var mailto = 'mailto:' + encodeURIComponent(to)
            + '?subject=' + encodeURIComponent(subject)
            + '&body=' + encodeURIComponent(body);
        window.location.href = mailto;
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
