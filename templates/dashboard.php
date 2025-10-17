<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Login System</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body id="dashboard">
    <div class="header">
        <div class="logo">Login System</div>
        <div class="user-info">
            <span class="welcome">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="dashboard-card">
            <h2>User Dashboard</h2>
            <p>Welcome to your personal dashboard. Here you can view your account information and manage your profile.</p>
            <div class="user-details">
                <div class="detail-item">
                    <div class="detail-label">Username</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Member Since</div>
                    <div class="detail-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">User ID</div>
                    <div class="detail-value">#<?php echo $user['id']; ?></div>
                </div>
            </div>
            <div class="actions">
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                <a href="change_password.php" class="btn btn-secondary">Change Password</a>
            </div>
        </div>
    </div>
</body>
</html>

