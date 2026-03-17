<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

function respond(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    respond(422, [
        'success' => false,
        'message' => 'Please complete the required form fields.'
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
}

$entry = [
    'submitted_at' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

$storageDir = __DIR__ . DIRECTORY_SEPARATOR . 'storage';
$logFile = $storageDir . DIRECTORY_SEPARATOR . 'contact-submissions.jsonl';

if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
    respond(500, [
        'success' => false,
        'message' => 'Unable to prepare contact storage on the server.'
    ]);
}

$written = file_put_contents(
    $logFile,
    json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if ($written === false) {
    respond(500, [
        'success' => false,
        'message' => 'Unable to store your message right now.'
    ]);
}

$recipient = trim((string) getenv('PORTFOLIO_CONTACT_TO'));
$emailNotice = '';

if ($recipient !== '') {
    $mailSubject = $subject !== '' ? "Portfolio inquiry: {$subject}" : "Portfolio inquiry from {$name}";
    $mailBody = "Name: {$name}\n"
        . "Email: {$email}\n"
        . "Subject: {$subject}\n\n"
        . $message . "\n";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];

    $sent = @mail($recipient, $mailSubject, $mailBody, implode("\r\n", $headers));

    if (!$sent) {
        $emailNotice = ' Your message was saved, but server email delivery is not configured yet.';
    }
}

respond(200, [
    'success' => true,
    'message' => 'Thanks for reaching out. Your message has been saved successfully.' . $emailNotice
]);
