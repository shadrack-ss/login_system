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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Login System</title>
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
            <h2>Edit Profile</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
            
            <div class="actions">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

