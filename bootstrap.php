<?php
// Bootstrap: session, database, and autoloader

session_start();

require_once __DIR__ . '/config/database.php'; // provides $pdo

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Provide container-like simple globals
App\Core\Container::set('pdo', $pdo);
?>

