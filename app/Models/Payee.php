<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

final class Payee
{
    public static function all(): array
    {
        return Db::all('SELECT PayeeName, DefCateg, DefSubCateg FROM Payee_List ORDER BY PayeeName COLLATE NOCASE');
    }

    public static function names(): array
    {
        return Db::column('SELECT PayeeName FROM Payee_List ORDER BY PayeeName COLLATE NOCASE');
    }

    public static function find(string $name): ?array
    {
        return Db::one('SELECT PayeeName, DefCateg, DefSubCateg FROM Payee_List WHERE PayeeName = ? COLLATE NOCASE', [$name]);
    }

    public static function create(string $name, ?string $defCateg = null, ?string $defSub = null): void
    {
        Db::query(
            'INSERT OR IGNORE INTO Payee_List (PayeeName, DefCateg, DefSubCateg) VALUES (?, ?, ?)',
            [$name, $defCateg, $defSub]
        );
    }

    public static function deleteAll(): void
    {
        Db::query('DELETE FROM Payee_List');
    }

    public static function frequent(int $limit = 3): array
    {
        return Db::column(
            'SELECT Payee FROM New_Transaction
             WHERE Payee IS NOT NULL AND Payee != ""
             GROUP BY Payee ORDER BY COUNT(*) DESC LIMIT ' . (int) $limit
        );
    }
}
