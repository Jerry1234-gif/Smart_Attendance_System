<?php
require_once __DIR__ . '/../includes/guard.php';
$student = require_role('student');
$stmt = db()->prepare('SELECT ar.checked_in_at,ar.method,s.session_title,s.starts_at,c.course_code,c.course_name,u.full_name AS lecturer_name FROM attendance_records ar JOIN attendance_sessions s ON s.id=ar.session_id JOIN courses c ON c.id=s.course_id JOIN users u ON u.id=s.lecturer_id WHERE ar.student_id=? ORDER BY ar.checked_in_at DESC');
$stmt->execute([(int)$student['id']]);
$rows = $stmt->fetchAll();
render_header('My attendance history', 'A record of attendance sessions you successfully checked into.');
?>
<section class="panel"><div class="panel-heading"><div><h2>Recorded attendance</h2><p><?= count($rows) ?> successful check-ins</p></div></div><?php if (!$rows): ?><p class="empty-state">You do not have any recorded attendance yet. Your check-ins will appear here after your lecturer opens a session.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Course</th><th>Class session</th><th>Lecturer</th><th>Check-in time</th><th>Method</th><th>Status</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['course_code']) ?></strong><small class="table-subtext"><?= e($r['course_name']) ?></small></td><td><?= e($r['session_title']) ?></td><td><?= e($r['lecturer_name']) ?></td><td><?= e(format_datetime($r['checked_in_at'])) ?></td><td><?= e(strtoupper($r['method'])) ?></td><td><?= status_badge('present') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php render_footer(); ?>
