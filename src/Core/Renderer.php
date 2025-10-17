<?php
namespace App\Core;

class Renderer {
    public static function render(string $template, array $vars = []): void {
        extract($vars, EXTR_SKIP);
        include __DIR__ . '/../../templates/' . $template . '.php';
    }
}
?>

