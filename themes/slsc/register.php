<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/registration.php';
\SLSC\Registration\startSession();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function icon(string $name, string $class = ''): string { return '<svg class="icon ' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"></use></svg>'; }
function asset(string $path): string { return e($path . '?v=' . filemtime(__DIR__ . '/' . $path)); }
$registrationStandalone = true;
$registrationValues = [];
$registrationErrors = [];
$registrationErrorCode = '';
$registrationReference = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
        http_response_code(413);
        $registrationErrorCode = 'regSubmissionError';
    } else {
        $result = \SLSC\Registration\submit($_POST, $_SESSION);
        if ($result['body']['ok']) {
            $_SESSION['registration_receipt'] = $result['body']['reference'];
            header('Location: register.php?complete=1', true, 303);
            exit;
        }
        http_response_code($result['status']);
        $registrationValues = \SLSC\Registration\validate($_POST)['values'];
        $registrationValues['consent'] = ($_POST['consent'] ?? '') === '1' ? '1' : '';
        $registrationErrors = $result['body']['errors'] ?? [];
        $registrationErrorCode = $result['body']['code'];
    }
}
if (isset($_GET['complete'], $_SESSION['registration_receipt'])) {
    $registrationReference = $_SESSION['registration_receipt'];
    unset($_SESSION['registration_receipt']);
}
$registrationFormState = \SLSC\Registration\formState();
// Keep the same idempotency key when correcting server-side validation errors.
if (is_string($_POST['submission_id'] ?? null) && preg_match('/^[a-f0-9]{32}$/D', $_POST['submission_id'])) $registrationFormState['submission_id'] = $_POST['submission_id'];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Online Registration | Sri Lanka Signal Corps</title><meta name="theme-color" content="#071b3a"><link rel="icon" type="image/png" href="assets/images/logo/signals-logo.png"><link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>"><script type="module" src="<?= asset('assets/js/script.js') ?>"></script></head>
<body class="registration-body">
<?php require __DIR__ . '/includes/icons.php'; ?>
<div class="container registration-page-header"><a href="index.php#join-us" class="registration-back"><?= icon('chevron-left') ?><span data-i18n="regBackWebsite">Back to website</span></a><div class="language-switcher enhanced-only" role="group" aria-label="Website language" data-i18n-aria="websiteLanguage"><button type="button" class="lang-btn active" data-lang="en" aria-pressed="true">EN</button><button type="button" class="lang-btn" data-lang="si" aria-pressed="false" lang="si">සිං</button></div></div>
<main class="container registration-page"><section class="registration-standalone" aria-labelledby="registrationTitle"><header class="registration-heading"><p class="eyebrow" data-i18n="joinEyebrow">JOIN US</p><h1 id="registrationTitle" data-i18n="regModalTitle">Online registration</h1><p id="registrationIntro" data-i18n="regIntro">Share your details and register your interest in the Sri Lanka Signal Corps.</p></header><?php require __DIR__ . '/includes/registration-form.php'; ?></section></main>
</body></html>
