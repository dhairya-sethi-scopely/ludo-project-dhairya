<?php
/**
 * API: Get Game Session
 * ----------------------
 * Fetches the full state of a given game session.
 * Requires authentication (JWT).
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

// Initialize variables to null/empty
$config = [];
$constants = [];

// === LOAD CONFIGURATION (SEPARATE BLOCKS) ===

// 1. Load Config File
try {
    $config = json_decode(
        file_get_contents(__DIR__ . '/../../config/config.local.json'), 
        true, 
        512, 
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Invalid configuration file (config.local.json).'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

// 2. Load Constants File
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../../config/constants.json'), 
        true, 
        512, 
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_913', 'message' => 'Invalid constants file (constants.json).'],
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
    $userData = $auth->check(); // Will validate JWT cookie
} catch (Throwable $t) {
    echo json_encode($errorResponder->send($E['ERR_ID_900'], ['detail' => $t->getMessage()]));
    return;
}

// === INPUT VALIDATION ===
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['session_id'])) {
    echo json_encode($errorResponder->send($E['ERR_ID_905'], ['detail' => 'Missing required field: session_id']));
    return;
}

$sessionId = (int)$input['session_id'];

// === FETCH SESSION ===
$repo = new GameSessionRepository($conn);
$session = $repo->getSessionById($sessionId);

if (!$session) {
    echo json_encode($errorResponder->send($E['ERR_ID_917']));
    return;
}

$userId = (int) ($userData['sub'] ?? $userData['user_id'] ?? 0);

// === SUCCESS RESPONSE ===
echo json_encode([
    "message_id" => $M['MSG_ID_2009']['id'] ?? "MSG_ID_2009",
    "message"    => $M['MSG_ID_2009']['message'] ?? "Game state fetched successfully.",
    "session_id" => $session['session_id'] ?? $sessionId,
    "host_id"    => $session['host_id'] ?? null,
    "players"    => $session['players'],
    "turn"       => $session['turn'],
    "game_state" => $session['game_state'],
    "me"         => $userId,   
    "success"    => true
], JSON_PRETTY_PRINT);

return;