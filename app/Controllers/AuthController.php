<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Csrf;
use App\Models\Invitation;
use App\Models\Parameter;
use App\Models\User;
use App\View;

final class AuthController
{
    public function showLogin(): void
    {
        Parameter::ensureDefaults();

        // Premier lancement : pas d'admin → on bascule vers setup
        if (!Auth::hasAdmin()) {
            header('Location: ' . Config::url('/setup'));
            return;
        }
        if (Auth::check()) {
            header('Location: ' . Config::url('/new'));
            return;
        }
        echo View::render('login', [
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function login(): void
    {
        Csrf::assertPost();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '' || !Auth::attempt($username, $password)) {
            header('Location: ' . Config::url('/login?error=1'));
            return;
        }
        header('Location: ' . Config::url('/new'));
    }

    public function logout(): void
    {
        Csrf::assertPost();
        Auth::logout();
        header('Location: ' . Config::url('/login'));
    }

    public function showSetup(): void
    {
        Parameter::ensureDefaults();
        if (Auth::hasAdmin()) {
            header('Location: ' . Config::url('/login'));
            return;
        }
        echo View::render('setup');
    }

    public function doSetup(): void
    {
        Csrf::assertPost();
        if (Auth::hasAdmin()) {
            header('Location: ' . Config::url('/login'));
            return;
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || strlen($password) < 4) {
            header('Location: ' . Config::url('/setup?error=1'));
            return;
        }
        Auth::createAdmin($username, $password);
        header('Location: ' . Config::url('/login?setup=1'));
    }

    // ========== Invitations ==========

    public function showInvite(array $params): void
    {
        $inv = Invitation::findByToken((string) $params['token']);
        if (!$inv || !Invitation::isUsable($inv)) {
            echo View::render('invite', [
                'error' => 'invalid',
                'token' => $params['token'],
            ]);
            return;
        }
        echo View::render('invite', [
            'error' => $_GET['error'] ?? null,
            'token' => $params['token'],
        ]);
    }

    public function acceptInvite(array $params): void
    {
        Csrf::assertPost();
        $token = (string) $params['token'];
        $inv = Invitation::findByToken($token);
        if (!$inv || !Invitation::isUsable($inv)) {
            header('Location: ' . Config::url('/invite/' . $token . '?error=expired'));
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || strlen($password) < 4) {
            header('Location: ' . Config::url('/invite/' . $token . '?error=invalid'));
            return;
        }
        if (User::findByUsername($username)) {
            header('Location: ' . Config::url('/invite/' . $token . '?error=exists'));
            return;
        }

        $userId = User::create($username, $password, false);
        Invitation::markUsed((int) $inv['id'], $userId);

        // Auto-login
        $u = User::find($userId);
        if ($u) Auth::login($u);
        header('Location: ' . Config::url('/new'));
    }
}
