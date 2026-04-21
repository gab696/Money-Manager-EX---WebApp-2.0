<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * SQLite PDO wrapper.
 *
 * Le schéma reproduit EXACTEMENT celui du webapp MMEX original
 * (`MMEX_New_Transaction.db`) pour rester compatible avec le desktop
 * sans modification côté C++.
 */
final class Db
{
    private static ?PDO $pdo = null;
    public const APP_VERSION = '2.1.3';
    public const API_VERSION = '1.0.1'; // version attendue par le desktop MMEX

    /**
     * URL PayPal du projet — soutient le développement du fork UI 2.0 par Gabriele Fusco.
     *
     * IMPORTANT pour les forks et self-hosters : par convention open-source, la destination
     * du don reste celle de l'auteur amont. Merci de ne pas modifier cette constante dans
     * une redistribution publique ou un hébergement servant des tiers. Un utilisateur final
     * qui souhaite simplement ne pas voir le bouton peut passer par "Masquer ce bouton"
     * (s'il est disponible) ou ignorer la CTA — elle est non bloquante.
     */
    public const PAYPAL_URL = 'https://www.paypal.com/donate/?hosted_button_id=WUVWMM6WRPX42';

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $path = self::dbPath();
        $dir  = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossible de créer le dossier DB : $dir");
        }

        self::$pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Comme l'original : force le retour string pour que le desktop MMEX
        // reçoive Amount en "42.10" et non 42.1 dans le JSON.
        self::$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA journal_mode = WAL');

        self::createSchema();
        return self::$pdo;
    }

    public static function dbPath(): string
    {
        $configured = Config::get('db.path');
        if ($configured) {
            return $configured;
        }
        // Emplacement par défaut : à la racine du projet (comme l'original)
        return dirname(__DIR__) . '/MMEX_New_Transaction.db';
    }

    private static function createSchema(): void
    {
        $pdo = self::$pdo;
        $pdo->exec("CREATE TABLE IF NOT EXISTS [New_Transaction](
            ID          INTEGER     PRIMARY KEY  AUTOINCREMENT,
            Date        DATE        NOT NULL,
            Account     TEXT        NOT NULL,
            ToAccount   TEXT,
            Status      TEXT        NOT NULL,
            Type        TEXT        NOT NULL,
            Payee       TEXT,
            Category    TEXT,
            SubCategory TEXT,
            Amount      NUMERIC     NOT NULL,
            Notes       TEXT
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Account_List] (
            AccountName TEXT PRIMARY KEY NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Payee_List] (
            PayeeName   TEXT PRIMARY KEY NOT NULL,
            DefCateg    TEXT,
            DefSubCateg TEXT
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Category_List] (
            CategoryName    TEXT NOT NULL,
            SubCategoryName TEXT,
            CONSTRAINT 'Category_Key' PRIMARY KEY
                (CategoryName COLLATE NOCASE, SubCategoryName COLLATE NOCASE)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Parameters] (
            Parameter TEXT PRIMARY KEY NOT NULL,
            Value     TEXT
        )");
        // Version en place pour le desktop
        $pdo->exec("INSERT OR IGNORE INTO Parameters VALUES ('Version', '" . self::APP_VERSION . "')");

        // Table Users : extension MMEX Web pour le multi-utilisateur.
        // Invisible pour le desktop (qui ne lit que les 5 tables standard).
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Users] (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            active        INTEGER NOT NULL DEFAULT 1,
            is_admin      INTEGER NOT NULL DEFAULT 0
        )");

        // Invitations : un admin génère un token que l'invité utilise pour créer son compte.
        $pdo->exec("CREATE TABLE IF NOT EXISTS [Invitations] (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            token       TEXT UNIQUE NOT NULL,
            created_by  INTEGER NOT NULL,
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at  TEXT NOT NULL,
            used_at     TEXT NULL,
            used_by     INTEGER NULL
        )");

        // Migrations Users : colonnes ajoutées au fil des versions
        $cols = array_column($pdo->query("PRAGMA table_info(Users)")->fetchAll(), 'name');
        if (!in_array('is_admin', $cols, true)) {
            $pdo->exec("ALTER TABLE Users ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0");
        }
        if (!in_array('donor_hidden', $cols, true)) {
            $pdo->exec("ALTER TABLE Users ADD COLUMN donor_hidden INTEGER NOT NULL DEFAULT 0");
        }

        // Migration : si la table Users est vide et qu'un user existe en Parameters
        // (héritage ancien webapp), on l'y reporte avec is_admin = 1.
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM Users')->fetchColumn();
        if ($userCount === 0) {
            $row = $pdo->query("SELECT (SELECT Value FROM Parameters WHERE Parameter='Username') AS u, (SELECT Value FROM Parameters WHERE Parameter='Password') AS p")->fetch();
            if ($row && !empty($row['u']) && !empty($row['p'])) {
                $stmt = $pdo->prepare('INSERT INTO Users (username, password_hash, is_admin) VALUES (?, ?, 1)');
                $stmt->execute([$row['u'], $row['p']]);
            }
        }

        // Garantit qu'au moins un admin existe : promeut le plus ancien utilisateur.
        $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM Users WHERE is_admin = 1')->fetchColumn();
        if ($adminCount === 0) {
            $pdo->exec("UPDATE Users SET is_admin = 1 WHERE id = (SELECT MIN(id) FROM Users WHERE active = 1)");
        }

        // Purge les invitations expirées ou utilisées depuis plus de 30 jours.
        $pdo->exec("DELETE FROM Invitations WHERE (used_at IS NOT NULL AND used_at < datetime('now','-30 days'))
                                               OR (used_at IS NULL    AND expires_at < datetime('now','-30 days'))");
    }

    // ---------- Helpers requête ----------

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function column(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public static function value(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }
}
