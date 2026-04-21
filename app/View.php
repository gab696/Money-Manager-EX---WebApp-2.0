<?php
declare(strict_types=1);

namespace App;

final class View
{
    private static string $root = __DIR__ . '/Views';

    public static function render(string $template, array $data = []): string
    {
        $file = self::$root . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Vue introuvable : {$template}");
        }
        $data['csrf']    = Csrf::token();
        $data['baseUrl'] = Config::baseUrl();
        $data['locale']  = I18n::locale();
        $data['e']  = fn($s) => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
        $data['url'] = fn($p) => Config::url($p);
        $data['t']  = fn($k, $d = '') => I18n::t($k, $d);

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function layout(string $template, array $data = []): string
    {
        $data['__body'] = self::render($template, $data);
        return self::render('layout/app', $data);
    }

    public static function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function e(mixed $s): string
    {
        return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
