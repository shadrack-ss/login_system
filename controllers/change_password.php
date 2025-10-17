<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Login System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .welcome {
            color: #555;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .form-card h2 {
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 1rem;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .password-requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #555;
        }
        .password-requirements h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Login System</div>
        <div class="user-info">
            <span class="welcome">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>Change Password</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="password-requirements">
                <h4>Password Requirements:</h4>
                <ul>
                    <li>At least 6 characters long</li>
                    <li>Must be different from your current password</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
            
            <div class="actions">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

