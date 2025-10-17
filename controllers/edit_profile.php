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
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);

    // Validation
    if (empty($new_username) || empty($new_email)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($new_username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } else {
        try {
            // Check if username is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $user['id']]);
            if ($stmt->fetch()) {
                $error = 'Username is already taken.';
            } else {
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$new_email, $user['id']]);
                if ($stmt->fetch()) {
                    $error = 'Email is already taken.';
                } else {
                    // Update user information
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$new_username, $new_email, $user['id']]);

                    // Update session username if it changed
                    if ($new_username !== $user['username']) {
                        $_SESSION['username'] = $new_username;
                    }

                    $message = 'Profile updated successfully!';

                    // Refresh user data
                    $user = getCurrentUser();
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred while updating your profile.';
        }
    }
}

Renderer::render('edit_profile', [
    'user' => $user,
    'message' => $message,
    'error' => $error
]);
