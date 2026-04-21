<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Config;
use App\Csrf;
use App\Models\Account;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\Payee;
use App\Models\Transaction;
use App\View;

final class TransactionController
{
    public function showNew(): void
    {
        Auth::requireLogin();
        echo View::render('new', self::viewData(null));
    }

    public function showEdit(array $params): void
    {
        Auth::requireLogin();
        $tx = Transaction::find((int) $params['id']);
        if (!$tx) {
            header('Location: ' . Config::url('/queue'));
            return;
        }
        echo View::render('new', self::viewData($tx));
    }

    public function save(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();

        // Normalisation vers le format attendu par Transaction::create
        $uiType = $_POST['ui_type'] ?? 'Withdrawal'; // valeur envoyée par la nouvelle UI
        $type   = in_array($uiType, ['Withdrawal','Deposit','Transfer'], true) ? $uiType : 'Withdrawal';

        $data = [
            'Date'        => $_POST['date']        ?? date('Y-m-d'),
            'Status'      => $_POST['status']      ?? Parameter::get('DefaultStatus', 'N'),
            'Type'        => $type,
            'Account'     => trim((string) ($_POST['account']      ?? '')),
            'ToAccount'   => trim((string) ($_POST['to_account']   ?? '')),
            'Payee'       => trim((string) ($_POST['payee']        ?? '')),
            'Category'    => trim((string) ($_POST['category']     ?? '')),
            'SubCategory' => trim((string) ($_POST['subcategory']  ?? '')),
            'Amount'      => $_POST['amount'] ?? '0',
            'Notes'       => trim((string) ($_POST['notes'] ?? '')),
        ];

        // Création inline d'un compte ou payee (envoyés avec suffixe _new)
        $accountNew = trim((string) ($_POST['account_new'] ?? ''));
        if ($accountNew !== '' && $data['Account'] === '') {
            Account::insertOrIgnore($accountNew);
            $data['Account'] = $accountNew;
        }
        $payeeNew = trim((string) ($_POST['payee_new'] ?? ''));
        if ($payeeNew !== '' && $data['Payee'] === '') {
            Payee::create($payeeNew);
            $data['Payee'] = $payeeNew;
        }

        // Préfixe [Par: X] dans les Notes si la fonction "champs personnalisés"
        // est active. Le desktop voit simplement du texte dans le champ Notes
        // standard (le protocole de sync ne transporte pas les custom fields).
        if (Parameter::get('CustomFieldsEnabled') === 'True') {
            $label = trim((string) Parameter::get('CustomFieldsLabel', 'Par')) ?: 'Par';
            $name  = trim((string) Parameter::get('CustomFieldsName',  '')) ?: (string) Auth::username();
            if ($name !== '') {
                $prefix = '[' . $label . ': ' . $name . '] ';
                if (!str_starts_with($data['Notes'], '[' . $label . ':')) {
                    $data['Notes'] = $prefix . $data['Notes'];
                }
            }
        }

        if (!self::validate($data)) {
            if (self::wantsJson()) {
                echo View::json(['ok' => false, 'error' => 'validation'], 422);
                return;
            }
            header('Location: ' . Config::url('/new?error=validation'));
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            Transaction::update($id, $data);
        } else {
            $id = Transaction::create($data);
        }

        if (self::wantsJson()) {
            echo View::json(['ok' => true, 'id' => $id]);
            return;
        }
        header('Location: ' . Config::url('/new?saved=1'));
    }

    public function delete(array $params): void
    {
        Auth::requireLogin();
        Csrf::assertPost();
        Transaction::delete((int) $params['id']);
        if (self::wantsJson()) {
            echo View::json(['ok' => true]);
            return;
        }
        header('Location: ' . Config::url('/queue'));
    }

    private static function viewData(?array $tx): array
    {
        // Ensure default account est pris si pas encore
        $defaultAccount = Parameter::get('DefaultAccount', '');
        $accounts = Account::all();
        if ($defaultAccount === '' && $accounts) {
            $defaultAccount = $accounts[0];
            Parameter::set('DefaultAccount', $defaultAccount);
        }

        $paypalUrl   = \App\Db::PAYPAL_URL;
        $donorHidden = \App\Models\User::isDonorHidden((int) \App\Auth::id());

        return [
            'accounts'    => $accounts,
            'categories'  => Category::leaves(),
            'payees'      => array_map(fn($p) => [
                'PayeeName' => $p['PayeeName'], 'DefCateg' => $p['DefCateg'], 'DefSubCateg' => $p['DefSubCateg'],
            ], Payee::all()),
            'frequentAccounts'   => Account::frequent(),
            'frequentPayees'     => Payee::frequent(),
            'frequentCategories' => Category::frequent(),
            'defaultAccount'     => $defaultAccount,
            'defaultStatus'      => Parameter::get('DefaultStatus', 'F'),
            'disablePayee'       => Parameter::get('DisablePayee')    === 'True',
            'disableCategory'    => Parameter::get('DisableCategory') === 'True',
            'pendingCount'       => Transaction::totals()['count'],
            'edit'               => $tx,
            'jsStrings'          => self::jsStrings(),
            'jsLocale'           => self::jsLocale(),
            'paypalUrl'          => $paypalUrl,
            'donorHidden'        => $donorHidden,
        ];
    }

    /** Subset des chaînes i18n consommées par assets/app.js (toasts, labels dynamiques). */
    private static function jsStrings(): array
    {
        $keys = [
            'withdrawal','deposit','transfer','today','yesterday',
            'amount_required','account_required','toaccount_required',
            'queued','updated','save','saving','update','new','edit','next','prev','cancel',
            'payee_created','account_created','category_created',
            'save_error','network_down','error_prefix','error_unknown',
            'nothing_to_reuse','last_tx_reused',
        ];
        $out = [];
        foreach ($keys as $k) $out[$k] = \App\I18n::t('tx.' . $k);
        return $out;
    }

    /** Locale BCP-47 pour Intl.DateTimeFormat / NumberFormat côté navigateur. */
    private static function jsLocale(): string
    {
        return \App\I18n::locale() === 'fr' ? 'fr-CH' : 'en-GB';
    }

    private static function validate(array $d): bool
    {
        if ((float) $d['Amount'] <= 0) return false;
        if ($d['Account'] === '') return false;
        if ($d['Type'] === 'Transfer' && $d['ToAccount'] === '') return false;
        return true;
    }

    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
