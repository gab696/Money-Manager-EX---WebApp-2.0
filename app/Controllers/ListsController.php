<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Models\Account;
use App\Models\Category;
use App\Models\Payee;
use App\View;

final class ListsController
{
    public function index(): void
    {
        Auth::requireLogin();
        echo View::json([
            'accounts'           => Account::all(),
            'categories'         => Category::leaves(),
            'payees'             => Payee::all(),
            'frequentAccounts'   => Account::frequent(),
            'frequentCategories' => Category::frequent(),
            'frequentPayees'     => Payee::frequent(),
        ]);
    }

    public function createPayee(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            echo View::json(['ok' => false, 'error' => 'name_required'], 422);
            return;
        }
        Payee::create($name);
        echo View::json(['ok' => true, 'name' => $name]);
    }

    public function createAccount(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            echo View::json(['ok' => false, 'error' => 'name_required'], 422);
            return;
        }
        Account::insertOrIgnore($name);
        echo View::json(['ok' => true, 'name' => $name]);
    }

    public function createCategory(): void
    {
        Auth::requireLogin();
        Csrf::assertPost();
        $cat = trim((string) ($_POST['category']    ?? ''));
        $sub = trim((string) ($_POST['subcategory'] ?? ''));
        if ($cat === '') {
            echo View::json(['ok' => false, 'error' => 'category_required'], 422);
            return;
        }
        // L'original fait toujours un INSERT de la ligne parent (SubCategoryName = 'None')
        // pour que la catégorie seule apparaisse dans les listes parent.
        Category::insertOrIgnore($cat, 'None');
        if ($sub !== '' && $sub !== 'None') {
            Category::insertOrIgnore($cat, $sub);
        }
        echo View::json([
            'ok' => true,
            'CategoryName'    => $cat,
            'SubCategoryName' => $sub !== '' ? $sub : null,
        ]);
    }
}
