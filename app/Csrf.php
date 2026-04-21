<?php
declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        Auth::session();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function check(?string $token): bool
    {
        Auth::session();
        if (!is_string($token) || empty($_SESSION['csrf'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf'], $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function assertPost(): void
    {
        $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::check($t)) {
            http_response_code(419);
            exit('Jeton CSRF invalide');
        }
    }
}
