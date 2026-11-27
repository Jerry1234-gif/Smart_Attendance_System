<?php
declare(strict_types=1);
session_start();
$config = require __DIR__ . '/config/database.php';
$error = '';
$success = false;
$alreadyInstalled = false;

try {
    $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $config['host'], $config['port'] ?? 3306, $config['charset'] ?? 'utf8mb4');
    $server = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $safeDbName = (string)$config['database'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $safeDbName)) {
        throw new RuntimeException('The database name may contain only letters, numbers and underscores.');
    }
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . $safeDbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db = new PDO($dsn . ';dbname=' . $safeDbName, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tableCheck = $db->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if ($tableCheck) {
        $adminCheck = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $alreadyInstalled = ((int)$adminCheck > 0);
    }
} catch (Throwable $exception) {
    $error = 'Database connection failed: ' . $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && !$alreadyInstalled) {
    $sentToken = (string)($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['setup_csrf']) || !hash_equals($_SESSION['setup_csrf'], $sentToken)) {
        $error = 'The setup form expired. Refresh the page and try again.';
    } else {
        $name = trim((string)($_POST['admin_name'] ?? ''));
        $email = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $password = (string)($_POST['admin_password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $error = 'Enter the administrator name, a valid email address and a password with at least 8 characters.';
        } else {
            try {
                $schema = file_get_contents(__DIR__ . '/database/schema.sql');
                if ($schema === false) throw new RuntimeException('Could not read database/schema.sql.');
                foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
                    $db->exec($statement);
                }
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, must_change_password) VALUES (?, ?, ?, 'admin', 1)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $success = true;
            } catch (Throwable $exception) {
                $error = 'Setup could not finish: ' . $exception->getMessage();
            }
        }
    }
}

if (empty($_SESSION['setup_csrf'])) $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
function setup_escape(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Install Smart Attendance System</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-body"><main class="auth-card setup-card">
    <div class="auth-brand"><span class="brand-mark">SA</span><div><strong>Smart Attendance</strong><small>First-time setup</small></div></div>
    <?php if ($success): ?>
        <div class="status-illustration">✓</div><h1>Installation complete</h1><p>Your database tables and administrator account have been created.</p>
        <p class="alert alert-info">For security, the administrator must change the password after the first sign-in.</p>
        <a class="button button-primary button-wide" href="login.php">Go to sign in</a>
    <?php elseif ($alreadyInstalled): ?>
        <div class="status-illustration">✓</div><h1>Already installed</h1><p>An administrator account already exists in this database. The setup page is locked to avoid creating duplicate installation accounts.</p><a class="button button-primary button-wide" href="login.php">Go to sign in</a>
    <?php else: ?>
        <p class="eyebrow">INSTALLATION WIZARD</p><h1>Set up your system</h1><p class="muted">This creates the database tables and your first administrator account. Check config/database.php first if your MySQL settings are different.</p>
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= setup_escape($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= setup_escape($_SESSION['setup_csrf']) ?>">
            <label>Administrator full name<input name="admin_name" required maxlength="120" placeholder="Your full name"></label>
            <label>Administrator email<input type="email" name="admin_email" required maxlength="190" placeholder="admin@example.com"></label>
            <label>Initial password<input type="password" name="admin_password" required minlength="8" placeholder="At least 8 characters"></label>
            <button class="button button-primary button-wide" type="submit">Create database and administrator</button>
        </form>
        <p class="tiny-note">Database name: <strong><?= setup_escape($config['database']) ?></strong>. No sample student records are created.</p>
    <?php endif; ?>
</main></body></html>
