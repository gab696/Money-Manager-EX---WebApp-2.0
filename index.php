<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\I18n;
use App\Models\Parameter;
use App\Router;
use App\Config;

// Charge la langue utilisateur une fois la DB garantie.
try {
    Parameter::ensureDefaults();
    I18n::setLocale(Parameter::get('Language', 'fr') ?? 'fr');
} catch (\Throwable $e) {
    // DB indisponible → on reste en français par défaut.
}
use App\Controllers\AuthController;
use App\Controllers\TransactionController;
use App\Controllers\QueueController;
use App\Controllers\ListsController;
use App\Controllers\SettingsController;

$router = new Router();

// Racine → login (qui bascule vers /setup si pas d'admin, sinon /new)
$router->get('/', function () {
    header('Location: ' . Config::url('/login'));
});

// First-run setup (affiché si aucun admin dans Parameters)
$router->get ('/setup', [AuthController::class, 'showSetup']);
$router->post('/setup', [AuthController::class, 'doSetup']);

// Auth
$router->get ('/login',  [AuthController::class, 'showLogin']);
$router->post('/login',  [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Saisie
$router->get ('/new',                     [TransactionController::class, 'showNew']);
$router->get ('/transaction/{id}/edit',   [TransactionController::class, 'showEdit']);
$router->post('/transaction',             [TransactionController::class, 'save']);
$router->post('/transaction/{id}/delete', [TransactionController::class, 'delete']);
$router->post('/transaction/{id}/attachment/delete', [TransactionController::class, 'deleteAttachment']);
$router->get ('/attachments/{filename}',  [TransactionController::class, 'downloadAttachment']);

// File d'attente (tx pas encore aspirées par le desktop)
$router->get('/queue', [QueueController::class, 'index']);

// Listes pour les bottom-sheets
$router->get ('/api/lists',   [ListsController::class, 'index']);
$router->post('/api/payees',  [ListsController::class, 'createPayee']);
$router->post('/api/accounts',  [ListsController::class, 'createAccount']);
$router->post('/api/categories',[ListsController::class, 'createCategory']);

// Réglages
$router->get ('/settings',          [SettingsController::class, 'index']);
$router->post('/settings/password',            [SettingsController::class, 'changePassword']);
$router->post('/settings/guid',                [SettingsController::class, 'regenerateGuid']);
$router->post('/settings/preferences',         [SettingsController::class, 'updatePreferences']);
$router->post('/settings/users/{id}/delete',   [SettingsController::class, 'deleteUser']);
$router->post('/settings/users/{id}/password', [SettingsController::class, 'resetUserPassword']);
$router->post('/settings/invitations',         [SettingsController::class, 'createInvitation']);
$router->post('/settings/invitations/{id}/revoke', [SettingsController::class, 'revokeInvitation']);
$router->post('/settings/donation',            [SettingsController::class, 'toggleDonation']);

// Invitations publiques (token dans l'URL)
$router->get ('/invite/{token}', [AuthController::class, 'showInvite']);
$router->post('/invite/{token}', [AuthController::class, 'acceptInvite']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
