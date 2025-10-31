<?php
/**
 * API: Login User
 * -------------------
 * Authenticates user credentials and issues a JWT.
 * ✅ Clean, production-ready version.
*/

header('Content-Type: application/json');

// === Dependencies ===
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ErrorResponder.php';
// === Load Authentication Classes ===
require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../classes/Auth.php';

$errorResponder = new ErrorResponder();

// === LOAD CONFIGURATION (Separate Try/Catch) ===
try {
    $config = json_decode(
        file_get_contents(__DIR__ . '/../config/config.local.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    error_log("LOGIN_ERROR | Config file load failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_912', 'message' => 'Configuration file invalid.'],
        ['detail' => $t->getMessage()]
    ), JSON_PRETTY_PRINT);
    return;
}

// === LOAD CONSTANTS (Separate Try/Catch) ===
try {
    $constants = json_decode(
        file_get_contents(__DIR__ . '/../config/constants.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $t) {
    error_log("LOGIN_ERROR | Constants file load failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        ['id' => 'ERR_ID_913', 'message' => 'Constants file invalid.'],
        ['detail' => $t->getMessage()]
    ), JSON_PRETTY_PRINT);
    return;
}

// === Set Environment Variables ===
$_ENV['SECRET_KEY'] = $config['secret_key'] ?? null;
$E = $constants['ERRORS'] ?? [];
$M = $constants['MESSAGES'] ?? [];

// === Validate Configuration ===
if (empty($_ENV['SECRET_KEY']) || empty($config['db'])) {
    echo json_encode($errorResponder->send(
        $E['ERR_ID_914'] ?? ['id' => 'ERR_ID_914', 'message' => 'Incomplete configuration.'],
        ['detail' => 'Missing DB config or secret key.']
    ), JSON_PRETTY_PRINT);
    return;
}

// === DATABASE CONNECTION ===
try {
    $db = new Database($config['db']);
    $conn = $db->getConnection();
} catch (Throwable $t) {
    error_log("LOGIN_ERROR | Database connection failed: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'] ?? ['id' => 'ERR_ID_910', 'message' => 'Database connection failed.'],
        ['detail' => $t->getMessage()]
    ), JSON_PRETTY_PRINT);
    return;
}


// === Initialize AuthController ===
$repo = new UserRepository($conn);
Auth::init($_ENV['SECRET_KEY']);
$controller = new AuthController($repo);

// === Handle Login Request ===
try {
    $response = $controller->login(); // Should return array
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    error_log("LOGIN_ERROR | Exception during login: " . $t->getMessage());
    echo json_encode($errorResponder->send(
        $E['ERR_ID_910'] ?? ['id' => 'ERR_ID_910', 'message' => 'Internal server error.'],
        ['detail' => 'Login failed: ' . $t->getMessage()]
    ), JSON_PRETTY_PRINT);
}

return;
