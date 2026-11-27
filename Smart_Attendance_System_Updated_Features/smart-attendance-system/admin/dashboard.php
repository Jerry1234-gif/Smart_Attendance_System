<?php
require_once __DIR__ . '/../includes/guard.php';
$admin = require_role('admin');
close_expired_sessions();
$stats = [
    'students' => (int)db()->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1")->fetchColumn(),
    'lecturers' => (int)db()->query("SELECT COUNT(*) FROM users WHERE role='lecturer' AND is_active=1")->fetchColumn(),
    'courses' => (int)db()->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'sessions' => (int)db()->query("SELECT COUNT(*) FROM attendance_sessions WHERE status='open' AND starts_at <= NOW() AND ends_at >= NOW()")->fetchColumn(),
];
$recent = db()->query('SELECT s.id, s.session_title, s.starts_at, s.status, c.course_code, c.course_name, u.full_name AS lecturer_name FROM attendance_sessions s JOIN courses c ON c.id=s.course_id JOIN users u ON u.id=s.lecturer_id ORDER BY s.created_at DESC LIMIT 8')->fetchAll();
render_header('Administrator dashboard', 'Manage user accounts, courses and student enrolments.');
?>
<section class="stats-grid">
<?php foreach ([['Active students',$stats['students'],'◉'],['Active lecturers',$stats['lecturers'],'♙'],['Courses',$stats['courses'],'▤'],['Live sessions',$stats['sessions'],'◷']] as [$label,$value,$icon]): ?><article class="stat-card"><span class="stat-icon"><?= e($icon) ?></span><span class="stat-label"><?= e($label) ?></span><strong><?= e((string)$value) ?></strong></article><?php endforeach; ?>
</section>
<section class="content-grid two-col">
    <article class="panel"><div class="panel-heading"><div><h2>Quick actions</h2><p>Create and organise the learning environment.</p></div></div><div class="action-list"><a href="<?= e(app_url('admin/users.php')) ?>"><span class="action-icon">＋</span><span><strong>Add a user</strong><small>Create a student or lecturer account</small></span><b>→</b></a><a href="<?= e(app_url('admin/courses.php')) ?>"><span class="action-icon">▤</span><span><strong>Create a course</strong><small>Connect a course to its lecturer</small></span><b>→</b></a><a href="<?= e(app_url('admin/enrollments.php')) ?>"><span class="action-icon">♧</span><span><strong>Manage enrolments</strong><small>Add students to their courses</small></span><b>→</b></a></div></article>
    <article class="panel"><div class="panel-heading"><div><h2>System responsibilities</h2><p>Before lecturers can record attendance</p></div></div><ol class="number-list"><li>Create lecturer and student accounts.</li><li>Create courses and assign each lecturer.</li><li>Open each course's enrolment page and add students.</li><li>Give each user their login details securely.</li></ol><div class="alert alert-info">New accounts must change their temporary password at first sign-in.</div></article>
</section>
<section class="panel"><div class="panel-heading"><div><h2>Recently created attendance sessions</h2><p>Latest sessions across the system</p></div></div><?php if (!$recent): ?><p class="empty-state">No attendance sessions have been created yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Session</th><th>Course</th><th>Lecturer</th><th>Created for</th><th>Status</th></tr></thead><tbody><?php foreach ($recent as $row): ?><tr><td><?= e($row['session_title']) ?></td><td><?= e($row['course_code']) ?> · <?= e($row['course_name']) ?></td><td><?= e($row['lecturer_name']) ?></td><td><?= e(format_datetime($row['starts_at'])) ?></td><td><?= status_badge($row['status']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php render_footer(); ?>
