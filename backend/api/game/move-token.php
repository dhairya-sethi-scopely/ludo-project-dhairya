<?php
/**
 * API: Move Token (PvP Mode)
 * -------------------
 * Handles player token movement, captures, and win logic.
 */

header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

// === DEPENDENCIES ===
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ErrorResponder.php';
require_once __DIR__ . '/../../classes/GameSessionRepository.php';
require_once __DIR__ . '/../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../classes/UserRepository.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../services/TokenService.php';
require_once __DIR__ . '/../../services/TurnManager.php';
require_once __DIR__ . '/../../services/WinConditionService.php';

$errorResponder = new ErrorResponder();

// === LOAD CONFIGURATION ===
try {
    $config = json_decode(file_get_contents(__DIR__ . '/../../config/config.local.json'), true, 512, JSON_THROW_ON_ERROR);
    $constants = json_decode(file_get_contents(__DIR__ . '/../../config/constants.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Configuration or constants invalid.'],
        ['detail' => $t->getMessage()]
    ));
    return;
}

$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === DATABASE CONNECTION + AUTH ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
    $userRepo = new UserRepository($conn);
    Auth::init($_ENV['SECRET_KEY']);
    $auth = new AuthenticationMiddleware($userRepo);
    $userData = $auth->check();
} catch (Throwable $t) {
    error_log("MOVE_ERROR | Auth/DB failure: " . $t->getMessage());
    echo json_encode($errorResponder->send($E['ERR_ID_900'], ['detail' => $t->getMessage()]));
    return;
}

$playerId = (int)($userData['sub'] ?? $userData['user_id'] ?? 0);

// === READ REQUEST ===
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['session_id'], $input['token_id'], $input['dice_value'])) {
    echo json_encode($errorResponder->send($E['ERR_ID_905'], ['detail' => 'Missing required fields']));
    return;
}

$sessionId = (int)$input['session_id'];
$tokenId   = (string)$input['token_id'];
$diceValue = (int)$input['dice_value'];

error_log("MOVE-TOKEN START | Player=$playerId | Session=$sessionId | Dice=$diceValue");

// === INITIALIZE SERVICES ===
$repo         = new GameSessionRepository($conn);
$tokenService = new TokenService();
$turnManager  = new TurnManager();
$winService   = new WinConditionService();

// === FETCH SESSION ===
$session = $repo->getSessionById($sessionId);
if (!$session) {
    echo json_encode($errorResponder->send($E['ERR_ID_917']));
    return;
}

$players = is_string($session['players']) ? json_decode($session['players'], true) : $session['players'];
$state   = is_string($session['game_state']) ? json_decode($session['game_state'], true) : $session['game_state'];
if (!is_array($players)) $players = [];
if (!is_array($state))   $state   = [];

$currentTurnIndex = (int)$session['turn'];
$currentPlayerId  = (int)($players[$currentTurnIndex] ?? 0);

if ($currentPlayerId !== $playerId) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_920'],
        ['detail' => "Not your turn. Current turn belongs to player $currentPlayerId."]
    ));
    return;
}

// === FIND SELECTED TOKEN ===
$playerTokens = $state['tokens'][$playerId] ?? [];
$selectedToken = null;
foreach ($playerTokens as &$token) {
    if (($token['id'] ?? '') === $tokenId) {
        $selectedToken = &$token;
        break;
    }
}
if (!$selectedToken) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_908'],
        ['detail' => "Token $tokenId not found for player $playerId"]
    ));
    return;
}

// === MOVE TOKEN ===
$tokenService->moveToken($selectedToken, $diceValue);
$path = str_starts_with($tokenId, 'P1_') ? $tokenService->getRedPath() : $tokenService->getBluePath();
$currentStep = max(1, (int)$selectedToken['steps']);
$coord = $path[$currentStep - 1] ?? null;
if ($coord) {
    $selectedToken['x'] = $coord['x'];
    $selectedToken['y'] = $coord['y'];
}

$state['tokens'][$playerId] = $playerTokens;
$state['lastDice'] = $diceValue;

// === CAPTURE CHECK ===
$bonusTurn = false;
foreach ($players as $opponentId) {
    if ($opponentId == $playerId) continue;
    $captured = $tokenService->checkCapture($selectedToken, $state['tokens'][$opponentId]);
    if ($captured) {
        $state['captures'][$playerId] = ($state['captures'][$playerId] ?? 0) + 1;
        $bonusTurn = true;
    }
}

// === WIN CONDITION ===
if ($winService->checkWin($state['tokens'][$playerId])) {
    $repo->endSession($sessionId, (string)$playerId);
    $state['winner'] = $playerId;
    $state['isGameOver'] = true;
    $repo->updateState($sessionId, $state, $currentTurnIndex);

    error_log("MOVE_WIN | Player=$playerId won the game");
    echo json_encode([
        "success" => true,
        "message" => "Player $playerId has won the game!",
        "winner_id" => $playerId,
        "session_id" => $sessionId,
        "state" => $state
    ]);
    return;
}

// === UPDATE STATE ===
$state['move_counters'][$playerId] = ($state['move_counters'][$playerId] ?? 0) + 1;
$nextTurnIndex = $bonusTurn ? $currentTurnIndex : $turnManager->getNextTurn($currentTurnIndex, $diceValue, $players);
$repo->updateState($sessionId, $state, $nextTurnIndex);

error_log("MOVE_SUCCESS | Player=$playerId | Steps={$selectedToken['steps']} | NextTurn=$nextTurnIndex");

// === RESPONSE ===
$response = [
    "success" => true,
    "message_id" => $M['MSG_ID_2000']['id'] ?? "MSG_ID_2000",
    "message" => $bonusTurn
        ? "Token captured! Bonus turn awarded."
        : ($M['MSG_ID_2000']['message'] ?? "Token moved successfully."),
    "session_id" => $sessionId,
    "player_id" => $playerId,
    "token_id" => $tokenId,
    "new_position" => $selectedToken['position'],
    "steps" => $selectedToken['steps'],
    "dice_value" => $diceValue,
    "next_turn" => $players[$nextTurnIndex] ?? null,
    "captures" => $state['captures'][$playerId] ?? 0,
    "game_state" => $state
];

echo json_encode($response);
return;
