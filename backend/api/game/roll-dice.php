<?php
/**
 * API: Roll Dice (PvP)
 * --------------------
 * Rolls a dice for the authenticated player.
 * Ensures only the player whose turn it is can roll.
 * Determines which tokens are eligible to move.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ErrorResponder.php';
require_once __DIR__ . '/../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../classes/UserRepository.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../services/TurnManager.php';
require_once __DIR__ . '/../../classes/RandomNumberGenerator.php';

$errorResponder = new ErrorResponder();

// === LOAD CONFIGURATION ===
try {
    $config = json_decode(
        file_get_contents(__DIR__ . '/../../config/config.local.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    error_log("ROLL_ERROR | Config load failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Configuration file invalid.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

// === LOAD CONSTANTS SEPARATELY ===
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../../config/constants.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    error_log("ROLL_ERROR | Constants load failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_913', 'message' => 'Constants file invalid.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$_ENV['APP_MODE'] = $_ENV['APP_MODE'] ?? ($config['app_mode'] ?? 'prod');
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === CONFIG VALIDATION ===
if (empty($_ENV['SECRET_KEY']) || empty($config['db'])) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_914'] ?? ['id' => 'ERR_ID_914', 'message' => 'Missing DB config or secret key.']
    ));
    return;
}

// === DATABASE CONNECTION ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    error_log("ROLL_ERROR | DB connection failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'] ?? ['id' => 'ERR_ID_910', 'message' => 'Database connection failed.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

// === INITIALIZE REPOSITORIES ===
$repo = new GameSessionRepository($conn);
$userRepo = new UserRepository($conn);

// === AUTHENTICATE USER ===
try {
    Auth::init($_ENV['SECRET_KEY']);
    $auth = new AuthenticationMiddleware($userRepo);
    $userData = $auth->check();
} catch (Throwable $t) {
    error_log("ROLL_ERROR | Auth failed: " . $t->getMessage());
    echo json_encode($errorResponder->send($E['ERR_ID_900'], ['detail' => $t->getMessage()]));
    return;
}

$playerId = (int) ($userData['sub'] ?? $userData['user_id'] ?? 0);
if (!$playerId) {
    error_log("ROLL_ERROR | Missing player ID");
    echo json_encode($E['ERR_ID_900']);
    return;
}

// === READ INPUT ===
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['session_id'])) {
    echo json_encode($errorResponder->send($E['ERR_ID_905'], ['detail' => 'Missing required field: session_id']));
    return;
}

$sessionId = (int)$input['session_id'];

// === FETCH SESSION ===
$session = $repo->getSessionById($sessionId);
if (!$session) {
    error_log("ROLL_ERROR | Session not found: $sessionId");
    echo json_encode($errorResponder->send($E['ERR_ID_917']));
    return;
}

// === AUTHORIZE TURN ===
$players = $session['players'];
$currentTurnIndex = (int)$session['turn'];
$currentPlayerTurn = (int)$players[$currentTurnIndex] ?? 0;

if ($_ENV['APP_MODE'] !== 'test' && $currentPlayerTurn !== $playerId) {
    echo json_encode($errorResponder->send($E['ERR_ID_920'], ['detail' => 'Not your turn.']));
    return;
}

// === GENERATE DICE VALUE ===
$diceValue = RandomNumberGenerator::between(1, 6);
$state = $session['game_state'];
$state['lastDice'] = $diceValue;

// === DETERMINE ELIGIBLE TOKENS ===
$eligibleTokens = [];
$playerTokens = $state['tokens'][$playerId] ?? [];

foreach ($playerTokens as $token) {
    $pos = strtoupper($token['position'] ?? 'YARD');
    if ($pos === 'PATH' || ($diceValue === 6 && $pos === 'YARD')) {
        $eligibleTokens[] = $token['id'];
    }
}

// === SAFETY: Add fallback for 6 ===
if ($diceValue === 6 && empty($eligibleTokens)) {
    foreach ($playerTokens as $token) {
        if ($token['position'] === 'YARD') {
            $eligibleTokens[] = $token['id'];
        }
    }
}

// === IF NO TOKENS CAN MOVE, SKIP TURN ===
if (empty($eligibleTokens)) {
    $turnManager = new TurnManager();
    $nextTurn = $turnManager->getNextTurn($currentTurnIndex, $diceValue, $players);
    $repo->updateState($sessionId, $state, $nextTurn);

    $response = [
        "message_id" => $M['MSG_ID_2012']['id'] ?? "MSG_ID_2012",
        "message" => $M['MSG_ID_2012']['message'] ?? "No valid tokens. Turn passed.",
        "dice_value" => $diceValue,
        "player_id" => $playerId,
        "next_turn" => $players[$nextTurn],
        "success" => true
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    return;
}

// === SAVE UPDATED STATE ===
$repo->updateState($sessionId, $state, $currentTurnIndex);

// === SUCCESS RESPONSE ===
$response = [
    "message_id" => $M['MSG_ID_2016']['id'] ?? "MSG_ID_2016",
    "message" => $M['MSG_ID_2016']['message'] ?? "Dice rolled successfully.",
    "dice_value" => $diceValue,
    "player_id" => $playerId,
    "valid_tokens" => $eligibleTokens,
    "next_action" => "SELECT_TOKEN",
    "success" => true
];

error_log("ROLL_SUCCESS | Player=$playerId | Dice=$diceValue | Eligible=" . count($eligibleTokens));
echo json_encode($response, JSON_PRETTY_PRINT);
return;
