<?php
/**
 * API: Join or Create Multiplayer Session
 * ---------------------------------------
 * Handles matchmaking for PvP mode (Player vs Player).
 * Uses constants.json for standardized messages and error codes.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json");

// === DEPENDENCIES ===
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../classes/GameStateFactory.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../../classes/UserRepository.php';
require_once __DIR__ . '/../../../classes/ErrorResponder.php';

$errorResponder = new ErrorResponder();



// =======================
// 🔹 LOAD CONFIGURATION
// =======================
try {
    $config = json_decode(
        file_get_contents(__DIR__ . '/../../../config/config.local.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    echo json_encode([
        "success" => false,
        "error" => "Configuration file invalid.",
        "detail" => $t->getMessage()
    ]);
    return;
}

// =======================
// 🔹 LOAD CONSTANTS
// =======================
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../../../config/constants.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

} catch (Throwable $t) {
    echo json_encode([
        "success" => false,
        "error" => "Constants file invalid.",
        "detail" => $t->getMessage()
    ]);
    return;
}

$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// =======================
// 🔹 DATABASE CONNECTION
// =======================
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'] ?? ["id" => "ERR_ID_910", "message" => "Database connection failed."],
        ["detail" => $t->getMessage()]
    ));
    return;
}

// =======================
// 🔹 AUTHENTICATE USER
// =======================
try {
    Auth::init($config['secret_key']);
    $userRepo = new UserRepository($conn);
    $auth = new AuthenticationMiddleware($userRepo);
    $user = $auth->check();
    $userId = $user['sub'] ?? $user['user_id'] ?? null;

    if (!$userId) {
        http_response_code($E['ERR_ID_900']['http']);
        echo json_encode($E['ERR_ID_900']);
        return;
    }

} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_900'],
        ["detail" => $t->getMessage()]
    ));
    return;
}

// =======================
// 🔹 MATCHMAKING LOGIC
// =======================
$repo = new GameSessionRepository($conn);

// Step 1: Check if player already has a pending session
if ($repo->hasPendingSession($userId)) {
    http_response_code($E['ERR_ID_922']['http']);
    echo json_encode($E['ERR_ID_922']);
    return;
}

// Step 2: Try to find open waiting session
try {
    $openSession = $repo->findOpenSession();
} catch (Throwable $t) {
    echo json_encode($errorResponder->send($E['ERR_ID_910'], ["detail" => $t->getMessage()]));
    return;
}

// Step 3: Join existing session if available
if ($openSession) {
    try {
        $repo->addPlayerToSession($openSession['session_id'], $userId);
        $repo->initializeGameStateIfEmpty($openSession['session_id']);
        $session = $repo->getSessionState($openSession['session_id']);
    } catch (Throwable $t) {
        echo json_encode($errorResponder->send($E['ERR_ID_910'], ["detail" => $t->getMessage()]));
        return;
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message_id" => $M['MSG_ID_2103']['id'],
        "message" => $M['MSG_ID_2103']['message'],
        "data" => [
            "sessionId" => $session['session_id'],
            "sessionData" => $session
        ]
    ], JSON_PRETTY_PRINT);
    return;
}

// Step 4: Create a new session if none found
try {
    
    $newSessionId = $repo->createPendingSession((int)$userId, 2, 90); // grace = 90s
    if (!$newSessionId) {
        throw new Exception("Failed to create pending session. No session ID returned.");
    }

    $newSession = $repo->getSessionState($newSessionId);
    if (!$newSession) {
        throw new Exception("Session not found after creation (ID=$newSessionId)");
    }

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message_id" => $M['MSG_ID_2102']['id'],
        "message" => $M['MSG_ID_2102']['message'],
        "data" => [
            "sessionId" => $newSession['session_id'],
            "sessionData" => $newSession
        ]
    ], JSON_PRETTY_PRINT);
    return;

} catch (Throwable $t) {
    http_response_code($E['ERR_ID_910']['http']);
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'],
        ["detail" => $t->getMessage()]
    ));
    return;
}
