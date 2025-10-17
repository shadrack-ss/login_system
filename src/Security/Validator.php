<?php
namespace App\Security;

class Validator {
    public static function isValidUsername(string $username): bool {
        return (bool)preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username);
    }

    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isStrongPassword(string $password): bool {
        return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);
    }
}
?>

