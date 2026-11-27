<?php
require_once __DIR__ . '/includes/guard.php';
$user = current_user();
$error = '';
$success = '';

// Create this table automatically so the feature also works in an existing installation.
db()->exec("CREATE TABLE IF NOT EXISTS support_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_status_date (status, created_at),
    KEY idx_support_user (user_id),
    CONSTRAINT fk_support_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = $user ? (string)$user['full_name'] : trim((string)($_POST['full_name'] ?? ''));
    $email = $user ? (string)$user['email'] : strtolower(trim((string)($_POST['email'] ?? '')));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter your name and a valid email address.';
    elseif ((function_exists('mb_strlen') ? mb_strlen($subject) : strlen($subject)) < 3 || (function_exists('mb_strlen') ? mb_strlen($subject) : strlen($subject)) > 180) $error = 'Enter a subject between 3 and 180 characters.';
    elseif ((function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) < 10 || (function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) > 4000) $error = 'Your message must be between 10 and 4,000 characters.';
    else {
        $stmt = db()->prepare('INSERT INTO support_requests (user_id,full_name,email,subject,message) VALUES (?,?,?,?,?)');
        $stmt->execute([$user ? (int)$user['id'] : null, $name, $email, $subject, $message]);
        set_flash('success', 'Your support request has been sent to the system administrator.');
        redirect('help_support.php');
    }
}
render_header('Help & Support', 'Find answers to common questions or send a message to the system administrator.');
?>
<div class="content-grid two-col align-start">
    <section class="panel"><div class="panel-heading"><div><h2>Common questions</h2><p>Quick guidance for using Smart Attendance.</p></div></div>
        <div class="faq-list">
            <details><summary>How do students mark attendance?</summary><p>Sign in, open Scan QR and scan the active code displayed by the lecturer. Students must be enrolled in the course and the session must be open.</p></details>
            <details><summary>Why must a student ID start with 90500?</summary><p>The system applies the Limkokwing student-number rule: the ID must begin with 90500 and contain digits only after the prefix.</p></details>
            <details><summary>How can an administrator add users?</summary><p>Open Users in the navigation. Administrators can create student and lecturer accounts. Student accounts require a valid 90500-prefixed student ID.</p></details>
            <details><summary>Where can lecturers find attendance reports?</summary><p>Lecturers can open Reports from the top navigation to view records for their courses and export a CSV file where available.</p></details>
            <details><summary>How do I change text size or use dark mode?</summary><p>Open Accessibility &amp; display below the top navigation. Choose a text size, colour theme or display mode, and enable other accessibility options.</p></details>
            <details><summary>Can I use text-to-speech?</summary><p>Use the Read page aloud button in the accessibility bar. This relies on your browser's built-in speech feature and voice support.</p></details>
        </div>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Contact support</h2><p>Send a request to the system administrator.</p></div></div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <?php if (!$user): ?><label>Your name<input name="full_name" required maxlength="120"></label><label>Email address<input type="email" name="email" required maxlength="190"></label><?php endif; ?>
            <label>Subject<input name="subject" required maxlength="180" placeholder="Briefly describe the issue"></label>
            <label>Message<textarea name="message" required maxlength="4000" rows="6" placeholder="Explain what happened and what help you need..."></textarea></label>
            <button type="submit" class="button button-primary">Submit support request</button>
        </form>
    </section>
</div>
<?php render_footer(); ?>
