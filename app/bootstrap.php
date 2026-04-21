<?php
declare(strict_types=1);

// Autoload minimal PSR-4 pour le namespace App\
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $relative = str_replace('\\', '/', substr($class, 4));
    $path = __DIR__ . '/' . $relative . '.php';
    if (is_file($path)) require $path;
});

// Config.php est optionnel : si absent, on charge des défauts.
$configPath = dirname(__DIR__) . '/config.php';
if (is_file($configPath)) {
    \App\Config::loadFile($configPath);
} else {
    \App\Config::loadArray([
        'app' => [
            'name'         => 'MMEX Web',
            'base_url'     => '',
            'timezone'     => 'Europe/Zurich',
            'debug'        => false,
            'session_name' => 'mmex_sid',
        ],
        'db' => ['path' => null],
        'attachments_path' => null,
    ]);
}

date_default_timezone_set((string) \App\Config::get('app.timezone', 'UTC'));

if (\App\Config::get('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

// Chargement de la langue (default FR, override par Parameters.Language
// qui peut être changé depuis l'écran Réglages).
// On ne touche pas la DB ici ; le chargement se fait paresseusement dans
// I18n::setLocale — le premier contrôleur qui utilise la DB peut appeler
// I18n::setLocale(Parameter::get('Language','fr')).
\App\I18n::setLocale('fr');
