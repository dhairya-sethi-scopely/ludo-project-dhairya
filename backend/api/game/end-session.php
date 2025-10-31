<?php
/**
 * API: End Game Session
 * ---------------------
 * Marks a game session as completed and assigns the winner.
 */

header('Content-Type: application/json');

// === DEPENDENCIES ===
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ErrorResponder.php';
require_once __DIR__ . '/../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../classes/UserRepository.php';
require_once __DIR__ . '/../../classes/Auth.php';

$errorResponder = new ErrorResponder();

// === LOAD CONFIG FILE ===
try {
    $config = json_decode(file_get_contents(__DIR__ . '/../../config/config.local.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Configuration file missing or invalid.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

// === LOAD CONSTANTS FILE ===
try {
    $constants = json_decode(file_get_contents(__DIR__ . '/../../config/constants.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_913', 'message' => 'Constants file missing or unreadable.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === CONFIG VALIDATION ===
if (empty($_ENV['SECRET_KEY']) || empty($config['db'])) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_914'],
        ['detail' => 'Missing DB config or secret key.']
    ));
    return;
}

// === DATABASE CONNECTION ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'],
        ['detail' => 'Database connection failed: ' . $t->getMessage()]
    ));
    return;
}

// === AUTHENTICATION ===
try {
    $userRepo = new UserRepository($conn);
    Auth::init($_ENV['SECRET_KEY']);
    $auth = new AuthenticationMiddleware($userRepo);
    $userData = $auth->check(); // verifies JWT
} catch (Throwable $t) {
    echo json_encode($errorResponder->send($E['ERR_ID_900'], ['detail' => $t->getMessage()]));
    return;
}

// === READ INPUT ===
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['session_id'], $input['winner_id'])) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_905'],
        ['detail' => 'Missing required fields: session_id or winner_id']
    ));
    return;
}

$sessionId = (int)$input['session_id'];
$winnerId = (int)$input['winner_id'];

// === UPDATE DATABASE ===
$repo = new GameSessionRepository($conn);
$session = $repo->getSessionById($sessionId);

if (!$session) {
    echo json_encode($errorResponder->send($E['ERR_ID_917']));
    return;
}

try {
    $repo->endSession($sessionId, (string)$winnerId);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'],
        ['detail' => 'Failed to end session: ' . $t->getMessage()]
    ));
    return;
}

// === SUCCESS RESPONSE ===
echo json_encode([
    "message_id" => $M['MSG_ID_2004']['id'] ?? "MSG_ID_2004",
    "message"    => $M['MSG_ID_2004']['message'] ?? "Session ended successfully.",
    "session_id" => $sessionId,
    "winner_id"  => $winnerId,
    "success"    => true
], JSON_PRETTY_PRINT);

return;
