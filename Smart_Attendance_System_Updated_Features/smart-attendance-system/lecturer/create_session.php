<?php
require_once __DIR__ . '/../includes/guard.php';
$lecturer = require_role('lecturer');
$stmt = db()->prepare('SELECT id,course_code,course_name FROM courses WHERE lecturer_id=? ORDER BY course_code');
$stmt->execute([(int)$lecturer['id']]);
$courses = $stmt->fetchAll();
$selectedId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim((string)($_POST['session_title'] ?? ''));
    $room = trim((string)($_POST['room'] ?? ''));
    $duration = (int)($_POST['duration'] ?? 15);
    if (!lecturer_owns_course((int)$lecturer['id'], $courseId)) $error = 'Select one of your assigned courses.';
    elseif ($title === '') $error = 'Enter a session title, such as Lecture 1 or Practical 2.';
    elseif (!in_array($duration, [5,10,15,20,30,45,60,90,120], true)) $error = 'Choose a valid attendance window.';
    else {
        $start = new DateTimeImmutable('now');
        $end = $start->modify('+' . $duration . ' minutes');
        $stmt = db()->prepare("INSERT INTO attendance_sessions (course_id,lecturer_id,session_title,room,starts_at,ends_at,status) VALUES (?,?,?,?,?,?,'open')");
        $stmt->execute([$courseId,(int)$lecturer['id'],$title,$room ?: null,$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s')]);
        $sessionId = (int)db()->lastInsertId();
        create_session_credentials($sessionId);
        set_flash('success', 'Attendance session created. Display the QR code or share the six-digit code with enrolled students.');
        redirect('lecturer/session.php?id='.$sessionId);
    }
}
render_header('Create attendance session', 'Set the attendance window for a class you teach.');
?>
<div class="content-grid two-col align-start"><section class="panel"><h2>Session details</h2><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$courses): ?><div class="alert alert-warning">No courses are assigned to your account. Ask the administrator to assign a course first.</div><?php else: ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Course<select name="course_id" required><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $selectedId===(int)$c['id']?'selected':'' ?>><?= e($c['course_code'].' — '.$c['course_name']) ?></option><?php endforeach; ?></select></label><label>Session title<input name="session_title" required maxlength="150" placeholder="e.g. Week 4 Lecture"></label><label>Room (optional)<input name="room" maxlength="80" placeholder="e.g. Room B12"></label><label>Attendance window<select name="duration"><option value="5">5 minutes</option><option value="10">10 minutes</option><option value="15" selected>15 minutes</option><option value="20">20 minutes</option><option value="30">30 minutes</option><option value="45">45 minutes</option><option value="60">60 minutes</option><option value="90">90 minutes</option><option value="120">120 minutes</option></select><small class="field-help">The QR code and manual attendance code stop working when this window ends.</small></label><button class="button button-primary" type="submit">Create session and QR code</button></form><?php endif; ?></section><aside class="panel"><h2>What happens next?</h2><div class="step-list"><div><span>1</span><p><strong>Session opens</strong><small>The system creates a unique QR token and a six-digit backup code.</small></p></div><div><span>2</span><p><strong>Students check in</strong><small>Only active students enrolled in the course can record attendance.</small></p></div><div><span>3</span><p><strong>Review the list</strong><small>Attendance records appear on your session page and can be exported as CSV.</small></p></div></div><div class="alert alert-info">Students must be signed in before scanning or entering the code. Each student can check in only once per session.</div></aside></div>
<?php render_footer(); ?>
