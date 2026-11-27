<?php
require_once __DIR__ . '/includes/guard.php';
$student = require_role('student');
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = trim((string)($_POST['attendance_code'] ?? ''));
    if (!preg_match('/^[0-9]{6}$/', $code)) {
        $result = ['ok'=>false,'message'=>'Enter the six-digit code exactly as shown by your lecturer.'];
    } else {
        $stmt = db()->query("SELECT s.*,c.course_code,c.course_name FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.status='open' AND s.starts_at<=NOW() AND s.ends_at>=NOW() AND s.attendance_code_hash IS NOT NULL ORDER BY s.created_at DESC");
        $active = $stmt->fetchAll();
        $matched = null;
        $submittedHash = code_hash($code);
        foreach ($active as $candidate) {
            if (hash_equals((string)$candidate['attendance_code_hash'], $submittedHash)) { $matched = $candidate; break; }
        }
        if (!$matched) $result = ['ok'=>false,'message'=>'That code is invalid, expired or the attendance session has closed.'];
        else $result = record_student_attendance($matched, (int)$student['id'], 'code');
    }
} else {
    $token = (string)($_GET['token'] ?? '');
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        $result = ['ok'=>false,'message'=>'The QR check-in link is not valid. Scan the current code displayed by your lecturer.'];
    } else {
        $stmt = db()->prepare("SELECT s.*,c.course_code,c.course_name FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.qr_token_hash=? AND s.status='open' AND s.starts_at<=NOW() AND s.ends_at>=NOW() LIMIT 1");
        $stmt->execute([hash('sha256', $token)]);
        $matched = $stmt->fetch();
        if (!$matched) $result = ['ok'=>false,'message'=>'This QR code is invalid or expired. Ask your lecturer to display the current attendance QR code.'];
        else $result = record_student_attendance($matched, (int)$student['id'], 'qr');
    }
}
render_header('Attendance check-in', 'Result of your attendance request.');
?>
<section class="result-card <?= $result['ok']?'result-success':'result-error' ?>"><div class="result-icon"><?= $result['ok']?'✓':'!' ?></div><h2><?= $result['ok']?'Check-in successful':'Check-in not completed' ?></h2><p><?= e($result['message']) ?></p><?php if (!empty($matched)): ?><div class="result-course"><strong><?= e($matched['course_code']) ?> · <?= e($matched['course_name']) ?></strong><small><?= e($matched['session_title']) ?></small></div><?php endif; ?><a class="button button-primary" href="<?= e(app_url('student/dashboard.php')) ?>">Return to student dashboard</a><a class="text-link" href="<?= e(app_url('student/history.php')) ?>">View attendance history</a></section>
<?php render_footer(); ?>
