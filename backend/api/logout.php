<?php
/**
 * API: Logout User
 * ----------------
 * Safely clears JWT cookie and confirms logout.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/ErrorResponder.php';

$errorResponder = new ErrorResponder();

// === Load constants safely ===
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../config/constants.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    $response = $errorResponder->send(
        ['id' => 'ERR_ID_913', 'message' => 'Constants file missing or invalid.'],
        ['detail' => $t->getMessage()]
    );
    echo json_encode($response, JSON_PRETTY_PRINT);
    return;
}

// === Extract constants ===
$M = $constants['MESSAGES'] ?? [];
$E = $constants['ERRORS'] ?? [];

// === Clear JWT cookie ===
setcookie(
    $constants['JWT_COOKIE_NAME'],
    '',
    time() - 3600,
    $constants['COOKIE_PATH'],
    '',
    $constants['COOKIE_SECURE'],
    $constants['COOKIE_HTTPONLY']
);

// === Return JSON Response ===
$response = [
    'message_id' => $M['MSG_ID_2008']['id'] ?? 'MSG_ID_2008',
    'message'    => $M['MSG_ID_2008']['message'] ?? 'Logout successful.',
    'success'    => true
];

echo json_encode($response, JSON_PRETTY_PRINT);
return;
