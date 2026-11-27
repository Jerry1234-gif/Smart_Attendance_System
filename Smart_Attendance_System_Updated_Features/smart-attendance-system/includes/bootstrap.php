<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('smart_attendance_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

$dbConfig = require __DIR__ . '/../config/database.php';
$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Africa/Freetown');

if (!defined('APP_NAME')) {
    define('APP_NAME', $appConfig['name'] ?? 'Smart Attendance System');
}
if (!defined('APP_KEY')) {
    define('APP_KEY', (string)($appConfig['app_key'] ?? 'change-this-secret'));
}

function app_config(string $key, mixed $default = null): mixed
{
    global $appConfig;
    return $appConfig[$key] ?? $default;
}

function db(): PDO
{
    static $pdo = null;
    global $dbConfig;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'] ?? 3306,
        $dbConfig['database'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Sierra Leone uses UTC (Africa/Freetown), so database and PHP clocks match.
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function app_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appRoot = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $relative = '';
    if ($appRoot !== false && $docRoot !== false && str_starts_with($appRoot, $docRoot)) {
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($appRoot, strlen($docRoot)));
    }
    return $scheme . '://' . $host . rtrim($relative, '/');
}

function app_url(string $path = ''): string
{
    return app_base_url() . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    if (preg_match('~^https?://~i', $path)) {
        // External redirects are intentionally not accepted in this application.
        $path = 'login.php';
    }
    header('Location: ' . app_url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string)($_POST['csrf_token'] ?? '');
    $saved = (string)($_SESSION['csrf_token'] ?? '');
    if ($sent === '' || $saved === '' || !hash_equals($saved, $sent)) {
        http_response_code(419);
        exit('Your form has expired. Go back, refresh the page and try again.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $stmt = db()->prepare('SELECT id, full_name, email, role, student_number, is_active, must_change_password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user || (int)$user['is_active'] !== 1) {
            $user = null;
            unset($_SESSION['user_id']);
        }
    } catch (Throwable $exception) {
        $user = null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $requested = $_SERVER['REQUEST_URI'] ?? '';
        $next = '';
        if ($requested !== '' && str_starts_with($requested, '/') && !str_starts_with($requested, '//')) {
            $basePath = (string)(parse_url(app_base_url(), PHP_URL_PATH) ?? '');
            if ($basePath !== '' && str_starts_with($requested, $basePath . '/')) {
                $requested = substr($requested, strlen($basePath) + 1);
            } else {
                $requested = ltrim($requested, '/');
            }
            if ($requested !== '' && !str_contains($requested, '..')) {
                $next = '?next=' . rawurlencode('/' . $requested);
            }
        }
        header('Location: ' . app_url('login.php') . $next);
        exit;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ((int)$user['must_change_password'] === 1 && !in_array($script, ['change_password.php', 'logout.php'], true)) {
        redirect('change_password.php');
    }
    return $user;
}

function require_role(string|array $roles): array
{
    $user = require_login();
    $roles = (array)$roles;
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
    return $user;
}

function role_home(string $role): string
{
    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'lecturer' => 'lecturer/dashboard.php',
        default => 'student/dashboard.php',
    };
}

function format_datetime(?string $value, string $format = 'd M Y, h:i A'): string
{
    if (!$value) return '—';
    try {
        return (new DateTime($value))->format($format);
    } catch (Throwable $exception) {
        return (string)$value;
    }
}

function safe_csv_cell(mixed $value): string
{
    $text = (string)$value;
    if ($text !== '' && preg_match('/^[=+\-@\t\r]/', $text)) {
        return "'" . $text;
    }
    return $text;
}
