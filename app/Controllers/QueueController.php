<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Parameter;
use App\Models\Transaction;
use App\View;

final class QueueController
{
    public function index(): void
    {
        Auth::requireLogin();
        echo View::render('queue', [
            'pending'    => Transaction::all('DESC'),
            'totals'     => Transaction::totals(),
            'lastSyncAt' => Parameter::get('LastSyncAt'),
        ]);
    }
}
