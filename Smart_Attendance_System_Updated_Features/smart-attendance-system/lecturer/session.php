<?php
require_once __DIR__ . '/../includes/guard.php';
$lecturer = require_role('lecturer');
close_expired_sessions();
$sessionId = (int)($_GET['id'] ?? $_POST['session_id'] ?? 0);
if (!lecturer_owns_session((int)$lecturer['id'], $sessionId)) { http_response_code(404); exit('Attendance session not found.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'close') {
        $stmt = db()->prepare("UPDATE attendance_sessions SET status='closed' WHERE id=? AND lecturer_id=?");
        $stmt->execute([$sessionId,(int)$lecturer['id']]);
        unset($_SESSION['session_secrets'][$sessionId]);
        set_flash('success', 'Attendance session closed. The QR code and attendance code will no longer work.');
        redirect('lecturer/session.php?id='.$sessionId);
    }
    if ($action === 'regenerate') {
        $stmt = db()->prepare("SELECT id FROM attendance_sessions WHERE id=? AND status='open' AND starts_at<=NOW() AND ends_at>=NOW()");
        $stmt->execute([$sessionId]);
        if ($stmt->fetchColumn()) {
            create_session_credentials($sessionId);
            set_flash('success', 'A new QR token and attendance code have been generated. The previous pair is invalid.');
        } else set_flash('warning', 'This session is not currently open, so its code cannot be renewed.');
        redirect('lecturer/session.php?id='.$sessionId);
    }
}
$stmt = db()->prepare('SELECT s.*,c.course_code,c.course_name FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.id=? AND s.lecturer_id=?');
$stmt->execute([$sessionId,(int)$lecturer['id']]);
$session = $stmt->fetch();
if (!$session) { http_response_code(404); exit('Attendance session not found.'); }
$isLive = $session['status']==='open' && strtotime($session['starts_at']) <= time() && strtotime($session['ends_at']) >= time();
$credentials = null;
if ($isLive) $credentials = get_or_create_session_credentials($sessionId);
$stmt = db()->prepare('SELECT u.full_name,u.student_number,u.email,ar.checked_in_at,ar.method FROM attendance_records ar JOIN users u ON u.id=ar.student_id WHERE ar.session_id=? ORDER BY ar.checked_in_at');
$stmt->execute([$sessionId]);
$records = $stmt->fetchAll();
$qrUrl = $credentials ? app_url('checkin.php?token=' . rawurlencode($credentials['token'])) : '';
render_header('Attendance session', $session['course_code'].' · '.$session['course_name']);
?>
<div class="session-overview"><div><span class="eyebrow">SESSION</span><h2><?= e($session['session_title']) ?></h2><p><?= e($session['room'] ?: 'Room not specified') ?> · Started <?= e(format_datetime($session['starts_at'],'d M Y, h:i A')) ?></p></div><div class="session-status"><?= status_badge($isLive?'active':$session['status']) ?><strong><?= count($records) ?></strong><small>students checked in</small></div></div>
<?php if ($isLive && $credentials): ?>
<section class="content-grid two-col align-start"><article class="panel qr-panel"><div class="panel-heading"><div><h2>Student QR code</h2><p>Students scan this code after signing in.</p></div><span class="live-indicator">● LIVE</span></div><div id="session-qr" class="qr-box" data-qr-url="<?= e($qrUrl) ?>"><span class="muted">Loading QR code…</span></div><p class="qr-url"><small>QR destination</small><span><?= e($qrUrl) ?></span></p><p class="tiny-note">Keep this QR code visible during the attendance window. Regenerating it invalidates the previous QR and manual code.</p></article>
<article class="panel code-panel"><span class="eyebrow">BACKUP CHECK-IN</span><h2>Six-digit attendance code</h2><div class="attendance-code" aria-label="Attendance code"><?= e($credentials['code']) ?></div><p class="muted">Students can enter this code from their dashboard when the camera cannot scan the QR.</p><div class="session-facts"><div><small>Attendance closes</small><strong><?= e(format_datetime($session['ends_at'],'h:i A')) ?></strong></div><div><small>Room</small><strong><?= e($session['room'] ?: 'Not set') ?></strong></div></div><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="regenerate"><button class="button button-outline button-wide" type="submit" data-confirm="Regenerate the QR and manual code? The old code will stop working.">Regenerate QR and code</button></form></article></section>
<?php else: ?><div class="alert alert-warning">This session is not accepting attendance now. It may have expired or been closed. Students cannot use its previous QR code or manual code.</div><?php endif; ?>
<section class="panel"><div class="panel-heading"><div><h2>Attendance records</h2><p>Each enrolled student can appear only once for this session.</p></div><div class="panel-actions"><a class="button button-small button-outline" href="<?= e(app_url('lecturer/export_csv.php?session_id='.$sessionId)) ?>">Download CSV</a><?php if ($session['status']==='open'): ?><form method="post" class="inline-form" data-confirm="Close this attendance session now? Students will no longer be able to check in."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>"><input type="hidden" name="action" value="close"><button class="button button-small button-danger" type="submit">Close session</button></form><?php endif; ?></div></div><?php if (!$records): ?><p class="empty-state">No students have checked in yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>#</th><th>Student</th><th>Student number</th><th>Check-in time</th><th>Method</th></tr></thead><tbody><?php foreach ($records as $i=>$r): ?><tr><td><?= $i+1 ?></td><td><strong><?= e($r['full_name']) ?></strong></td><td><?= e($r['student_number'] ?: '—') ?></td><td><?= e(format_datetime($r['checked_in_at'])) ?></td><td><?= e(strtoupper($r['method'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<script src="<?= e(app_config('qr_library_url')) ?>"></script><script>
document.addEventListener('DOMContentLoaded', function () {
 const box = document.getElementById('session-qr');
 if (!box) return;
 const url = box.dataset.qrUrl;
 if (typeof QRCode === 'undefined') { box.textContent = 'The QR library could not load. Share the six-digit code instead.'; return; }
 box.innerHTML = '';
 new QRCode(box, {text:url, width:240, height:240, colorDark:'#102c25', colorLight:'#ffffff', correctLevel:QRCode.CorrectLevel.M});
});
</script>
<?php render_footer(); ?>
