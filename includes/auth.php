<?php
// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function logout() {
    // Remove session from database
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
        } catch (PDOException $e) {
            // Log error but don't stop logout process
        }
    }
    
    // Clear session data
    session_unset();
    session_destroy();
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}
?>
