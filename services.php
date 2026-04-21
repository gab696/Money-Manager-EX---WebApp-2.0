<?php
declare(strict_types=1);

/**
 * MMEX Web — endpoint compatible avec le client desktop Money Manager EX.
 *
 * Protocole copié du webapp original (WebApp/services.php) :
 *   - auth par paramètre GET "guid" qui doit matcher Parameters.DesktopGuid
 *   - chaque action est un paramètre GET dont la présence déclenche l'action
 *   - réponses en text/plain "Operation has succeeded" / "Wrong GUID"
 *     ou JSON pour download_transaction.
 *
 * NE PAS MODIFIER les URLs ni le format des réponses : le desktop les attend
 * tels quels.
 */

require __DIR__ . '/app/bootstrap.php';

use App\Db;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\Payee;
use App\Models\Transaction;

Parameter::ensureDefaults();

$OK  = 'Operation has succeeded';
$BAD = 'Wrong GUID';

header('Content-Type: text/plain; charset=utf-8');

$providedGuid = $_GET['guid'] ?? '';
$expectedGuid = Parameter::get('DesktopGuid', '');

if ($providedGuid === '' || $providedGuid !== $expectedGuid) {
    http_response_code(200);
    echo $BAD;
    exit;
}

// --- Test GUID ---
if (isset($_GET['check_guid'])) {
    echo $OK;
    exit;
}

// --- Version API ---
if (isset($_GET['check_api_version'])) {
    echo Db::API_VERSION;
    exit;
}

// --- BankAccount ---
if (isset($_GET['delete_bankaccount'])) {
    Account::deleteAll();
    echo $OK;
    exit;
}
if (isset($_GET['import_bankaccount'])) {
    $payload = json_decode((string) ($_POST['MMEX_Post'] ?? ''), true) ?: [];
    $list = $payload['Accounts'] ?? [];
    $pdo = Db::pdo();
    $pdo->beginTransaction();
    foreach ($list as $row) {
        $name = trim((string) ($row['AccountName'] ?? ''));
        if ($name !== '') Account::insertOrIgnore($name);
    }
    $pdo->commit();
    echo $OK;
    exit;
}

// --- Payee ---
if (isset($_GET['delete_payee'])) {
    Payee::deleteAll();
    echo $OK;
    exit;
}
if (isset($_GET['import_payee'])) {
    $payload = json_decode((string) ($_POST['MMEX_Post'] ?? ''), true) ?: [];
    $list = $payload['Payees'] ?? [];
    $pdo = Db::pdo();
    $pdo->beginTransaction();
    foreach ($list as $row) {
        $name = trim((string) ($row['PayeeName'] ?? ''));
        if ($name === '') continue;
        Payee::create(
            $name,
            $row['DefCateg']    ?? null,
            $row['DefSubCateg'] ?? null
        );
    }
    $pdo->commit();
    echo $OK;
    exit;
}

// --- Category ---
if (isset($_GET['delete_category'])) {
    Category::deleteAll();
    echo $OK;
    exit;
}
if (isset($_GET['import_category'])) {
    $payload = json_decode((string) ($_POST['MMEX_Post'] ?? ''), true) ?: [];
    $list = $payload['Categories'] ?? [];
    $pdo = Db::pdo();
    $pdo->beginTransaction();
    $seenParents = [];
    foreach ($list as $row) {
        $cat = trim((string) ($row['CategoryName'] ?? ''));
        if ($cat === '') continue;
        // Comme l'original : premier insert de la catégorie → ligne parent (SubCategory='None')
        if (!in_array($cat, $seenParents, true)) {
            Category::insertOrIgnore($cat, 'None');
            $seenParents[] = $cat;
        }
        $sub = $row['SubCategoryName'] ?? null;
        if ($sub !== null && $sub !== '' && $sub !== 'None') {
            Category::insertOrIgnore($cat, (string) $sub);
        }
    }
    $pdo->commit();
    echo $OK;
    exit;
}

// --- Download transactions (pour le desktop) ---
if (isset($_GET['download_transaction'])) {
    header('Content-Type: application/json; charset=utf-8');
    $rows = Transaction::all('ASC'); // ASC comme l'original
    if (empty($rows)) {
        echo '';
        exit;
    }
    foreach ($rows as &$r) {
        $r['Attachments'] = implode(';', Attachment::forTransaction((int) $r['ID']));
    }
    unset($r);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
    exit;
}

// --- Download attachment ---
if (isset($_GET['download_attachment'])) {
    $filename = (string) $_GET['download_attachment'];
    $path = Attachment::path($filename);
    if (!$path) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    header_remove('Content-Type');
    header('Content-Type: application/octet-stream');
    header('Cache-Control: public');
    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename=' . basename($path));
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// --- Delete attachment ---
if (isset($_GET['delete_attachment'])) {
    $filename = (string) $_GET['delete_attachment'];
    if ($filename !== '') Attachment::deleteByName($filename);
    echo $OK;
    exit;
}

// --- Delete transaction group (acquittement après aspiration par desktop) ---
if (isset($_GET['delete_group'])) {
    $ids = array_filter(array_map('trim', explode(',', (string) $_GET['delete_group'])), 'strlen');
    if ($ids) {
        $intIds = array_map('intval', $ids);
        Transaction::deleteMany($intIds);
        Attachment::deleteForTransactions($intIds);
        Parameter::set('LastSyncAt', date('c'));
    }
    echo $OK;
    exit;
}

// Aucun action déclenchée — rien à faire (silence, comme l'original)
echo '';
