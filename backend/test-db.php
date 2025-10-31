<?php
require_once __DIR__ . '/config/db.php';

// Load JSON configuration file
$configData = json_decode(file_get_contents(__DIR__ . '/config/config.local.json'), true);

// Extract the 'db' section (the array expected by Database)
$dbConfig = $configData['db'];

$db = new Database($dbConfig);  // Pass config array to Database class
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Database connected successfully using config.local.json!";
}
