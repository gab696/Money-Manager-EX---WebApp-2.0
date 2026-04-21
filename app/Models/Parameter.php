<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Accès à la table Parameters (clé/valeur).
 * Contient notamment : Version, Username, Password (sha512), DesktopGuid,
 * DisableAuthentication, DisablePayee, DisableCategory, DefaultAccount,
 * DefaultStatus, DefaultType, Language, LastSyncAt.
 */
final class Parameter
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $v = Db::value('SELECT Value FROM Parameters WHERE Parameter = ?', [$key]);
        if ($v === false || $v === null) return $default;
        return (string) $v;
    }

    public static function set(string $key, ?string $value): void
    {
        // INSERT OR REPLACE : compatible SQLite < 3.24 (upsert "ON CONFLICT DO UPDATE"
        // n'est apparu qu'en 3.24, pas dispo sur certains mutualisés).
        Db::query(
            'INSERT OR REPLACE INTO Parameters (Parameter, Value) VALUES (?, ?)',
            [$key, $value]
        );
    }

    public static function all(): array
    {
        $rows = Db::all('SELECT Parameter, Value FROM Parameters');
        $out = [];
        foreach ($rows as $r) $out[$r['Parameter']] = $r['Value'];
        return $out;
    }

    public static function ensureDefaults(): void
    {
        $defaults = [
            'DesktopGuid'            => self::generateGuid(),
            'DisableAuthentication'  => 'False',
            'DisablePayee'           => 'False',
            'DisableCategory'        => 'False',
            'DefaultAccount'         => '',
            'DefaultStatus'          => 'N',   // N = None / Non pointé
            'DefaultType'            => 'Withdrawal',
            'Language'               => 'fr',
            // Champs personnalisés (via Notes, le desktop ne supporte pas vraiment)
            'CustomFieldsEnabled'    => 'False',
            'CustomFieldsLabel'      => 'Par',
            'CustomFieldsName'       => '',   // vide = on utilise le login
        ];
        foreach ($defaults as $k => $v) {
            if (self::get($k) === null) self::set($k, $v);
        }
        // Migration : ancien défaut 'F' (Follow-up) → 'N' (Non pointé)
        if (self::get('DefaultStatus') === 'F') {
            self::set('DefaultStatus', 'N');
        }
    }

    public static function generateGuid(): string
    {
        // Format original MMEX : {XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $hex = strtoupper(bin2hex($b));
        return '{' . substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' .
                    substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' .
                    substr($hex, 20, 12) . '}';
    }
}
