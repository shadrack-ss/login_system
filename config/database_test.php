<?php
// Test Database configuration
$host = 'localhost';
$dbname = 'login_system_test';
$username = 'root';
$password = 'shadrack21';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Test database connection failed: " . $e->getMessage());
}
?>
