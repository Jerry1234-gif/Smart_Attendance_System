<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function ai_json(int $status, string $reply): never
{
    http_response_code($status);
    echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') ai_json(405, 'Please send a question using the chat form.');
verify_csrf();
$message = trim((string)($_POST['message'] ?? ''));
if ($message === '' || (function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) > 1200) ai_json(422, 'Please enter a question of 1 to 1,200 characters.');

// Simple per-session request limit to reduce accidental repeated API calls.
$now = time();
$_SESSION['ai_request_times'] = array_values(array_filter($_SESSION['ai_request_times'] ?? [], static fn($t) => (int)$t > $now - 300));
if (count($_SESSION['ai_request_times']) >= 12) ai_json(429, 'You have sent several questions recently. Please wait a few minutes and try again.');
$_SESSION['ai_request_times'][] = $now;

$config = require __DIR__ . '/../config/ai.php';
$key = trim((string)$config['api_key']);
if ($key === '') {
    $lower = function_exists('mb_strtolower') ? mb_strtolower($message) : strtolower($message);
    $answers = [
        ['qr|scan|attendance code', 'To mark attendance, sign in with your student account, open Scan QR, allow camera access and scan the code currently displayed by your lecturer. The attendance session must still be open, and you must be enrolled in that course. If camera scanning is unavailable, use the lecturer’s active six-digit attendance code if that option is enabled.'],
        ['90500|student id|student number', 'Student accounts must use a Limkokwing student number that begins with 90500 and contains digits only after that prefix. For example, 905001234 is valid. The rule is checked by the form and the server.'],
        ['add user|create user|new student|new lecturer', 'Only an administrator can add accounts. Open Users in the top navigation, complete the Create a user account form, choose Student or Lecturer, and enter the required details. For a student, enter an ID beginning with 90500.'],
        ['report|export|csv', 'Lecturers can open Reports from the navigation to view attendance records for their courses. Use the CSV export option on the report page when you need a spreadsheet copy.'],
        ['dark|light|colour|color|text size|accessibility|disability|speech|read aloud', 'Open Accessibility & display beneath the top navigation. From there you can change text size, choose a colour theme, switch between light and dark mode, enable high contrast, choose a readable font, underline links, reduce animations, use a larger cursor and read the current page aloud.'],
        ['help|support|problem|contact', 'Open Help & Support from the top navigation. Read the common questions or submit a support request. Administrators can review submitted requests from the Support inbox.'],
        ['password|login|sign in', 'Use your registered email address and password. If you were given a temporary password, the system may ask you to change it after the first sign-in. Use the Change Password link after signing in.'],
    ];
    foreach ($answers as [$patterns, $reply]) {
        foreach (explode('|', $patterns) as $pattern) {
            if (str_contains($lower, $pattern)) ai_json(200, $reply . "\n\nNote: Live AI is not configured yet; this is the built-in help mode.");
        }
    }
    ai_json(200, 'I can help with QR scanning, student IDs beginning with 90500, adding users, attendance reports, passwords and accessibility settings. Please ask about one of these topics, or use Help & Support to contact your administrator. Live AI is not configured yet, so this is the built-in help mode.');
}

if (!function_exists('curl_init')) ai_json(503, 'The server does not have PHP cURL enabled. Enable the cURL extension in XAMPP, restart Apache, and try again.');
$payload = [
    'model' => (string)$config['model'],
    'store' => false,
    'instructions' => 'You are the Smart Attendance System assistant for a university HCI project. Answer clearly and briefly in plain English. Help users understand how to use this application, especially student QR check-in, lecturer attendance sessions, administrator user creation, reports, the 90500 student ID rule, accessibility and support. Never claim that you have changed records or accounts. You cannot access the database or personal attendance records. Do not ask for passwords, API keys or sensitive personal information. For account-specific matters, direct the user to Help & Support or the system administrator. Distinguish demo features from real connected functionality.',
    'input' => $message,
    'max_output_tokens' => 500,
];
$curl = curl_init((string)$config['endpoint']);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 35,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
]);
$response = curl_exec($curl);
$httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);
if ($response === false) {
    error_log('Smart Attendance AI connection error: ' . $curlError);
    ai_json(502, 'The AI service could not be reached. Please check the server internet connection and try again.');
}
$data = json_decode((string)$response, true);
if ($httpCode < 200 || $httpCode >= 300) {
    $apiMessage = (string)($data['error']['message'] ?? 'The AI provider returned an error.');
    error_log('Smart Attendance AI API error (' . $httpCode . '): ' . $apiMessage);
    ai_json(502, 'The AI service could not answer at the moment. Check the API key, model setting and API account status, then try again.');
}
$reply = trim((string)($data['output_text'] ?? ''));
if ($reply === '' && !empty($data['output']) && is_array($data['output'])) {
    foreach ($data['output'] as $item) {
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text') $reply .= (string)($content['text'] ?? '') . "\n";
        }
    }
}
$reply = trim($reply);
if ($reply === '') ai_json(502, 'The AI service returned an empty answer. Please try asking in a different way.');
ai_json(200, $reply);
