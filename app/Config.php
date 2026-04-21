<?php
declare(strict_types=1);

namespace App;

final class Config
{
    private static ?array $data = null;

    public static function loadFile(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Config introuvable : $path");
        }
        self::$data = require $path;
    }

    public static function loadArray(array $data): void
    {
        self::$data = $data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $ref = self::$data ?? [];
        foreach (explode('.', $key) as $part) {
            if (!is_array($ref) || !array_key_exists($part, $ref)) {
                return $default;
            }
            $ref = $ref[$part];
        }
        return $ref;
    }

    public static function baseUrl(): string
    {
        return rtrim((string) self::get('app.base_url', ''), '/');
    }

    public static function url(string $path = '/'): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }
}
