<?php
function render_header(string $title, string $subtitle = ''): void
{
    $user = current_user();
    $flash = get_flash();
    $brandLink = $user ? role_home($user['role']) : 'index.php';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#173d32">
        <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
        <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
    </head>
    <body>
    <header class="topbar">
        <a class="brand" href="<?= e(app_url($brandLink)) ?>">
            <span class="brand-mark">SA</span>
            <span><strong>Smart Attendance</strong><small>University attendance portal</small></span>
        </a>
        <?php if ($user): ?>
            <nav class="main-nav" aria-label="Main navigation">
                <a href="<?= e(app_url(role_home($user['role']))) ?>">Dashboard</a>
                <?php if ($user['role'] === 'student'): ?>
                    <a href="<?= e(app_url('student/history.php')) ?>">My history</a>
                    <a href="<?= e(app_url('scan.php')) ?>">Scan QR</a>
                <?php elseif ($user['role'] === 'lecturer'): ?>
                    <a href="<?= e(app_url('lecturer/reports.php')) ?>">Reports</a>
                <?php else: ?>
                    <a href="<?= e(app_url('admin/users.php')) ?>">Users</a>
                    <a href="<?= e(app_url('admin/courses.php')) ?>">Courses</a>
                    <a href="<?= e(app_url('admin/support.php')) ?>">Support inbox</a>
                <?php endif; ?>
                <a href="<?= e(app_url('ai_assistant.php')) ?>">AI Assistant</a>
                <a href="<?= e(app_url('help_support.php')) ?>">Help &amp; Support</a>
                <a href="<?= e(app_url('change_password.php')) ?>">Password</a>
                <a class="nav-logout" href="<?= e(app_url('logout.php')) ?>">Sign out</a>
            </nav>
            <div class="top-user"><span class="avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span><span><?= e($user['full_name']) ?><small><?= e(ucfirst($user['role'])) ?></small></span></div>
        <?php else: ?>
            <nav class="main-nav"><a href="<?= e(app_url('login.php')) ?>">Sign in</a><a href="<?= e(app_url('help_support.php')) ?>">Help &amp; Support</a></nav>
        <?php endif; ?>
    </header>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <section class="accessibility-bar" aria-label="Display and accessibility tools">
        <div class="accessibility-bar-inner">
            <button class="accessibility-toggle" type="button" data-accessibility-toggle aria-expanded="false" aria-controls="accessibility-panel">Accessibility &amp; display <span aria-hidden="true">⌄</span></button>
            <div class="accessibility-quick-actions">
                <button class="accessibility-quick-button" type="button" data-speech-read aria-label="Read this page aloud">Read page aloud</button>
                <button class="accessibility-quick-button" type="button" data-speech-stop hidden>Stop reading</button>
            </div>
        </div>
        <div class="accessibility-panel" id="accessibility-panel" data-accessibility-panel hidden>
            <div class="accessibility-panel-heading"><div><strong>Accessibility &amp; display settings</strong><small>Your choices are saved on this device.</small></div><button type="button" class="panel-close" data-accessibility-close aria-label="Close accessibility settings">×</button></div>
            <div class="accessibility-settings-grid">
                <label>Text size<select data-text-size><option value="1">Normal</option><option value="1.12">Large</option><option value="1.25">Extra large</option></select></label>
                <label>Colour theme<select data-colour-theme><option value="green">Original green</option><option value="teal">Teal</option><option value="blue">Blue</option><option value="purple">Purple</option><option value="orange">Orange</option></select></label>
                <label>Display mode<select data-display-mode><option value="light">Light mode</option><option value="dark">Dark mode</option></select></label>
            </div>
            <div class="accessibility-checks">
                <label><input type="checkbox" data-a11y="contrast"> High contrast</label>
                <label><input type="checkbox" data-a11y="readable-font"> Use a highly readable font</label>
                <label><input type="checkbox" data-a11y="underline-links"> Underline links</label>
                <label><input type="checkbox" data-a11y="reduce-motion"> Reduce animations</label>
                <label><input type="checkbox" data-a11y="large-cursor"> Larger cursor</label>
            </div>
            <p class="accessibility-note">These options support different visual, reading and motor preferences. They do not replace assistive technology or individual accessibility needs.</p>
            <button class="button button-outline button-small" type="button" data-reset-accessibility>Reset settings</button>
        </div>
    </section>
    <main class="page-shell" id="main-content" tabindex="-1">
        <div class="page-heading">
            <div><p class="eyebrow">SMART ATTENDANCE SYSTEM</p><h1><?= e($title) ?></h1><?php if ($subtitle !== ''): ?><p class="page-subtitle"><?= e($subtitle) ?></p><?php endif; ?></div>
        </div>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer"><span>Smart Attendance System · HCI Project Prototype</span><span>Use university-approved attendance rules before real deployment.</span></footer>
    <script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
    </body>
    </html>
    <?php
}

function status_badge(string $status): string
{
    $class = match (strtolower($status)) {
        'open', 'active', 'present' => 'badge-success',
        'closed', 'inactive' => 'badge-muted',
        default => 'badge-warning',
    };
    return '<span class="badge ' . $class . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}
