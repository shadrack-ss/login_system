<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Renderer;
use App\Auth\AuthService;

$auth = new AuthService();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
Renderer::render('dashboard', ['user' => $user]);
?>
