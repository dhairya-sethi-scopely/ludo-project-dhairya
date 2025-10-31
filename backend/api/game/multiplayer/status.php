<?php
/**
 * API: Multiplayer Status Polling
 * --------------------------------
 * Used by waiting screen to check if another player joined.
 * Returns countdown, player count, and game start flag.
 */

header("Content-Type: application/json");

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/Auth.php';
require_once __DIR__ . '/../../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../../classes/UserRepository.php';
require_once __DIR__ . '/../../../classes/ErrorResponder.php';

$errorResponder = new ErrorResponder();

// === Load Config & Constants ===
try {
    $config = json_decode(file_get_contents(__DIR__ . '/../../../config/config.local.json'), true, 512, JSON_THROW_ON_ERROR);
    $constants = json_decode(file_get_contents(__DIR__ . '/../../../config/constants.json'), true, 512, JSON_THROW_ON_ERROR);
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    $E = $constants['ERRORS'];
    $M = $constants['MESSAGES'];
} catch (Throwable $t) {
    error_log("STATUS_ERROR | Config or constants load failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        $E['ERR_ID_912'] ?? ["id" => "ERR_ID_912", "message" => "Invalid configuration."],
        ["detail" => $t->getMessage()]
    ));
    return;
}

// === Authenticate ===
try {
    Auth::init($config['secret_key']);
    $userRepo = new UserRepository($conn);
    $auth = new AuthenticationMiddleware($userRepo);
    $user = $auth->check();
} catch (Throwable $t) {
    error_log("STATUS_ERROR | Auth failure: " . $t->getMessage());
    http_response_code(401);
    echo json_encode($E['ERR_ID_900']);
    return;
}

$userId = $user['user_id'] ?? $user['sub'] ?? null;
if (!$userId) {
    error_log("STATUS_ERROR | Authentication failed: missing user ID");
    http_response_code($E['ERR_ID_900']['http']);
    echo json_encode($E['ERR_ID_900']);
    return;
}

// === Validate Session ID ===
$sessionId = $_GET['sessionId'] ?? null;
if (!$sessionId) {
    http_response_code($E['ERR_ID_905']['http']);
    echo json_encode($E['ERR_ID_905']);
    return;
}

// === Load Session ===
$repo = new GameSessionRepository($conn);
$session = $repo->getSessionState($sessionId);

if (!$session) {
    error_log("STATUS_ERROR | Session not found for ID $sessionId");
    http_response_code($E['ERR_ID_917']['http']);
    echo json_encode($E['ERR_ID_917']);
    return;
}

// === Compute Grace Period & Player Count ===
$remaining = max(0, strtotime($session['grace_until']) - time());
$currentCount = count(json_decode($session['players'], true));

// === Handle Session Activation ===
if (!$session['is_active']) {
    // Activate immediately if both players joined
    if ($currentCount >= $session['min_players']) {
        $repo->activateSession($sessionId);
        $session['is_active'] = 1;
        $remaining = 0;

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message_id" => $M['MSG_ID_2104']['id'],
            "message" => $M['MSG_ID_2104']['message'],
            "data" => [
                "participants" => json_decode($session['players'], true),
                "currentCount" => $currentCount,
                "maxPlayers" => $session['max_players'] ?? 2,
                "timeRemaining" => $remaining,
                "isActive" => true
            ]
        ], JSON_PRETTY_PRINT);
        return;
    }

    // Expire if time runs out
    if ($remaining <= 0 && $currentCount < $session['min_players']) {
        $repo->expireSession($sessionId);
        http_response_code($E['ERR_ID_923']['http']);
        echo json_encode($E['ERR_ID_923']);
        return;
    }
}

// === Normal Waiting Response ===
http_response_code(200);
echo json_encode([
    "success" => true,
    "message_id" => $M['MSG_ID_2009']['id'],
    "message" => $M['MSG_ID_2009']['message'],
    "data" => [
        "participants" => json_decode($session['players'], true),
        "currentCount" => $currentCount,
        "maxPlayers" => $session['max_players'] ?? 2,
        "timeRemaining" => $remaining,
        "isActive" => (bool)$session['is_active']
    ]
], JSON_PRETTY_PRINT);
