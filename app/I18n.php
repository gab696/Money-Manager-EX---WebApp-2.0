<?php
declare(strict_types=1);

namespace App;

/**
 * Internationalisation minimale sans dépendance externe.
 *
 * Usage dans les vues : <?= I18n::t('settings.title') ?>
 * Fallback : si la clé n'existe pas dans la langue active, on prend l'EN,
 * et en dernier recours on renvoie la clé elle-même.
 */
final class I18n
{
    public const LOCALES = [
        'fr' => 'Français',
        'en' => 'English',
    ];

    private static string $locale = 'fr';
    /** @var array<string, mixed> */
    private static array $strings = [];
    /** @var array<string, mixed> */
    private static array $fallback = [];

    public static function setLocale(string $locale): void
    {
        if (!isset(self::LOCALES[$locale])) {
            $locale = 'fr';
        }
        self::$locale   = $locale;
        self::$strings  = self::loadFile($locale);
        self::$fallback = self::$strings; // par défaut fallback = locale courante
        if ($locale !== 'en') {
            self::$fallback = self::loadFile('en') ?: self::$strings;
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function t(string $key, string $default = ''): string
    {
        return self::lookup(self::$strings, $key)
            ?? self::lookup(self::$fallback, $key)
            ?? ($default !== '' ? $default : $key);
    }

    private static function lookup(array $data, string $key): ?string
    {
        $ref = $data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($ref) || !array_key_exists($part, $ref)) {
                return null;
            }
            $ref = $ref[$part];
        }
        return is_string($ref) ? $ref : null;
    }

    private static function loadFile(string $locale): array
    {
        $path = __DIR__ . '/Lang/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }
        $data = require $path;
        return is_array($data) ? $data : [];
    }
}
