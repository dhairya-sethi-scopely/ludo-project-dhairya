<?php
// api/users.php
/**
 * API endpoint to return all users in JSON format.
 * Example: GET http://localhost:8888/ludo_backend/api/users.php
 */
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/UserRepository.php';
 
// Connect to DB
$db = (new Database())->getConnection();
$repo = new UserRepository($db);

// Fetch users and output as JSON
$users = $repo->getAllUsers();
echo json_encode($users, JSON_PRETTY_PRINT);