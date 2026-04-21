<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Table Category_List : chaque ligne = (CategoryName, SubCategoryName).
 * Une "catégorie parent" seule apparaît avec SubCategoryName = 'None' ou NULL.
 */
final class Category
{
    public static function all(): array
    {
        return Db::all('SELECT CategoryName, SubCategoryName FROM Category_List ORDER BY CategoryName COLLATE NOCASE, SubCategoryName COLLATE NOCASE');
    }

    public static function parents(): array
    {
        return Db::column('SELECT DISTINCT CategoryName FROM Category_List ORDER BY CategoryName COLLATE NOCASE');
    }

    public static function subs(string $parent): array
    {
        return Db::column(
            'SELECT SubCategoryName FROM Category_List
             WHERE CategoryName = ? COLLATE NOCASE AND SubCategoryName IS NOT NULL AND SubCategoryName != "" AND SubCategoryName != "None"
             ORDER BY SubCategoryName COLLATE NOCASE',
            [$parent]
        );
    }

    /** Liste aplatie des "feuilles" (Category + SubCategory) pour la bottom-sheet. */
    public static function leaves(): array
    {
        $rows = Db::all(
            'SELECT CategoryName, SubCategoryName FROM Category_List
             WHERE SubCategoryName IS NOT NULL AND SubCategoryName != "" AND SubCategoryName != "None"
             ORDER BY CategoryName COLLATE NOCASE, SubCategoryName COLLATE NOCASE'
        );
        return $rows;
    }

    public static function insertOrIgnore(string $category, ?string $sub = null): void
    {
        Db::query(
            'INSERT OR IGNORE INTO Category_List (CategoryName, SubCategoryName) VALUES (?, ?)',
            [$category, $sub]
        );
    }

    public static function deleteAll(): void
    {
        Db::query('DELETE FROM Category_List');
    }

    public static function frequent(int $limit = 3): array
    {
        // Retourne les (Category, SubCategory) les plus utilisées
        return Db::all(
            'SELECT Category, SubCategory FROM New_Transaction
             WHERE Category IS NOT NULL AND Category != ""
             GROUP BY Category, SubCategory
             ORDER BY COUNT(*) DESC LIMIT ' . (int) $limit
        );
    }
}
