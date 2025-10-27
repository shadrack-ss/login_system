<?php
namespace App\Auth;

use App\Core\Container;
use App\Security\Validator;
use PDO;
use PDOException;

/**
 * AuthService - Singleton Pattern Implementation
 *
 * This class manages user authentication and session handling.
 * Implements the Singleton design pattern to ensure only one instance
 * exists throughout the application lifecycle, preventing multiple
 * database connections and ensuring consistent state management.
 */
class AuthService {
    private PDO $pdo;
    private static ?AuthService $instance = null;

    /**
     * Private constructor to prevent direct instantiation (Singleton pattern)
     */
    private function __construct() {
        $this->pdo = Container::get('pdo');
    }

    /**
     * Prevents cloning of the instance (Singleton pattern)
     */
    private function __clone() {}

    /**
     * Prevents unserialization of the instance (Singleton pattern)
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Get the single instance of AuthService (Singleton pattern)
     *
     * @return AuthService The singleton instance
     */
    public static function getInstance(): AuthService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function findUserByUsernameOrEmail(string $identifier) {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, created_at FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch();
    }

    /**
     * Register a new user with validation
     *
     * @param string $username User's chosen username
     * @param string $email User's email address
     * @param string $password User's password (will be hashed)
     * @return array [success: bool, error_message: string|null]
     */
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

    /**
     * Create a new user (alias for register method - matches assignment requirements)
     *
     * This method implements the required createUser() function from the assignment.
     * It delegates to the register() method for actual implementation.
     *
     * @param string $username User's chosen username
     * @param string $password User's password (will be hashed with Argon2id)
     * @return array [success: bool, error_message: string|null]
     */
    public function createUser(string $username, string $password): array {
        // Generate email from username for consistency
        $email = $username . '@localhost.local';
        return $this->register($username, $email, $password);
    }

    /**
     * Authenticate user with username/email and password (matches assignment requirements)
     *
     * This method implements the required login() function from the assignment.
     * Validates user credentials against stored data using secure password verification.
     *
     * @param string $identifier Username or email address
     * @param string $password Password to verify
     * @return array [success: bool, user_data: array|null, error_message: string|null]
     */
    public function login(string $identifier, string $password): array {
        // Handle empty credentials
        if (empty($identifier) || empty($password)) {
            return [false, null, 'Username and password are required.'];
        }

        // Find user by username or email
        $user = $this->findUserByUsernameOrEmail($identifier);

        if (!$user) {
            return [false, null, 'Invalid credentials.'];
        }

        // Verify password against stored hash (Argon2id)
        if (!password_verify($password, $user['password'])) {
            return [false, null, 'Invalid credentials.'];
        }

        // Remove password from returned user data for security
        unset($user['password']);

        return [true, $user, null];
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

