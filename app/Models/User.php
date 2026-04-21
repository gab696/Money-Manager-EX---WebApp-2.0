<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Table Users : extension MMEX Web pour le multi-utilisateur.
 *
 * - Nouveaux mots de passe hashés en bcrypt (password_hash).
 * - Anciens mots de passe issus de la migration depuis Parameters restent en
 *   sha512 hex ; lors d'un login réussi on les rehash en bcrypt de façon
 *   transparente (voir verify()).
 */
final class User
{
    public static function all(): array
    {
        return Db::all('SELECT id, username, created_at, active, is_admin, donor_hidden FROM Users ORDER BY is_admin DESC, username COLLATE NOCASE');
    }

    public static function activeUsers(): array
    {
        return Db::all('SELECT id, username FROM Users WHERE active = 1 ORDER BY username COLLATE NOCASE');
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT id, username, password_hash, active, is_admin, donor_hidden FROM Users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Db::one('SELECT id, username, password_hash, active, is_admin, donor_hidden FROM Users WHERE username = ? COLLATE NOCASE', [$username]);
    }

    public static function setDonorHidden(int $id, bool $hidden): void
    {
        Db::query('UPDATE Users SET donor_hidden = ? WHERE id = ?', [$hidden ? 1 : 0, $id]);
    }

    public static function isDonorHidden(int $id): bool
    {
        $u = self::find($id);
        return $u !== null && (int) $u['donor_hidden'] === 1;
    }

    public static function create(string $username, string $password, bool $isAdmin = false): int
    {
        // Le tout premier utilisateur est admin d'office.
        if (self::count() === 0) {
            $isAdmin = true;
        }
        Db::query('INSERT INTO Users (username, password_hash, is_admin) VALUES (?, ?, ?)', [
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $isAdmin ? 1 : 0,
        ]);
        return (int) Db::pdo()->lastInsertId();
    }

    public static function isAdmin(int $id): bool
    {
        $u = self::find($id);
        return $u !== null && (int) $u['is_admin'] === 1;
    }

    public static function adminCount(): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM Users WHERE is_admin = 1 AND active = 1');
    }

    public static function setPassword(int $id, string $password): void
    {
        Db::query('UPDATE Users SET password_hash = ? WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Db::query('DELETE FROM Users WHERE id = ?', [$id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Db::query('UPDATE Users SET active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public static function count(): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM Users');
    }

    public static function activeCount(): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM Users WHERE active = 1');
    }

    /**
     * Vérifie le mot de passe. Supporte les hash bcrypt (nouveaux) et sha512 hex
     * (migration depuis l'ancien stockage Parameters).
     * En cas de succès sur sha512, re-hash en bcrypt pour upgrader.
     */
    public static function verify(array $user, string $password): bool
    {
        $hash = (string) $user['password_hash'];

        // bcrypt / argon2 : commence par $
        if (str_starts_with($hash, '$')) {
            return password_verify($password, $hash);
        }

        // sha512 hex (128 car.) — legacy
        if (strlen($hash) === 128 && ctype_xdigit($hash)) {
            if (hash_equals($hash, hash('sha512', $password))) {
                // Upgrade transparent vers bcrypt
                self::setPassword((int) $user['id'], $password);
                return true;
            }
        }
        return false;
    }
}
