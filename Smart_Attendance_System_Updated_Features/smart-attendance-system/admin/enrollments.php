<?php
require_once __DIR__ . '/../includes/guard.php';
require_role('admin');
$courses = db()->query('SELECT id,course_code,course_name FROM courses ORDER BY course_code')->fetchAll();
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? ($courses[0]['id'] ?? 0));
$error = '';
$selectedCourse = null;
foreach ($courses as $course) if ((int)$course['id'] === $courseId) $selectedCourse = $course;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'add');
    $studentId = (int)($_POST['student_id'] ?? 0);
    if (!$selectedCourse) $error = 'Select a valid course.';
    elseif ($action === 'remove') {
        $stmt = db()->prepare('DELETE FROM enrollments WHERE course_id=? AND student_id=?');
        $stmt->execute([$courseId,$studentId]);
        set_flash('success','Student removed from the course.');
        redirect('admin/enrollments.php?course_id='.$courseId);
    } else {
        $check = db()->prepare("SELECT id FROM users WHERE id=? AND role='student' AND is_active=1");
        $check->execute([$studentId]);
        if (!$check->fetchColumn()) $error = 'Choose an active student account.';
        else {
            try {
                $stmt = db()->prepare('INSERT INTO enrollments (course_id,student_id) VALUES (?,?)');
                $stmt->execute([$courseId,$studentId]);
                set_flash('success','Student enrolled in the course.');
                redirect('admin/enrollments.php?course_id='.$courseId);
            } catch (PDOException $exception) { $error = 'This student is already enrolled in the selected course.'; }
        }
    }
}
$students = db()->query("SELECT id,full_name,student_number,email FROM users WHERE role='student' AND is_active=1 ORDER BY full_name")->fetchAll();
$enrolled = [];
if ($selectedCourse) {
    $stmt = db()->prepare('SELECT u.id,u.full_name,u.student_number,u.email,e.enrolled_at FROM enrollments e JOIN users u ON u.id=e.student_id WHERE e.course_id=? ORDER BY u.full_name');
    $stmt->execute([$courseId]);
    $enrolled = $stmt->fetchAll();
}
render_header('Manage enrolments', 'Choose a course and connect its registered students to it.');
?>
<section class="panel"><form method="get" class="filter-form"><label>Choose course<select name="course_id" onchange="this.form.submit()"><option value="">Select course</option><?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $courseId===(int)$c['id']?'selected':'' ?>><?= e($c['course_code'].' — '.$c['course_name']) ?></option><?php endforeach; ?></select></label><noscript><button class="button button-primary" type="submit">Open course</button></noscript></form></section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!$courses): ?><section class="panel empty-state">Create a course first on the Courses page.</section><?php elseif ($selectedCourse): ?>
<section class="content-grid two-col align-start"><article class="panel"><h2>Add student to <?= e($selectedCourse['course_code']) ?></h2><?php if (!$students): ?><p class="empty-state">Create student accounts before enrolling students.</p><?php else: ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="course_id" value="<?= $courseId ?>"><label>Student<select name="student_id" required><option value="">Select student</option><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name'].' · '.$s['student_number']) ?></option><?php endforeach; ?></select></label><button class="button button-primary" type="submit">Enrol student</button></form><?php endif; ?></article><article class="panel"><h2>Course enrolment</h2><p class="muted">Students enrolled in this course can check in to its attendance sessions.</p><div class="metric-large"><?= count($enrolled) ?><small>enrolled students</small></div></article></section>
<section class="panel"><div class="panel-heading"><div><h2>Enrolled students</h2><p><?= e($selectedCourse['course_code']) ?> — <?= e($selectedCourse['course_name']) ?></p></div></div><?php if (!$enrolled): ?><p class="empty-state">No students have been enrolled yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Name</th><th>Student number</th><th>Email</th><th>Action</th></tr></thead><tbody><?php foreach ($enrolled as $s): ?><tr><td><?= e($s['full_name']) ?></td><td><?= e($s['student_number']) ?></td><td><?= e($s['email']) ?></td><td><form method="post" data-confirm="Remove this student from this course?" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="course_id" value="<?= $courseId ?>"><input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>"><button class="button button-small button-danger-outline" type="submit">Remove</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php render_footer(); ?>
