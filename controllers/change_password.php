<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once __DIR__ . '/../src/Core/Renderer.php';

use App\Core\Renderer;

// Check if user is logged in
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user_data = $stmt->fetch();

            if (!password_verify($current_password, $user_data['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);

                $message = 'Password changed successfully!';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred while changing your password.';
        }
    }
}

Renderer::render('change_password', [
    'user' => $user,
    'message' => $message,
    'error' => $error
]);
