<?php
namespace App\Core;

class Container {
    private static $registry = [];

    public static function set(string $key, $value): void {
        self::$registry[$key] = $value;
    }

    public static function get(string $key) {
        return self::$registry[$key] ?? null;
    }
}
?>

