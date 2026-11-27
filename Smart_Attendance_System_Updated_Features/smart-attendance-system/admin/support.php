<?php
require_once __DIR__ . '/../includes/guard.php';
require_role('admin');
// Ensure the table exists if the administrator visits the inbox before the public support page.
db()->exec("CREATE TABLE IF NOT EXISTS support_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_support_status_date (status, created_at), KEY idx_support_user (user_id),
    CONSTRAINT fk_support_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['request_id'] ?? 0);
    $status = (string)($_POST['status'] ?? 'open');
    if (!in_array($status, ['open','in_progress','resolved'], true)) $error = 'Choose a valid request status.';
    else {
        $stmt = db()->prepare('UPDATE support_requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        set_flash('success', 'Support request status updated.');
        redirect('admin/support.php');
    }
}
$requests = db()->query('SELECT id,full_name,email,subject,message,status,created_at FROM support_requests ORDER BY FIELD(status,\'open\',\'in_progress\',\'resolved\'), created_at DESC')->fetchAll();
render_header('Support inbox', 'Review questions and support requests submitted by users.');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<section class="panel"><div class="panel-heading"><div><h2>Support requests</h2><p><?= count($requests) ?> request(s)</p></div></div>
<?php if (!$requests): ?><p class="muted">No support requests have been submitted yet.</p><?php else: ?>
<div class="support-request-list">
<?php foreach ($requests as $request): ?>
    <article class="support-request"><div class="support-request-top"><div><strong><?= e($request['subject']) ?></strong><p><?= e($request['full_name']) ?> · <a href="mailto:<?= e($request['email']) ?>"><?= e($request['email']) ?></a> · <?= e(format_datetime($request['created_at'])) ?></p></div><?= status_badge($request['status']) ?></div>
    <p class="support-request-message"><?= nl2br(e($request['message'])) ?></p>
    <form method="post" class="support-status-form"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><label>Status<select name="status"><option value="open" <?= $request['status']==='open'?'selected':'' ?>>Open</option><option value="in_progress" <?= $request['status']==='in_progress'?'selected':'' ?>>In progress</option><option value="resolved" <?= $request['status']==='resolved'?'selected':'' ?>>Resolved</option></select></label><button class="button button-outline button-small" type="submit">Update status</button></form></article>
<?php endforeach; ?>
</div><?php endif; ?></section>
<?php render_footer(); ?>
