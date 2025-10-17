<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Renderer;
use App\Auth\AuthService;

$auth = new AuthService();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

Renderer::render('index');
?>
