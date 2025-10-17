<?php
// Test bootstrap file
require_once __DIR__ . '/../vendor/autoload.php';

// Start session for tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration for tests
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'login_system_test';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'shadrack21';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Store PDO in container for tests
    require_once __DIR__ . '/../src/Core/Container.php';
    App\Core\Container::set('pdo', $pdo);

} catch(PDOException $e) {
    die("Test database connection failed: " . $e->getMessage());
}
