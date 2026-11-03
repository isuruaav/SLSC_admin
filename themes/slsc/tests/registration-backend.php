<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/registration.php';

use function SLSC\Registration\validate;
use function SLSC\Registration\submit;
use function SLSC\Registration\connect;

$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
$directory = __DIR__ . '/.tmp';
if (!is_dir($directory)) mkdir($directory, 0770, true);
$path = $directory . '/registration-' . bin2hex(random_bytes(8)) . '.sqlite';
check(!file_exists($path), 'Test database must be new.');
$session = ['registration_csrf' => str_repeat('a', 64)];
$input = [
    '_token' => $session['registration_csrf'], 'submission_id' => str_repeat('b', 32),
    'full_name' => "Test Applicant O'Neil", 'email' => 'Applicant@Example.test',
    'phone' => '+94 77 123 4567', 'district' => 'Colombo', 'interest' => 'technology',
    'message' => 'පරීක්ෂණ ලියාපදිංචිය', 'consent' => '1', 'website' => '',
];
$now = 1793592000;
$db = null;
try {
    $validated = validate($input);
    check($validated['errors'] === [], 'Valid registration must pass.');
    check($validated['values']['email'] === 'applicant@example.test', 'Email is normalized.');
    check($validated['values']['phone'] === '+94771234567', 'Phone is normalized.');
    $unicode = $input; $unicode['full_name'] = 'කමල් බණ්ඩාර';
    check(validate($unicode)['errors'] === [], 'Sinhala names must be accepted.');
    $invalid = ['full_name' => [], 'email' => 'invalid', 'phone' => 'abc', 'district' => '', 'interest' => 'invalid', 'message' => str_repeat('a', 1001)];
    check(count(validate($invalid)['errors']) === 7, 'Required fields and length constraints are enforced.');
    $badEncoding = $input; $badEncoding['message'] = "\xff";
    check(isset(validate($badEncoding)['errors']['message']), 'Invalid UTF-8 is rejected.');
    $badToken = $input; $badToken['_token'] = 'incorrect';
    check(submit($badToken, $session, $path, $now)['status'] === 403, 'CSRF token must match.');
    check(!file_exists($path), 'Invalid requests must not create a database.');
    $bot = $input; $bot['website'] = 'spam.example';
    check(submit($bot, $session, $path, $now)['status'] === 422, 'Honeypot submissions are rejected.');
    $noConsent = $input; unset($noConsent['consent']);
    check(submit($noConsent, $session, $path, $now)['body']['errors']['consent'] === 'regErrorConsent', 'Consent is required on the server.');
    $created = submit($input, $session, $path, $now);
    check($created['status'] === 201 && $created['body']['ok'], 'Valid registration must persist.');
    check((bool)preg_match('/^SLSC-\d{6}-[A-F0-9]{12}$/', $created['body']['reference']), 'A reference is generated.');
    $db = connect($path);
    $row = $db->query('SELECT * FROM registrations')->fetch();
    check($row['full_name'] === $input['full_name'], 'Prepared statements preserve apostrophes.');
    check($row['message'] === $input['message'], 'Unicode messages are saved.');
    check($row['consent_version'] === 'join-interest-v1', 'Consent version is recorded.');
    $retry = submit($input, $session, $path, $now);
    check($retry['status'] === 200 && $retry['body']['reference'] === $created['body']['reference'], 'Retries return the same reference.');
    check((int)$db->query('SELECT COUNT(*) FROM registrations')->fetchColumn() === 1, 'Retries do not create duplicates.');
    for ($i = 0; $i < 4; $i++) {
        $next = $input; $next['submission_id'] = bin2hex(random_bytes(16));
        check(submit($next, $session, $path, $now)['status'] === 201, 'Registration within the rate limit succeeds.');
    }
    $next = $input; $next['submission_id'] = bin2hex(random_bytes(16));
    check(submit($next, $session, $path, $now)['status'] === 429, 'Excess submissions are limited.');
    check(submit($input, $session, $path, $now)['status'] === 200, 'A legitimate retry still works at the rate limit.');
    check(submit($next, $session, $path, $now + 601)['status'] === 201, 'The rate limit expires.');
    echo $checks . " backend checks passed.\n";
} finally {
    $db = null;
    if (is_file($path)) unlink($path);
}
