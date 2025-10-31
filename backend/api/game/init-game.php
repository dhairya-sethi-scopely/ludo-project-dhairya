<?php
/**
 * API: Initialize Game Session
 * ----------------------------
 * Creates a new game session (Player vs Player or Player vs AI).
 * ✅ Supports AI player auto-injection and cookie-based AI mode authentication.
 * ✅ Fully refactored to avoid exit() calls.
 */

header('Content-Type: application/json');

// === Load dependencies ===
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ErrorResponder.php';
require_once __DIR__ . '/../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../classes/GameStateFactory.php';

$errorResponder = new ErrorResponder();

// === CONFIGURATION LOADING ===
try {
    $config = json_decode(
        file_get_contents(__DIR__ . '/../../config/config.local.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    echo json_encode(
        $errorResponder->send(
            ['id' => 'ERR_ID_912', 'message' => 'Invalid or unreadable configuration file.'],
            ['detail' => $t->getMessage()]
        ),
        JSON_PRETTY_PRINT
    );
    return;
}

// === CONSTANTS LOADING ===
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../../config/constants.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    echo json_encode(
        $errorResponder->send(
            ['id' => 'ERR_ID_913', 'message' => 'Constants file missing or invalid.'],
            ['detail' => $t->getMessage()]
        ),
        JSON_PRETTY_PRINT
    );
    return;
}

// === Extract configuration ===
$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === CONFIG VALIDATION ===
if (empty($_ENV['SECRET_KEY']) || empty($config['db'])) {
    echo json_encode(
        $errorResponder->send(
            $E['ERR_ID_914'] ?? ['id' => 'ERR_ID_914', 'message' => 'Incomplete configuration parameters.'],
            ['detail' => 'Missing DB config or secret key.']
        ),
        JSON_PRETTY_PRINT
    );
    return;
}

// === DATABASE CONNECTION ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    echo json_encode(
        $errorResponder->send(
            $E['ERR_ID_910'] ?? ['id' => 'ERR_ID_910', 'message' => 'Database connection failed.'],
            ['detail' => $t->getMessage()]
        ),
        JSON_PRETTY_PRINT
    );
    return;
}

// === SETUP REPOSITORY & FACTORY ===
$repo = new GameSessionRepository($conn);
$factory = new GameStateFactory(
    $config,
    json_decode(file_get_contents(__DIR__ . '/../../config/default_game_state.json'), true)
);

// === INPUT VALIDATION ===
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['host_id'], $input['players'], $input['game_prize'])) {
    echo json_encode(
        $errorResponder->send(
            $E['ERR_ID_905'] ?? ['id' => 'ERR_ID_905', 'message' => 'Missing required fields in request.'],
            ['detail' => 'Expected host_id, players, and game_prize.']
        ),
        JSON_PRETTY_PRINT
    );
    return;
}

$hostId  = (int)$input['host_id'];
$players = $input['players']; // e.g., [12, 13]
$prize   = (int)$input['game_prize'];
$mode    = strtolower($input['mode'] ?? 'pvp'); // "pvp" | "vs_ai"

// === AI MODE HANDLING ===
if ($mode === 'vs_ai') {
    error_log("Initializing Player vs AI game mode");
    // ✅ Automatically create an AI player
    $aiPlayer = [
        'id'       => 99999,
        'username' => 'AI_' . substr(bin2hex(random_bytes(3)), 0, 6),
        'role'     => 'ai'
    ];

    // Append AI player ID to players array
    if (!in_array(9999, $players)) {
    $players[] = 9999;
    }
 
    // Store AI metadata (if supported by your factory)
    if (method_exists($factory, 'addAIMetadata')) {
        $factory->addAIMetadata($aiPlayer);
    }

    // Set AI_MODE cookie for backend auto-auth
    setcookie('AI_MODE', 'true', time() + 3600, '/');
}

// === CREATE GAME SESSION ===
try {
    $sessionId = $repo->createSession($hostId, $players, $prize);

    $messageId = $mode === 'vs_ai'
        ? ($M['MSG_ID_2101']['id'] ?? "MSG_ID_2101")
        : ($M['MSG_ID_2002']['id'] ?? "MSG_ID_2002");

    $messageText = $mode === 'vs_ai'
        ? ($M['MSG_ID_2101']['message'] ?? "AI game session created successfully.")
        : ($M['MSG_ID_2002']['message'] ?? "Game session created successfully.");

    echo json_encode([
        "message_id" => $messageId,
        "message"    => $messageText,
        "session_id" => $sessionId,
        "mode"       => $mode,
        "success"    => true
    ], JSON_PRETTY_PRINT);
    return;

} catch (Throwable $t) {
    echo json_encode(
        $errorResponder->send(
            $E['ERR_ID_910'] ?? ['id' => 'ERR_ID_910', 'message' => 'Failed to create game session.'],
            ['detail' => $t->getMessage()]
        ),
        JSON_PRETTY_PRINT
    );
    return;
}
