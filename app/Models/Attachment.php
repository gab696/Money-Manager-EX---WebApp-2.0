<?php
declare(strict_types=1);

namespace App\Models;

use App\Config;

/**
 * Attachments : stockés sur disque dans /attachments.
 * Convention MMEX : le nom contient l'ID de transaction, ex.
 *   tx_<ID>_<timestamp>_<basename>
 *
 * Le desktop appelle services.php?download_attachment=<filename> pour récupérer.
 */
final class Attachment
{
    public static function folder(): string
    {
        $dir = Config::get('attachments_path') ?: (dirname(__DIR__, 2) . '/attachments');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    public static function forTransaction(int $txId): array
    {
        $dir = self::folder();
        if (!is_dir($dir)) return [];
        $prefix = 'tx_' . $txId . '_';
        $files = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_starts_with($f, $prefix)) $files[] = $f;
        }
        sort($files);
        return $files;
    }

    public static function deleteByName(string $filename): void
    {
        $filename = basename($filename); // pas de traversal
        $path = self::folder() . '/' . $filename;
        if (is_file($path)) @unlink($path);
    }

    public static function deleteForTransactions(array $ids): void
    {
        foreach ($ids as $id) {
            foreach (self::forTransaction((int) $id) as $f) {
                self::deleteByName($f);
            }
        }
    }

    public static function path(string $filename): ?string
    {
        $filename = basename($filename);
        $p = self::folder() . '/' . $filename;
        return is_file($p) ? $p : null;
    }
}
