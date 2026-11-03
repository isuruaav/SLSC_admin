<?php
declare(strict_types=1);

namespace SLSC\Registration;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

const INTERESTS = [
    'communications' => 'regInterestCommunications',
    'technology' => 'regInterestTechnology',
    'training' => 'regInterestTraining',
    'general' => 'regInterestGeneral',
];
const ERRORS = [
    'regErrorName' => 'Enter your full name (2–120 characters).',
    'regErrorEmail' => 'Enter a valid email address.',
    'regErrorPhone' => 'Enter a contact number with 7–15 digits.',
    'regErrorDistrict' => 'Enter your district or area (2–80 characters).',
    'regErrorInterest' => 'Choose an area of interest.',
    'regErrorMessage' => 'Keep your message within 1,000 characters.',
    'regErrorConsent' => 'Please agree to share your details for registration follow-up.',
    'regFixErrors' => 'Please check the highlighted fields.',
    'regSessionExpired' => 'Your session has expired. Refresh the page and try again.',
    'regRateLimit' => 'Too many registrations. Please try again in 10 minutes.',
    'regSaveError' => 'Your registration could not be saved. Please try again.',
    'regSubmissionError' => 'Unable to submit this form. Refresh the page and try again.',
];

function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    if (basename($path) === 'api') $path = dirname($path);
    if (!str_starts_with($path, '/')) $path = '/';
    if (!session_start([
        'name' => 'slsc_registration',
        'cookie_path' => rtrim($path, '/') . '/',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true,
    ])) throw new RuntimeException('Unable to start registration session.');
    if (empty($_SESSION['registration_csrf'])) $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

function formState(): array
{
    startSession();
    return ['csrf' => $_SESSION['registration_csrf'], 'submission_id' => bin2hex(random_bytes(16))];
}

function errorMessage(string $key): string
{
    return ERRORS[$key] ?? ERRORS['regSaveError'];
}

function textLength(string $value): int
{
    if (preg_match('//u', $value) !== 1) return PHP_INT_MAX;
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : preg_match_all('/./us', $value);
}

function validate(array $input): array
{
    $values = [];
    foreach (['full_name', 'email', 'phone', 'district', 'interest', 'message'] as $field) {
        $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
    }
    $values['email'] = strtolower($values['email']);
    $values['phone'] = preg_replace('/[\s().-]+/', '', $values['phone']) ?? '';
    $errors = [];
    if (textLength($values['full_name']) < 2 || textLength($values['full_name']) > 120 || preg_match('/[\r\n\x00-\x1f]/', $values['full_name'])) $errors['full_name'] = 'regErrorName';
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'regErrorEmail';
    if (!preg_match('/^\+?[0-9]{7,15}$/D', $values['phone'])) $errors['phone'] = 'regErrorPhone';
    if (textLength($values['district']) < 2 || textLength($values['district']) > 80 || preg_match('/[\r\n\x00-\x1f]/', $values['district'])) $errors['district'] = 'regErrorDistrict';
    if (!array_key_exists($values['interest'], INTERESTS)) $errors['interest'] = 'regErrorInterest';
    if (textLength($values['message']) > 1000 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $values['message'])) $errors['message'] = 'regErrorMessage';
    if (($input['consent'] ?? '') !== '1') $errors['consent'] = 'regErrorConsent';
    return ['values' => $values, 'errors' => $errors];
}

function connect(?string $path = null): PDO
{
    $path ??= getenv('SLSC_REGISTRATION_DB') ?: dirname(__DIR__) . '/storage/registrations.sqlite';
    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) throw new RuntimeException('PDO SQLite is required.');
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Registration storage is unavailable.');
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec('CREATE TABLE IF NOT EXISTS registrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reference TEXT NOT NULL UNIQUE,
        submission_id TEXT NOT NULL UNIQUE,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        district TEXT NOT NULL,
        interest TEXT NOT NULL,
        message TEXT NOT NULL,
        consent_version TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');
    return $db;
}

/** Shared by the JSON endpoint and the no-JavaScript registration page. */
function submit(array $input, array &$session, ?string $path = null, ?int $now = null): array
{
    $now ??= time();
    $token = $input['_token'] ?? '';
    if (!is_string($token) || !isset($session['registration_csrf']) || !hash_equals($session['registration_csrf'], $token)) {
        return ['status' => 403, 'body' => ['ok' => false, 'code' => 'regSessionExpired']];
    }
    $submissionId = $input['submission_id'] ?? '';
    if (!is_string($submissionId) || !preg_match('/^[a-f0-9]{32}$/D', $submissionId) || !empty($input['website'])) {
        return ['status' => 422, 'body' => ['ok' => false, 'code' => 'regSubmissionError']];
    }
    $checked = validate($input);
    if ($checked['errors']) return ['status' => 422, 'body' => ['ok' => false, 'code' => 'regFixErrors', 'errors' => $checked['errors']]];
    $db = null;
    try {
        $db = connect($path);
        $db->beginTransaction();
        $existing = $db->prepare('SELECT reference FROM registrations WHERE submission_id = ?');
        $existing->execute([$submissionId]);
        $reference = $existing->fetchColumn();
        if ($reference !== false) {
            $db->commit();
            return ['status' => 200, 'body' => ['ok' => true, 'reference' => $reference]];
        }
        $recent = array_values(array_filter($session['registration_times'] ?? [], static fn($timestamp) => is_int($timestamp) && $timestamp > $now - 600));
        if (count($recent) >= 5) {
            $db->rollBack();
            return ['status' => 429, 'body' => ['ok' => false, 'code' => 'regRateLimit']];
        }
        $time = new DateTimeImmutable('@' . $now);
        $reference = 'SLSC-' . $time->setTimezone(new DateTimeZone('Asia/Colombo'))->format('ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
        $statement = $db->prepare('INSERT INTO registrations (reference, submission_id, full_name, email, phone, district, interest, message, consent_version, created_at)
            VALUES (:reference, :submission_id, :full_name, :email, :phone, :district, :interest, :message, :consent_version, :created_at)');
        $statement->execute(array_merge($checked['values'], [
            'reference' => $reference,
            'submission_id' => $submissionId,
            'consent_version' => 'join-interest-v1',
            'created_at' => $time->format('c'),
        ]));
        $db->commit();
        $session['registration_times'] = [...$recent, $now];
        return ['status' => 201, 'body' => ['ok' => true, 'reference' => $reference]];
    } catch (Throwable $error) {
        if ($db && $db->inTransaction()) $db->rollBack();
        error_log('SLSC registration storage failure: ' . get_class($error));
        return ['status' => 503, 'body' => ['ok' => false, 'code' => 'regSaveError']];
    }
}
