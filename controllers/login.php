<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Renderer;
use App\Auth\AuthService;

$auth = AuthService::getInstance();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate via service patterns then authenticate
    $user = $auth->findUserByUsernameOrEmail($username);
    if ($user && password_verify($password, $user['password'])) {
        $auth->createSession((int)$user['id']);
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}

Renderer::render('login', ['error' => $error]);
?>
