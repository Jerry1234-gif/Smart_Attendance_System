<?php
require_once __DIR__ . '/../includes/guard.php';
$lecturer = require_role('lecturer');
close_expired_sessions();
$stmt = db()->prepare('SELECT s.id,s.session_title,s.starts_at,s.ends_at,s.status,c.course_code,c.course_name,(SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id=s.id) AS present_count,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=s.course_id) AS enrolled_count FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.lecturer_id=? ORDER BY s.starts_at DESC');
$stmt->execute([(int)$lecturer['id']]);
$rows = $stmt->fetchAll();
render_header('Attendance reports', 'Review past sessions and export their attendance lists.');
?>
<section class="panel"><div class="panel-heading"><div><h2>Session summary</h2><p><?= count($rows) ?> sessions across your courses</p></div></div><?php if (!$rows): ?><p class="empty-state">No attendance data is available yet. Create a session from your dashboard.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Session</th><th>Course</th><th>Date</th><th>Present</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['session_title']) ?></strong></td><td><?= e($r['course_code']) ?></td><td><?= e(format_datetime($r['starts_at'],'d M Y, h:i A')) ?></td><td><?= (int)$r['present_count'] ?> / <?= (int)$r['enrolled_count'] ?></td><td><?= status_badge($r['status']) ?></td><td><a class="text-link" href="<?= e(app_url('lecturer/session.php?id='.(int)$r['id'])) ?>">View</a> · <a class="text-link" href="<?= e(app_url('lecturer/export_csv.php?session_id='.(int)$r['id'])) ?>">CSV</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php render_footer(); ?>
