<?php

/**
 * Validate Limkokwing University of Creative Technology student numbers.
 * Official student IDs must begin with 90500 and contain digits only after it.
 * At least one digit must follow the five-digit prefix.
 */
function is_valid_student_number(string $studentNumber): bool
{
    return preg_match('/\A90500[0-9]+\z/', $studentNumber) === 1;
}

function close_expired_sessions(): void
{
    db()->exec("UPDATE attendance_sessions SET status='closed' WHERE status='open' AND ends_at < UTC_TIMESTAMP()");
}

function generate_attendance_code(): string
{
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function code_hash(string $code): string
{
    return hash_hmac('sha256', $code, APP_KEY);
}

function create_session_credentials(int $sessionId): array
{
    $token = bin2hex(random_bytes(32));
    $code = generate_attendance_code();
    $tokenHash = hash('sha256', $token);
    $codeHash = code_hash($code);

    $stmt = db()->prepare('UPDATE attendance_sessions SET qr_token_hash = ?, attendance_code_hash = ? WHERE id = ?');
    $stmt->execute([$tokenHash, $codeHash, $sessionId]);

    $_SESSION['session_secrets'][$sessionId] = [
        'token' => $token,
        'code' => $code,
    ];
    return ['token' => $token, 'code' => $code];
}

function get_or_create_session_credentials(int $sessionId): array
{
    if (!empty($_SESSION['session_secrets'][$sessionId]['token']) && !empty($_SESSION['session_secrets'][$sessionId]['code'])) {
        return $_SESSION['session_secrets'][$sessionId];
    }
    return create_session_credentials($sessionId);
}

function course_is_enrolled(int $studentId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1');
    $stmt->execute([$studentId, $courseId]);
    return (bool)$stmt->fetchColumn();
}

function record_student_attendance(array $session, int $studentId, string $method): array
{
    if ($session['status'] !== 'open') {
        return ['ok' => false, 'message' => 'This attendance session is closed.'];
    }
    $now = new DateTimeImmutable('now');
    $start = new DateTimeImmutable($session['starts_at']);
    $end = new DateTimeImmutable($session['ends_at']);
    if ($now < $start || $now > $end) {
        return ['ok' => false, 'message' => 'This attendance session is not currently accepting check-ins.'];
    }
    if (!course_is_enrolled($studentId, (int)$session['course_id'])) {
        return ['ok' => false, 'message' => 'You are not enrolled in this course. Contact the administrator if this is incorrect.'];
    }

    try {
        $stmt = db()->prepare('INSERT INTO attendance_records (session_id, student_id, method) VALUES (?, ?, ?)');
        $stmt->execute([(int)$session['id'], $studentId, $method]);
        return ['ok' => true, 'message' => 'Your attendance has been recorded successfully.'];
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            return ['ok' => false, 'message' => 'Your attendance has already been recorded for this session.'];
        }
        throw $exception;
    }
}

function count_session_attendance(int $sessionId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM attendance_records WHERE session_id = ?');
    $stmt->execute([$sessionId]);
    return (int)$stmt->fetchColumn();
}

function lecturer_owns_course(int $lecturerId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM courses WHERE id = ? AND lecturer_id = ? LIMIT 1');
    $stmt->execute([$courseId, $lecturerId]);
    return (bool)$stmt->fetchColumn();
}

function lecturer_owns_session(int $lecturerId, int $sessionId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM attendance_sessions WHERE id = ? AND lecturer_id = ? LIMIT 1');
    $stmt->execute([$sessionId, $lecturerId]);
    return (bool)$stmt->fetchColumn();
}
