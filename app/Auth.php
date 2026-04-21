<?php
declare(strict_types=1);

namespace App;

use App\Models\Parameter;
use App\Models\User;

/**
 * Auth multi-utilisateur.
 * Les comptes vivent dans la table `Users`.
 * Le GUID de sync reste dans `Parameters.DesktopGuid` (non lié aux users).
 */
final class Auth
{
    public static function session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_name((string) Config::get('app.session_name', 'mmex_sid'));
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $base = Config::baseUrl();
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30,
            'path'     => $base === '' ? '/' : $base . '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(array $user): void
    {
        self::session();
        session_regenerate_id(true);
        $_SESSION['uid']      = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['login_at'] = time();
    }

    public static function logout(): void
    {
        self::session();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) ($p['secure'] ?? false), (bool) ($p['httponly'] ?? false));
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::session();
        if (Parameter::get('DisableAuthentication') === 'True') return true;
        if (empty($_SESSION['uid'])) return false;
        // Vérifie que l'utilisateur existe toujours et est actif
        $u = User::find((int) $_SESSION['uid']);
        return $u && (int) $u['active'] === 1;
    }

    public static function username(): ?string
    {
        self::session();
        return $_SESSION['username'] ?? null;
    }

    public static function id(): ?int
    {
        self::session();
        return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
    }

    public static function isAdmin(): bool
    {
        $id = self::id();
        return $id !== null && User::isAdmin($id);
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Accès réservé à l\'administrateur.';
            exit;
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . Config::url('/login'));
            exit;
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        if (!$user || (int) $user['active'] !== 1) return false;
        if (!User::verify($user, $password)) return false;
        self::login($user);
        return true;
    }

    public static function hasAdmin(): bool
    {
        return User::count() > 0;
    }

    public static function createAdmin(string $username, string $password): void
    {
        User::create($username, $password);
    }
}
