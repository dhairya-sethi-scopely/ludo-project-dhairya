<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AuthenticationMiddleware.php';
require_once __DIR__ . '/../../classes/AuthorizationService.php';

$config = require __DIR__ . '/../../config/config.local.php';
$_ENV['SECRET_KEY'] = $config['secret_key'];

// Connect DB + repo
$db = new Database($config['db']);
$conn = $db->getConnection();
$repo = new UserRepository($conn);

// ✅ Step 1: Authenticate
$auth = new AuthenticationMiddleware($repo);
$user = $auth->check();

// ✅ Step 2: Authorize (only admin role allowed)
$authz = new AuthorizationService();
$authz->requireRoles($user, ['admin']);

// If we reach here, user is authorized
echo json_encode([
    "message" => "Wallet adjusted successfully by admin",
    "user" => $user
]);
