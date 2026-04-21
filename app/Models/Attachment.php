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

    public const MAX_SIZE = 8 * 1024 * 1024; // 8 MB par fichier
    public const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'image/heic', 'image/heif', 'application/pdf',
    ];

    /**
     * Enregistre un fichier téléversé pour une transaction.
     * Nomenclature : tx_<ID>_<timestamp>_<basename_safe>
     * Retourne le nom du fichier stocké, ou null en cas d'erreur.
     */
    public static function store(int $txId, array $file): ?string
    {
        if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::MAX_SIZE) {
            return null;
        }

        // Vérification MIME par contenu (finfo plus fiable que $file['type'])
        $mime = null;
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($file['tmp_name']) ?: null;
        }
        if ($mime && !in_array($mime, self::ALLOWED_MIMES, true)) {
            return null;
        }

        $orig = basename((string) ($file['name'] ?? 'file'));
        // Garde que alphanum, ., -, _  ; limite longueur
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $orig);
        $safe = substr($safe, 0, 80);
        if ($safe === '' || $safe[0] === '.') {
            $safe = 'file_' . $safe;
        }

        $ts = date('YmdHis');
        $newName = sprintf('tx_%d_%s_%s', $txId, $ts, $safe);
        $dest = self::folder() . '/' . $newName;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }
        @chmod($dest, 0644);
        return $newName;
    }

    /**
     * Métadonnées affichables d'une pièce jointe : nom logique et type.
     */
    public static function info(string $filename): array
    {
        $filename = basename($filename);
        // Enlève le préfixe tx_<ID>_<ts>_ pour afficher le nom d'origine
        $display = preg_replace('/^tx_\d+_\d{14}_/', '', $filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'], true);
        return [
            'filename' => $filename,
            'display'  => $display,
            'ext'      => $ext,
            'is_image' => $isImage,
        ];
    }
}
