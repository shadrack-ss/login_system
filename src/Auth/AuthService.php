<?php
namespace App\Auth;

use App\Core\Container;
use App\Security\Validator;
use PDO;
use PDOException;

class AuthService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Container::get('pdo');
    }

    public function findUserByUsernameOrEmail(string $identifier) {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, created_at FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch();
    }

    public function register(string $username, string $email, string $password): array {
        if (!Validator::isValidUsername($username)) {
            return [false, 'Username must be 3-50 characters and can include letters, numbers, _ . -'];
        }
        if (!Validator::isValidEmail($email)) {
            return [false, 'Invalid email format.'];
        }
        if (!Validator::isStrongPassword($password)) {
            return [false, 'Password must be 8+ chars and include uppercase, lowercase, number, and special character.'];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                return [false, 'Username or email already exists.'];
            }

            $hashed = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed]);
            return [true, null];
        } catch (PDOException $e) {
            return [false, 'Registration failed. Please try again.'];
        }
    }

    public function createSession(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $expiresAt]);
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = $token;
        return $token;
    }

    public function destroySession(): void {
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token = ?");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
            } catch (PDOException $e) {
                // swallow
            }
        }
        session_unset();
        session_destroy();
    }

    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) return null;
        $stmt = $this->pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
    }
}
?>

