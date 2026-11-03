<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/registration.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'code' => 'regSubmissionError']);
    exit;
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'code' => 'regSubmissionError']);
    exit;
}
\SLSC\Registration\startSession();
$result = \SLSC\Registration\submit($_POST, $_SESSION);
session_write_close();
http_response_code($result['status']);
if ($result['status'] === 429) header('Retry-After: 600');
echo json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
