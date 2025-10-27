<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Auth\AuthService;
use App\Core\Container;
use PDO;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Get PDO from container
        $this->pdo = Container::get('pdo');
        // Use Singleton pattern to get AuthService instance
        $this->authService = AuthService::getInstance();

        // Clean up test data before each test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        // Delete all test users and sessions
        $this->pdo->exec("DELETE FROM user_sessions WHERE 1=1");
        $this->pdo->exec("DELETE FROM users WHERE 1=1");
    }

    /**
     * Test Case 1: Test a successful login with valid credentials
     */
    public function testSuccessfulLoginWithValidCredentials(): void
    {
        // Arrange: Create a test user
        $username = 'testuser';
        $email = 'test@example.com';
        $password = 'ValidPass123!';

        [$success, $error] = $this->authService->register($username, $email, $password);
        $this->assertTrue($success, 'User registration should succeed');

        // Act: Attempt to login with valid credentials
        $user = $this->authService->findUserByUsernameOrEmail($username);
        $loginSuccess = $user && password_verify($password, $user['password']);

        // Assert: Login should succeed
        $this->assertTrue($loginSuccess, 'Login with valid credentials should succeed');
        $this->assertNotNull($user);
        $this->assertEquals($username, $user['username']);
        $this->assertEquals($email, $user['email']);
    }

    /**
     * Test Case 2: Test an unsuccessful login with an incorrect password
     */
    public function testUnsuccessfulLoginWithIncorrectPassword(): void
    {
        // Arrange: Create a test user
        $username = 'testuser';
        $email = 'test@example.com';
        $password = 'ValidPass123!';
        $wrongPassword = 'WrongPass456!';

        [$success, $error] = $this->authService->register($username, $email, $password);
        $this->assertTrue($success, 'User registration should succeed');

        // Act: Attempt to login with incorrect password
        $user = $this->authService->findUserByUsernameOrEmail($username);
        $loginSuccess = $user && password_verify($wrongPassword, $user['password']);

        // Assert: Login should fail
        $this->assertFalse($loginSuccess, 'Login with incorrect password should fail');
    }

    /**
     * Test Case 3: Test an unsuccessful login with a nonexistent username
     */
    public function testUnsuccessfulLoginWithNonexistentUsername(): void
    {
        // Act: Attempt to login with a username that doesn't exist
        $user = $this->authService->findUserByUsernameOrEmail('nonexistentuser');

        // Assert: User should not be found
        $this->assertFalse($user, 'Finding a nonexistent user should return false');
    }

    /**
     * Test Case 4: Test a successful login after creating a new user
     */
    public function testSuccessfulLoginAfterCreatingNewUser(): void
    {
        // Arrange & Act: Create a new user
        $username = 'newuser';
        $email = 'newuser@example.com';
        $password = 'NewUserPass123!';

        [$registerSuccess, $error] = $this->authService->register($username, $email, $password);

        // Assert: Registration should succeed
        $this->assertTrue($registerSuccess, 'User registration should succeed');
        $this->assertNull($error, 'Registration error should be null');

        // Act: Immediately try to login with the newly created user
        $user = $this->authService->findUserByUsernameOrEmail($username);
        $loginSuccess = $user && password_verify($password, $user['password']);

        // Assert: Login should succeed
        $this->assertTrue($loginSuccess, 'Login after creating new user should succeed');
        $this->assertEquals($username, $user['username']);
        $this->assertEquals($email, $user['email']);
    }

    /**
     * Test Case 5: Test an unsuccessful login with an empty password
     */
    public function testUnsuccessfulLoginWithEmptyPassword(): void
    {
        // Arrange: Try to create a user with empty password
        $username = 'testuser';
        $email = 'test@example.com';
        $emptyPassword = '';

        [$registerSuccess, $error] = $this->authService->register($username, $email, $emptyPassword);

        // Assert: Registration should fail with empty password
        $this->assertFalse($registerSuccess, 'Registration with empty password should fail');
        $this->assertNotNull($error, 'Registration error message should be provided');
        $this->assertStringContainsString('Password', $error, 'Error should mention password');

        // Act: Try to login with empty password (user doesn't exist)
        $user = $this->authService->findUserByUsernameOrEmail($username);

        // Assert: User should not exist (registration failed)
        $this->assertFalse($user, 'User with empty password should not exist');

        // Additional test: Create a valid user and try to login with empty password
        $validPassword = 'ValidPass123!';
        [$registerSuccess2, $error2] = $this->authService->register($username, $email, $validPassword);
        $this->assertTrue($registerSuccess2, 'User registration with valid password should succeed');

        $user2 = $this->authService->findUserByUsernameOrEmail($username);
        $loginSuccess = $user2 && password_verify('', $user2['password']);

        // Assert: Login with empty password should fail
        $this->assertFalse($loginSuccess, 'Login with empty password should fail');
    }

    /**
     * Additional Test: Test username uniqueness
     */
    public function testUsernameUniqueness(): void
    {
        // Arrange: Create first user
        $username = 'testuser';
        $email1 = 'test1@example.com';
        $email2 = 'test2@example.com';
        $password = 'ValidPass123!';

        [$success1, $error1] = $this->authService->register($username, $email1, $password);
        $this->assertTrue($success1, 'First user registration should succeed');

        // Act: Try to create another user with the same username
        [$success2, $error2] = $this->authService->register($username, $email2, $password);

        // Assert: Second registration should fail
        $this->assertFalse($success2, 'Second registration with duplicate username should fail');
        $this->assertNotNull($error2, 'Error message should be provided');
        $this->assertStringContainsString('already exists', $error2, 'Error should mention username already exists');
    }

    /**
     * Additional Test: Test secure password storage (Argon2id)
     */
    public function testSecurePasswordStorage(): void
    {
        // Arrange & Act: Create a user
        $username = 'testuser';
        $email = 'test@example.com';
        $password = 'ValidPass123!';

        [$success, $error] = $this->authService->register($username, $email, $password);
        $this->assertTrue($success, 'User registration should succeed');

        // Get the stored password hash
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();

        // Assert: Password should be hashed with Argon2id
        $this->assertNotEquals($password, $result['password'], 'Password should be hashed, not plain text');
        $this->assertStringStartsWith('$argon2id$', $result['password'], 'Password should use Argon2id hashing');

        // Verify password can be verified correctly
        $this->assertTrue(password_verify($password, $result['password']), 'Password verification should succeed');
    }
}
