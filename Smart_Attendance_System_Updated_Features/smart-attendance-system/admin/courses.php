<?php
require_once __DIR__ . '/../includes/guard.php';
$admin = require_role('admin');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim((string)($_POST['course_code'] ?? '')));
    $name = trim((string)($_POST['course_name'] ?? ''));
    $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
    $check = db()->prepare("SELECT id FROM users WHERE id=? AND role='lecturer' AND is_active=1");
    $check->execute([$lecturerId]);
    if ($code === '' || $name === '' || !$check->fetchColumn()) $error = 'Enter a course code, course name and select an active lecturer.';
    else {
        try {
            $stmt = db()->prepare('INSERT INTO courses (course_code,course_name,lecturer_id) VALUES (?,?,?)');
            $stmt->execute([$code,$name,$lecturerId]);
            set_flash('success', 'Course created. Add the enrolled students next.');
            redirect('admin/courses.php');
        } catch (PDOException $exception) {
            $error = $exception->getCode()==='23000' ? 'That course code already exists.' : 'The course could not be created.';
        }
    }
}
$lecturers = db()->query("SELECT id,full_name FROM users WHERE role='lecturer' AND is_active=1 ORDER BY full_name")->fetchAll();
$courses = db()->query('SELECT c.id,c.course_code,c.course_name,u.full_name AS lecturer_name,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS student_count FROM courses c JOIN users u ON u.id=c.lecturer_id ORDER BY c.course_code')->fetchAll();
render_header('Manage courses', 'Create courses and assign each course to the lecturer responsible for it.');
?>
<div class="content-grid two-col align-start"><section class="panel"><h2>Create a course</h2><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$lecturers): ?><div class="alert alert-warning">Create at least one active lecturer account before adding a course.</div><?php endif; ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Course code<input name="course_code" required maxlength="30" placeholder="e.g. DTECH206"></label><label>Course name<input name="course_name" required maxlength="150" placeholder="Human Computer Interaction"></label><label>Assigned lecturer<select name="lecturer_id" required><option value="">Select lecturer</option><?php foreach ($lecturers as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['full_name']) ?></option><?php endforeach; ?></select></label><button class="button button-primary" type="submit" <?= !$lecturers?'disabled':'' ?>>Create course</button></form></section><section class="panel"><h2>How course setup works</h2><ol class="number-list"><li>Enter the official course code and name.</li><li>Assign one active lecturer.</li><li>Use the Enrol students link to add students.</li><li>The lecturer can then open attendance sessions for that course.</li></ol></section></div>
<section class="panel"><div class="panel-heading"><div><h2>Course list</h2><p><?= count($courses) ?> courses created</p></div><a class="button button-small button-outline" href="<?= e(app_url('admin/enrollments.php')) ?>">Manage enrolments</a></div><?php if (!$courses): ?><p class="empty-state">No courses have been created yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Code</th><th>Course name</th><th>Lecturer</th><th>Enrolled students</th><th></th></tr></thead><tbody><?php foreach ($courses as $c): ?><tr><td><strong><?= e($c['course_code']) ?></strong></td><td><?= e($c['course_name']) ?></td><td><?= e($c['lecturer_name']) ?></td><td><?= (int)$c['student_count'] ?></td><td><a class="text-link" href="<?= e(app_url('admin/enrollments.php?course_id='.(int)$c['id'])) ?>">Enrol students →</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php render_footer(); ?>
