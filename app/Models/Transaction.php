<?php
declare(strict_types=1);

namespace App\Models;

use App\Db;

/**
 * Table New_Transaction.
 * Les champs Account/Payee/Category/SubCategory sont des TEXT (noms),
 * pas des FK — reproduit à l'identique le schéma attendu par MMEX desktop.
 *
 * Toute transaction présente ici est "en attente de sync" : le desktop la
 * récupère via services.php?download_transaction → puis la supprime via
 * services.php?delete_group=ID1,ID2… pour la faire disparaître de la queue.
 */
final class Transaction
{
    public static function all(string $direction = 'DESC'): array
    {
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        return Db::all("SELECT * FROM New_Transaction ORDER BY Date $dir, ID $dir");
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM New_Transaction WHERE ID = ?', [$id]);
    }

    public static function create(array $d): int
    {
        $d = self::normalize($d);
        Db::query(
            'INSERT INTO New_Transaction (Date, Status, Type, Account, ToAccount, Payee, Category, SubCategory, Amount, Notes)
             VALUES (:Date, :Status, :Type, :Account, :ToAccount, :Payee, :Category, :SubCategory, :Amount, :Notes)',
            $d
        );
        return (int) Db::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $d = self::normalize($d);
        $d['ID'] = $id;
        Db::query(
            'UPDATE New_Transaction SET
                Date=:Date, Status=:Status, Type=:Type, Account=:Account, ToAccount=:ToAccount,
                Payee=:Payee, Category=:Category, SubCategory=:SubCategory, Amount=:Amount, Notes=:Notes
             WHERE ID=:ID',
            $d
        );
    }

    public static function delete(int $id): void
    {
        Db::query('DELETE FROM New_Transaction WHERE ID = ?', [$id]);
    }

    public static function deleteMany(array $ids): void
    {
        if (!$ids) return;
        $clean = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
        if (!$clean) return;
        $place = rtrim(str_repeat('?,', count($clean)), ',');
        Db::query("DELETE FROM New_Transaction WHERE ID IN ($place)", $clean);
    }

    public static function totals(): array
    {
        $out = ['Withdrawal' => 0.0, 'Deposit' => 0.0, 'Transfer' => 0.0, 'count' => 0];
        $rows = Db::all('SELECT Type, COUNT(*) AS c, COALESCE(SUM(Amount), 0) AS total FROM New_Transaction GROUP BY Type');
        foreach ($rows as $r) {
            $out[$r['Type']] = (float) $r['total'];
            $out['count'] += (int) $r['c'];
        }
        return $out;
    }

    private static function normalize(array $d): array
    {
        $type = $d['Type'] ?? 'Withdrawal';
        if (!in_array($type, ['Withdrawal', 'Deposit', 'Transfer'], true)) {
            $type = 'Withdrawal';
        }
        $row = [
            'Date'        => (string) ($d['Date'] ?? date('Y-m-d')),
            'Status'      => (string) ($d['Status'] ?? 'N'),
            'Type'        => $type,
            'Account'     => (string) ($d['Account'] ?? ''),
            'ToAccount'   => $type === 'Transfer' ? (string) ($d['ToAccount'] ?? '') : 'None',
            'Payee'       => $type !== 'Transfer' ? (string) ($d['Payee'] ?? '') : '',
            'Category'    => $type !== 'Transfer' ? (string) ($d['Category'] ?? '') : 'None',
            'SubCategory' => $type !== 'Transfer' ? (string) ($d['SubCategory'] ?? '') : 'None',
            'Amount'      => number_format((float) ($d['Amount'] ?? 0), 2, '.', ''),
            'Notes'       => (string) ($d['Notes'] ?? ''),
        ];
        // Les vides ne doivent pas casser — on tolère une sous-catégorie absente
        if ($row['SubCategory'] === '') $row['SubCategory'] = 'None';
        if ($row['Payee'] === '' && $type !== 'Transfer') $row['Payee'] = null;
        return $row;
    }
}
