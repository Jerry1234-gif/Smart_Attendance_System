<?php
require_once __DIR__ . '/../includes/guard.php';
$lecturer = require_role('lecturer');
$sessionId = (int)($_GET['session_id'] ?? 0);
$stmt = db()->prepare('SELECT s.session_title,c.course_code,c.course_name FROM attendance_sessions s JOIN courses c ON c.id=s.course_id WHERE s.id=? AND s.lecturer_id=?');
$stmt->execute([$sessionId,(int)$lecturer['id']]);
$session = $stmt->fetch();
if (!$session) { http_response_code(404); exit('Session not found.'); }
$stmt = db()->prepare('SELECT u.full_name,u.student_number,u.email,ar.checked_in_at,ar.method FROM attendance_records ar JOIN users u ON u.id=ar.student_id WHERE ar.session_id=? ORDER BY u.full_name');
$stmt->execute([$sessionId]);
$rows = $stmt->fetchAll();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_' . preg_replace('/[^A-Za-z0-9_-]/','_', $session['course_code']) . '_' . $sessionId . '.csv"');
$out = fopen('php://output','w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Course code','Course name','Session','Student name','Student number','Email','Checked in at','Method']);
foreach ($rows as $row) fputcsv($out, array_map('safe_csv_cell', [$session['course_code'],$session['course_name'],$session['session_title'],$row['full_name'],$row['student_number'],$row['email'],$row['checked_in_at'],$row['method']]));
fclose($out);
exit;
