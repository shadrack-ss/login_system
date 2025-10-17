<?php
require_once __DIR__ . '/../bootstrap.php';
use App\Auth\AuthService;

$auth = new AuthService();
$auth->destroySession();
header('Location: login.php?message=logged_out');
exit();
?>
