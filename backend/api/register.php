<?php
/**
 * API: Register User
 * -------------------
 * Handles environment bootstrapping and delegates to AuthController.
 * Produces a single clean JSON output (no multiple echo).
 */

header('Content-Type: application/json');

// === DEPENDENCIES ===
require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ErrorResponder.php';

$errorResponder = new ErrorResponder();

// === LOAD CONFIGURATION ===
try {
    $config = json_decode(file_get_contents(__DIR__ . '/../config/config.local.json'), true, 512, JSON_THROW_ON_ERROR);
    $constants = json_decode(file_get_contents(__DIR__ . '/../config/constants.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Invalid or unreadable configuration file.'],
        ['detail' => $t->getMessage()]
    ), JSON_PRETTY_PRINT);
    return;
}

$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === CONFIG VALIDATION ===
if (empty($_ENV['SECRET_KEY']) || empty($config['db'])) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_914'], ['detail' => 'Missing DB config or secret key']
    ), JSON_PRETTY_PRINT);
    return;
}

// === DATABASE CONNECTION ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'], ['detail' => 'Database connection failed: ' . $t->getMessage()]
    ), JSON_PRETTY_PRINT);
    return;
}


$repo = new UserRepository($conn);
Auth::init($_ENV['SECRET_KEY']);
$controller = new AuthController($repo);

// === CONTROLLER EXECUTION ===
try {
    $response = $controller->register(); // ✅ Now returns array instead of echoing
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'], ['detail' => 'Registration failed: ' . $t->getMessage()]
    ), JSON_PRETTY_PRINT);
}

return;
