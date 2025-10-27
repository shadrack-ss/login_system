<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Renderer;
use App\Auth\AuthService;

$auth = AuthService::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
Renderer::render('dashboard', ['user' => $user]);
?>
