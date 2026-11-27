<?php
require_once __DIR__ . '/../includes/guard.php';
$admin = require_role('admin');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'create');
    if ($action === 'toggle') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === (int)$admin['id']) $error = 'You cannot disable your own administrator account.';
        else {
            $stmt = db()->prepare('UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id = ? AND role <> \'admin\'');
            $stmt->execute([$id]);
            set_flash('success', 'User account status updated.');
            redirect('admin/users.php');
        }
    } else {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $role = (string)($_POST['role'] ?? 'student');
        $studentNumber = trim((string)($_POST['student_number'] ?? ''));
        $password = (string)($_POST['temporary_password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a name and valid email address.';
        elseif (!in_array($role, ['student','lecturer'], true)) $error = 'Choose either student or lecturer.';
        elseif ($role === 'student' && $studentNumber === '') $error = 'A student number is required for student accounts.';
        elseif ($role === 'student' && !is_valid_student_number($studentNumber)) $error = 'Invalid Limkokwing student ID. It must start with 90500 and contain digits only after the prefix (example: 905001234).';
        elseif (strlen($password) < 8) $error = 'Temporary password must contain at least 8 characters.';
        else {
            try {
                $stmt = db()->prepare('INSERT INTO users (full_name,email,password_hash,role,student_number,must_change_password) VALUES (?,?,?,?,?,1)');
                $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$role==='student'?$studentNumber:null]);
                set_flash('success', ucfirst($role) . ' account created. Share the temporary password privately.');
                redirect('admin/users.php');
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000' ? 'That email address or student number is already registered.' : 'The account could not be created.';
            }
        }
    }
}
$users = db()->query('SELECT id,full_name,email,role,student_number,is_active,created_at FROM users ORDER BY role,full_name')->fetchAll();
render_header('Manage users', 'Create student and lecturer accounts, or disable accounts that should no longer sign in.');
?>
<div class="content-grid two-col align-start"><section class="panel"><div class="panel-heading"><div><h2>Create a user account</h2><p>Administrator only</p></div></div><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Full name<input name="full_name" required maxlength="120"></label><label>Email address<input type="email" name="email" required maxlength="190"></label><label>Account type<select name="role" id="user-role"><option value="student">Student</option><option value="lecturer">Lecturer</option></select></label><label id="student-number-field">Limkokwing student ID<input name="student_number" type="text" inputmode="numeric" autocomplete="off" maxlength="60" minlength="6" pattern="90500[0-9]+" title="Student ID must start with 90500 and contain digits only after it. Example: 905001234." placeholder="90500XXXXXXXX"><small class="field-help">Required format: <strong>90500</strong> followed by the remaining digits. Example: 905001234. IDs with a different prefix or letters are rejected.</small></label><label>Temporary password<input type="password" name="temporary_password" required minlength="8" autocomplete="new-password"><small class="field-help">The user must change this password at first sign-in.</small></label><button class="button button-primary" type="submit">Create account</button></form></section>
<section class="panel"><h2>Account management notes</h2><ul class="plain-list"><li>Only an administrator can create accounts.</li><li>Every student ID must start with 90500, contain digits only, and be unique.</li><li>Lecturers are connected to courses on the Courses page.</li><li>Students are connected to courses through Enrolments.</li><li>Disabling an account prevents sign-in but keeps its attendance history.</li></ul><div class="alert alert-info">Never share passwords in a public group chat. Give temporary credentials directly to the account owner.</div></section></div>
<section class="panel"><div class="panel-heading"><div><h2>All user accounts</h2><p><?= count($users) ?> accounts in the system</p></div></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Student number</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach ($users as $u): ?><tr><td><strong><?= e($u['full_name']) ?></strong></td><td><?= e($u['email']) ?></td><td><?= e(ucfirst($u['role'])) ?></td><td><?= e($u['student_number'] ?: '—') ?></td><td><?= status_badge((int)$u['is_active']===1?'active':'inactive') ?></td><td><?php if ($u['role'] !== 'admin'): ?><form method="post" class="inline-form" data-confirm="Change this account's active status?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><button class="button button-small button-outline" type="submit"><?= (int)$u['is_active']===1?'Disable':'Enable' ?></button></form><?php else: ?><span class="muted">Protected</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php render_footer(); ?>
