<?php
require_once __DIR__ . '/includes/guard.php';
$user = current_user();
if ($user) redirect(role_home($user['role']));
render_header('Welcome', 'A simple way to record and review class attendance.');
?>
<section class="hero-grid">
    <div class="hero-copy">
        <span class="pill"><span class="dot"></span> QR-based classroom attendance</span>
        <h2>Attendance made clearer, quicker and easier.</h2>
        <p>Smart Attendance System helps lecturers open class sessions and lets enrolled students check in using a time-limited QR code or a six-digit attendance code.</p>
        <div class="hero-actions"><a class="button button-primary" href="<?= e(app_url('login.php')) ?>">Sign in to the portal <span>→</span></a><a class="button button-outline" href="#how-it-works">How it works</a></div>
        <div class="hero-points"><span>✓ One check-in per class session</span><span>✓ Attendance history</span><span>✓ Lecturer reports</span></div>
    </div>
    <div class="hero-image"><img src="<?= e(app_url('assets/images/classroom-qr-attendance.png')) ?>" alt="Student scanning a QR code in a university classroom"><div class="image-caption"><span class="caption-check">✓</span><span><strong>Check-in confirmation</strong><small>Attendance can be recorded in seconds</small></span></div></div>
</section>
<section class="feature-grid" id="how-it-works">
    <article class="feature-card"><span class="feature-icon">▦</span><h3>Scan or enter a code</h3><p>Students scan a lecturer's QR code or enter the short attendance code manually.</p></article>
    <article class="feature-card"><span class="feature-icon">✓</span><h3>Validated check-in</h3><p>The system checks the active session, course enrolment and previous attendance record.</p></article>
    <article class="feature-card"><span class="feature-icon">▤</span><h3>Clear reports</h3><p>Students can view their history while lecturers can review and export session records.</p></article>
</section>
<div class="info-strip"><strong>Important:</strong> This is an educational project implementation. Your university must approve any use for official attendance records.</div>
<?php render_footer(); ?>
