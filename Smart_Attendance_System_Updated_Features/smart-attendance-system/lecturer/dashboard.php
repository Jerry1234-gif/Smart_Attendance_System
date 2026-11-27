<?php
require_once __DIR__ . '/../includes/guard.php';
$lecturer = require_role('lecturer');
close_expired_sessions();
$stmt = db()->prepare('SELECT c.id,c.course_code,c.course_name,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS student_count,(SELECT COUNT(*) FROM attendance_sessions s WHERE s.course_id=c.id) AS session_count FROM courses c WHERE c.lecturer_id=? ORDER BY c.course_code');
$stmt->execute([(int)$lecturer['id']]);
$courses = $stmt->fetchAll();
$stmt = db()->prepare('SELECT s.id,s.session_title,s.starts_at,s.ends_at,s.status,c.course_code,c.course_name,(SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id=s.id) AS attendance_count FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.lecturer_id=? ORDER BY s.created_at DESC LIMIT 10');
$stmt->execute([(int)$lecturer['id']]);
$sessions = $stmt->fetchAll();
render_header('Lecturer dashboard', 'Open a class attendance session and review student check-ins.');
?>
<section class="welcome-banner"><div><span class="pill pill-light">LECTURER WORKSPACE</span><h2>Welcome, <?= e($lecturer['full_name']) ?>.</h2><p>Create a session for one of your courses, then display the QR code or share the attendance code with enrolled students.</p></div><div class="banner-art">▦</div></section>
<section class="panel"><div class="panel-heading"><div><h2>Your courses</h2><p>Courses assigned to your account</p></div></div><?php if (!$courses): ?><p class="empty-state">No courses are assigned to you yet. Ask the administrator to create a course and assign it to your lecturer account.</p><?php else: ?><div class="course-grid"><?php foreach ($courses as $c): ?><article class="course-card"><div class="course-code"><?= e($c['course_code']) ?></div><h3><?= e($c['course_name']) ?></h3><div class="course-meta"><span><?= (int)$c['student_count'] ?> students</span><span><?= (int)$c['session_count'] ?> sessions</span></div><a class="button button-primary button-wide" href="<?= e(app_url('lecturer/create_session.php?course_id='.(int)$c['id'])) ?>">Open attendance session</a></article><?php endforeach; ?></div><?php endif; ?></section>
<section class="panel"><div class="panel-heading"><div><h2>Recent attendance sessions</h2><p>Open a session to view the QR code and attendance list.</p></div><a class="text-link" href="<?= e(app_url('lecturer/reports.php')) ?>">All reports →</a></div><?php if (!$sessions): ?><p class="empty-state">You have not created an attendance session yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Session</th><th>Course</th><th>Time</th><th>Attendance</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($sessions as $s): ?><tr><td><strong><?= e($s['session_title']) ?></strong></td><td><?= e($s['course_code']) ?></td><td><?= e(format_datetime($s['starts_at'])) ?></td><td><?= (int)$s['attendance_count'] ?></td><td><?= status_badge($s['status']) ?></td><td><a class="text-link" href="<?= e(app_url('lecturer/session.php?id='.(int)$s['id'])) ?>">View →</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php render_footer(); ?>
