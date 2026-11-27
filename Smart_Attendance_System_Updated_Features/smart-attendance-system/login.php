<?php
require_once __DIR__ . '/includes/guard.php';
if (current_user()) redirect(role_home(current_user()['role']));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    try {
        $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $next = (string)($_POST['next'] ?? '');
            if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
                $basePath = (string)(parse_url(app_base_url(), PHP_URL_PATH) ?? '');
                if ($basePath !== '' && str_starts_with($next, $basePath . '/')) $next = substr($next, strlen($basePath) + 1);
                else $next = ltrim($next, '/');
                if ($next !== '' && !str_contains($next, '..')) redirect($next);
            }
            redirect(role_home($user['role']));
        }
        $error = 'The email or password is incorrect, or this account has been disabled.';
    } catch (Throwable $exception) {
        $error = 'Could not connect to the application database. Open setup.php first, and check config/database.php.';
    }
}
$next = (string)($_GET['next'] ?? '');
render_header('Sign in', 'Use the account provided by your system administrator.');
?>
<div class="auth-panel"><div class="auth-panel-art"><div class="art-icon">▦</div><h2>One simple check-in for every class.</h2><p>Sign in to access your courses, QR attendance sessions and attendance history.</p><img src="<?= e(app_url('assets/images/attendance-workflow.png')) ?>" alt="Attendance workflow diagram"></div><div class="auth-panel-form"><h2>Welcome back</h2><p class="muted">Enter your account details to continue.</p><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="next" value="<?= e($next) ?>"><label>Email address<input type="email" name="email" required autocomplete="username" placeholder="you@example.com"></label><label>Password<input type="password" name="password" required autocomplete="current-password" placeholder="Your password"></label><button class="button button-primary button-wide" type="submit">Sign in</button></form><p class="tiny-note">Need an account? Contact the system administrator.</p></div></div>
<?php render_footer(); ?>
