<?php
/**
 * Manual Test Runner
 * Run this file directly to execute all tests without PHPUnit
 */

// Start session
session_start();

// Load database and dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Core/Container.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Security/Validator.php';

use App\Core\Container;
use App\Auth\AuthService;

// Set up test database connection
$test_host = 'localhost';
$test_dbname = 'login_system_test';
$test_username = 'root';
$test_password = 'shadrack21';

try {
    $test_pdo = new PDO("mysql:host=$test_host;dbname=$test_dbname;charset=utf8", $test_username, $test_password);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $test_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    Container::set('pdo', $test_pdo);
} catch(PDOException $e) {
    die("❌ Test database connection failed: " . $e->getMessage() . "\n\nPlease run: mysql -u root -p < tests/setup_test_db.sql\n");
}

// Test statistics
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$assertions = 0;

// Helper functions
function cleanDatabase($pdo) {
    $pdo->exec("DELETE FROM user_sessions WHERE 1=1");
    $pdo->exec("DELETE FROM users WHERE 1=1");
}

function assertTrue($condition, $message) {
    global $assertions;
    $assertions++;
    if (!$condition) {
        throw new Exception("Assertion failed: $message");
    }
}

function assertFalse($condition, $message) {
    global $assertions;
    $assertions++;
    if ($condition) {
        throw new Exception("Assertion failed: $message");
    }
}

function assertNotNull($value, $message) {
    global $assertions;
    $assertions++;
    if ($value === null) {
        throw new Exception("Assertion failed: $message");
    }
}

function assertEquals($expected, $actual, $message) {
    global $assertions;
    $assertions++;
    if ($expected !== $actual) {
        throw new Exception("Assertion failed: $message. Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
    }
}

function assertStringStartsWith($prefix, $string, $message) {
    global $assertions;
    $assertions++;
    if (strpos($string, $prefix) !== 0) {
        throw new Exception("Assertion failed: $message. String does not start with '$prefix': $string");
    }
}

function assertStringContainsString($needle, $haystack, $message) {
    global $assertions;
    $assertions++;
    if (strpos($haystack, $needle) === false) {
        throw new Exception("Assertion failed: $message. '$needle' not found in '$haystack'");
    }
}

function runTest($testName, $testFunction) {
    global $total_tests, $passed_tests, $failed_tests, $test_pdo;

    $total_tests++;
    echo "\n🧪 Test $total_tests: $testName\n";

    try {
        // Clean database before test
        cleanDatabase($test_pdo);

        // Run test
        $testFunction();

        // Clean database after test
        cleanDatabase($test_pdo);

        $passed_tests++;
        echo "   ✅ PASSED\n";
    } catch (Exception $e) {
        $failed_tests++;
        echo "   ❌ FAILED: " . $e->getMessage() . "\n";
    }
}

// Print header
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        Login System Test Suite - Manual Test Runner           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1: Successful login with valid credentials
runTest("Successful Login with Valid Credentials", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange: Create a test user
    $username = 'testuser';
    $email = 'test@example.com';
    $password = 'ValidPass123!';

    [$success, $error] = $auth->register($username, $email, $password);
    assertTrue($success, 'User registration should succeed');

    // Act: Attempt to login with valid credentials
    $user = $auth->findUserByUsernameOrEmail($username);
    $loginSuccess = $user && password_verify($password, $user['password']);

    // Assert: Login should succeed
    assertTrue($loginSuccess, 'Login with valid credentials should succeed');
    assertNotNull($user, 'User should be found');
    assertEquals($username, $user['username'], 'Username should match');
    assertEquals($email, $user['email'], 'Email should match');
});

// Test 2: Unsuccessful login with incorrect password
runTest("Unsuccessful Login with Incorrect Password", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange: Create a test user
    $username = 'testuser';
    $email = 'test@example.com';
    $password = 'ValidPass123!';
    $wrongPassword = 'WrongPass456!';

    [$success, $error] = $auth->register($username, $email, $password);
    assertTrue($success, 'User registration should succeed');

    // Act: Attempt to login with incorrect password
    $user = $auth->findUserByUsernameOrEmail($username);
    $loginSuccess = $user && password_verify($wrongPassword, $user['password']);

    // Assert: Login should fail
    assertFalse($loginSuccess, 'Login with incorrect password should fail');
});

// Test 3: Unsuccessful login with nonexistent username
runTest("Unsuccessful Login with Nonexistent Username", function() use ($test_pdo) {
    $auth = new AuthService();

    // Act: Attempt to login with a username that doesn't exist
    $user = $auth->findUserByUsernameOrEmail('nonexistentuser');

    // Assert: User should not be found
    assertFalse($user, 'Finding a nonexistent user should return false');
});

// Test 4: Successful login after creating new user
runTest("Successful Login After Creating New User", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange & Act: Create a new user
    $username = 'newuser';
    $email = 'newuser@example.com';
    $password = 'NewUserPass123!';

    [$registerSuccess, $error] = $auth->register($username, $email, $password);

    // Assert: Registration should succeed
    assertTrue($registerSuccess, 'User registration should succeed');
    assertNotNull(!$error, 'Registration error should be null');

    // Act: Immediately try to login with the newly created user
    $user = $auth->findUserByUsernameOrEmail($username);
    $loginSuccess = $user && password_verify($password, $user['password']);

    // Assert: Login should succeed
    assertTrue($loginSuccess, 'Login after creating new user should succeed');
    assertEquals($username, $user['username'], 'Username should match');
    assertEquals($email, $user['email'], 'Email should match');
});

// Test 5: Unsuccessful login with empty password
runTest("Unsuccessful Login with Empty Password", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange: Try to create a user with empty password
    $username = 'testuser';
    $email = 'test@example.com';
    $emptyPassword = '';

    [$registerSuccess, $error] = $auth->register($username, $email, $emptyPassword);

    // Assert: Registration should fail with empty password
    assertFalse($registerSuccess, 'Registration with empty password should fail');
    assertNotNull($error, 'Registration error message should be provided');
    assertStringContainsString('Password', $error, 'Error should mention password');

    // Act: Try to login with empty password (user doesn't exist)
    $user = $auth->findUserByUsernameOrEmail($username);

    // Assert: User should not exist (registration failed)
    assertFalse($user, 'User with empty password should not exist');

    // Additional test: Create a valid user and try to login with empty password
    $validPassword = 'ValidPass123!';
    [$registerSuccess2, $error2] = $auth->register($username, $email, $validPassword);
    assertTrue($registerSuccess2, 'User registration with valid password should succeed');

    $user2 = $auth->findUserByUsernameOrEmail($username);
    $loginSuccess = $user2 && password_verify('', $user2['password']);

    // Assert: Login with empty password should fail
    assertFalse($loginSuccess, 'Login with empty password should fail');
});

// Test 6: Username uniqueness
runTest("Username Uniqueness", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange: Create first user
    $username = 'testuser';
    $email1 = 'test1@example.com';
    $email2 = 'test2@example.com';
    $password = 'ValidPass123!';

    [$success1, $error1] = $auth->register($username, $email1, $password);
    assertTrue($success1, 'First user registration should succeed');

    // Act: Try to create another user with the same username
    [$success2, $error2] = $auth->register($username, $email2, $password);

    // Assert: Second registration should fail
    assertFalse($success2, 'Second registration with duplicate username should fail');
    assertNotNull($error2, 'Error message should be provided');
    assertStringContainsString('already exists', $error2, 'Error should mention username already exists');
});

// Test 7: Secure password storage (Argon2id)
runTest("Secure Password Storage (Argon2id)", function() use ($test_pdo) {
    $auth = new AuthService();

    // Arrange & Act: Create a user
    $username = 'testuser';
    $email = 'test@example.com';
    $password = 'ValidPass123!';

    [$success, $error] = $auth->register($username, $email, $password);
    assertTrue($success, 'User registration should succeed');

    // Get the stored password hash
    $stmt = $test_pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();

    // Assert: Password should be hashed with Argon2id
    assertTrue($result['password'] !== $password, 'Password should be hashed, not plain text');
    assertStringStartsWith('$argon2id$', $result['password'], 'Password should use Argon2id hashing');

    // Verify password can be verified correctly
    assertTrue(password_verify($password, $result['password']), 'Password verification should succeed');
});

// Print results
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                         Test Results                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total Tests:    $total_tests\n";
echo "✅ Passed:      $passed_tests\n";
echo "❌ Failed:      $failed_tests\n";
echo "Assertions:     $assertions\n";
echo "\n";

if ($failed_tests === 0) {
    echo "🎉 All tests passed! Your login system meets all requirements.\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n\n";
    exit(1);
}
