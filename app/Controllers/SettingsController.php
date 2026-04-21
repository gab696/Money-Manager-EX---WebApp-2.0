<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Csrf;
use App\Db;
use App\Models\Account;
use App\Models\Category;
use App\Models\Invitation;
use App\Models\Parameter;
use App\Models\Payee;
use App\Models\Transaction;
use App\Models\User;
use App\View;

final class SettingsController
{
    public function index(): void
    {
        Auth::requireLogin();
        $isAdmin = Auth::isAdmin();
        echo View::render('settings', [
            'username'     => Auth::username(),
            'userId'       => Auth::id(),
            'isAdmin'      => $isAdmin,
            'users'        => $isAdmin ? User::all() : [],
            'invitations'  => $isAdmin ? Invitation::recent() : [],
            'params'       => Parameter::all(),
            'accountCount' => count(Account::all()),
            'payeeCount'   => count(Payee::all()),
            'categoryCount'=> count(Category::all()),
            'pendingCount' => Transaction::totals()['count'],
            'dbPath'       => Db::dbPath(),
        ]);
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();

        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        if (strlen($new) < 4) {
            header('Location: ' . Config::url('/settings?pwd=short'));
            return;
        }
        if (!Auth::attempt((string) Auth::username(), $current)) {
            header('Location: ' . Config::url('/settings?pwd=wrong'));
            return;
        }
        User::setPassword((int) Auth::id(), $new);
        header('Location: ' . Config::url('/settings?pwd=ok'));
    }

    // ========== Utilisateurs (admin uniquement) ==========

    public function deleteUser(array $params): void
    {
        Auth::requireAdmin();
        Csrf::assertPost();
        $id = (int) $params['id'];

        if ($id === Auth::id()) {
            header('Location: ' . Config::url('/settings?user=self'));
            return;
        }
        if (User::activeCount() <= 1) {
            header('Location: ' . Config::url('/settings?user=last'));
            return;
        }
        $target = User::find($id);
        if ($target && (int) $target['is_admin'] === 1 && User::adminCount() <= 1) {
            header('Location: ' . Config::url('/settings?user=lastadmin'));
            return;
        }
        User::delete($id);
        header('Location: ' . Config::url('/settings?user=deleted'));
    }

    public function resetUserPassword(array $params): void
    {
        Auth::requireAdmin();
        Csrf::assertPost();
        $id = (int) $params['id'];
        $new = (string) ($_POST['new'] ?? '');
        if (strlen($new) < 4) {
            header('Location: ' . Config::url('/settings?user=short'));
            return;
        }
        if (!User::find($id)) {
            header('Location: ' . Config::url('/settings?user=notfound'));
            return;
        }
        User::setPassword($id, $new);
        header('Location: ' . Config::url('/settings?user=pwdreset'));
    }

    public function regenerateGuid(): void
    {
        Auth::requireAdmin();
        Csrf::assertPost();
        Parameter::set('DesktopGuid', Parameter::generateGuid());
        header('Location: ' . Config::url('/settings?guid=ok'));
    }

    public function updatePreferences(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();

        $locales = array_keys(\App\I18n::LOCALES);
        $lang    = in_array($_POST['Language'] ?? '', $locales, true) ? $_POST['Language'] : 'fr';
        Parameter::set('Language', $lang);

        // Les autres préférences sont globales : réservées à l'admin.
        if (Auth::isAdmin()) {
            $statuses = ['N', 'R', 'F', 'D', 'V'];
            $status   = in_array($_POST['DefaultStatus'] ?? '', $statuses, true) ? $_POST['DefaultStatus'] : 'N';
            Parameter::set('DefaultStatus', $status);

            Parameter::set('DisablePayee',    isset($_POST['DisablePayee'])    ? 'True' : 'False');
            Parameter::set('DisableCategory', isset($_POST['DisableCategory']) ? 'True' : 'False');

            Parameter::set('CustomFieldsEnabled', isset($_POST['CustomFieldsEnabled']) ? 'True' : 'False');
            Parameter::set('CustomFieldsLabel',   trim((string) ($_POST['CustomFieldsLabel'] ?? 'Par')) ?: 'Par');
            Parameter::set('CustomFieldsName',    trim((string) ($_POST['CustomFieldsName']  ?? '')));
        }

        header('Location: ' . Config::url('/settings?prefs=ok'));
    }

    // ========== Invitations (admin uniquement) ==========

    public function createInvitation(): void
    {
        Auth::requireAdmin();
        Csrf::assertPost();
        Invitation::create((int) Auth::id());
        header('Location: ' . Config::url('/settings?invite=created#invitations'));
    }

    public function revokeInvitation(array $params): void
    {
        Auth::requireAdmin();
        Csrf::assertPost();
        Invitation::revoke((int) $params['id']);
        header('Location: ' . Config::url('/settings?invite=revoked#invitations'));
    }
}
