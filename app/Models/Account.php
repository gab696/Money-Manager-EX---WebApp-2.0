<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Reproduit la table Account_List de l'original : la clé est le NOM.
 * Pas d'icône, pas de devise côté DB — ces champs sont purement visuels,
 * dérivés par convention (première lettre → emoji via JS).
 */
final class Account
{
    public static function all(): array
    {
        return Db::column('SELECT AccountName FROM Account_List ORDER BY AccountName COLLATE NOCASE');
    }

    public static function exists(string $name): bool
    {
        return (bool) Db::value('SELECT 1 FROM Account_List WHERE AccountName = ? COLLATE NOCASE', [$name]);
    }

    public static function insertOrIgnore(string $name): void
    {
        Db::query('INSERT OR IGNORE INTO Account_List (AccountName) VALUES (?)', [$name]);
    }

    public static function deleteAll(): void
    {
        Db::query('DELETE FROM Account_List');
    }

    /** Comptes utilisés en tête : top 3 par fréquence dans New_Transaction. */
    public static function frequent(int $limit = 3): array
    {
        return Db::column(
            'SELECT Account FROM New_Transaction
             GROUP BY Account ORDER BY COUNT(*) DESC LIMIT ' . (int) $limit
        );
    }
}
