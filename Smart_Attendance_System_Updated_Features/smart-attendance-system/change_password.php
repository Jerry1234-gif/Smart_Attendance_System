<?php
require_once __DIR__ . '/includes/guard.php';
$user = require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    $hash = (string)$stmt->fetchColumn();
    if (!password_verify($current, $hash)) $error = 'Your current password is not correct.';
    elseif (strlen($new) < 8) $error = 'Your new password must contain at least 8 characters.';
    elseif ($new !== $confirm) $error = 'The new passwords do not match.';
    elseif (password_verify($new, $hash)) $error = 'Choose a password different from your current password.';
    else {
        $update = db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
        set_flash('success', 'Your password has been updated.');
        redirect(role_home($user['role']));
    }
}
render_header('Change password', 'Use a password that is difficult for other people to guess.');
?>
<div class="narrow-card"><h2>Update your password</h2><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label><label>New password<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label><label>Confirm new password<input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></label><button class="button button-primary" type="submit">Save new password</button></form></div>
<?php render_footer(); ?>
