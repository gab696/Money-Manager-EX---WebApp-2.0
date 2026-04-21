<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Invitations à rejoindre l'app.
 * L'admin crée une invitation, partage l'URL hors-bande (SMS, email…).
 * L'invité ouvre l'URL et choisit son username + password.
 */
final class Invitation
{
    public const DEFAULT_TTL_HOURS = 168; // 7 jours

    public static function create(int $createdBy, int $ttlHours = self::DEFAULT_TTL_HOURS): array
    {
        $token = bin2hex(random_bytes(24)); // 48 hex chars
        $expires = gmdate('Y-m-d H:i:s', time() + $ttlHours * 3600);
        Db::query(
            'INSERT INTO Invitations (token, created_by, expires_at) VALUES (?, ?, ?)',
            [$token, $createdBy, $expires]
        );
        return [
            'id'         => (int) Db::pdo()->lastInsertId(),
            'token'      => $token,
            'expires_at' => $expires,
        ];
    }

    public static function findByToken(string $token): ?array
    {
        return Db::one(
            'SELECT id, token, created_by, created_at, expires_at, used_at, used_by FROM Invitations WHERE token = ?',
            [$token]
        );
    }

    public static function isUsable(array $inv): bool
    {
        if (!empty($inv['used_at'])) return false;
        // expires_at stocké en UTC — on compare en UTC.
        return strtotime($inv['expires_at'] . ' UTC') > time();
    }

    public static function markUsed(int $id, int $userId): void
    {
        Db::query(
            "UPDATE Invitations SET used_at = datetime('now'), used_by = ? WHERE id = ?",
            [$userId, $id]
        );
    }

    public static function revoke(int $id): void
    {
        Db::query('DELETE FROM Invitations WHERE id = ?', [$id]);
    }

    /** Invitations actives (non utilisées et non expirées) + récemment utilisées. */
    public static function recent(): array
    {
        return Db::all(
            "SELECT i.id, i.token, i.created_at, i.expires_at, i.used_at,
                    (SELECT username FROM Users WHERE id = i.created_by) AS created_by_name,
                    (SELECT username FROM Users WHERE id = i.used_by)    AS used_by_name
               FROM Invitations i
              WHERE i.used_at IS NULL AND i.expires_at > datetime('now')
                 OR i.used_at > datetime('now','-7 days')
              ORDER BY i.created_at DESC"
        );
    }
}
