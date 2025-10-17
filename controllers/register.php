<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Core\Renderer;
use App\Auth\AuthService;

$auth = new AuthService();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        [$ok, $msg] = $auth->register($username, $email, $password);
        if ($ok) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $msg;
        }
    }
}

Renderer::render('register', ['error' => $error, 'success' => $success]);
?>
