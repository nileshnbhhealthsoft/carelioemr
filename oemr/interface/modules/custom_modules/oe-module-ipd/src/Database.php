<?php

namespace OpenEMR\Modules\Ipd;

use PDO;
use Exception;
use RuntimeException;

/**
 * Dedicated Database Access Layer for oe-module-ipd
 *
 * Provides a resilient multi-runtime connection (Web UI, Module Manager, CLI)
 * with prepared statements, transactions, and automatic schema self-healing.
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * Get or initialize the PDO connection
     *
     * @return PDO
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // 1. Check OpenEMR global dbh
        if (isset($GLOBALS['dbh']) && $GLOBALS['dbh'] instanceof PDO) {
            self::$pdo = $GLOBALS['dbh'];
            self::ensureSchema(self::$pdo);
            return self::$pdo;
        }

        // 2. Check OpenEMR ADOdb connection handle
        if (isset($GLOBALS['adodb']['db']) && is_object($GLOBALS['adodb']['db'])) {
            $conn = $GLOBALS['adodb']['db']->_connectionID ?? null;
            if ($conn instanceof PDO) {
                self::$pdo = $conn;
                self::ensureSchema(self::$pdo);
                return self::$pdo;
            }
        }

        // 3. Fallback: Parse sqlconf.php
        $site = $_SESSION['site_id'] ?? $GLOBALS['OE_SITE_DIR'] ?? 'default';
        $sitePath = dirname(__DIR__, 5) . "/sites/" . (is_string($site) ? basename($site) : 'default');
        $sqlconfFile = $sitePath . "/sqlconf.php";

        if (!file_exists($sqlconfFile)) {
            $sqlconfFile = dirname(__DIR__, 5) . "/sites/default/sqlconf.php";
        }

        if (file_exists($sqlconfFile)) {
            $host = 'localhost';
            $port = '3306';
            $login = 'root';
            $pass = '';
            $dbase = 'openemr';
            include $sqlconfFile;

            // In sqlconf.php $sqlconf array may also be populated
            $dbHost = $sqlconf['host'] ?? $host ?? '127.0.0.1';
            $dbPort = $sqlconf['port'] ?? $port ?? '3306';
            $dbUser = $sqlconf['login'] ?? $login ?? 'root';
            $dbPass = $sqlconf['pass'] ?? $pass ?? '';
            $dbName = $sqlconf['dbase'] ?? $dbase ?? 'openemr';

            // Candidates to try: primary configured db, then openemr_new if openemr has no tables
            $dbCandidates = array_unique([$dbName, 'openemr_new', 'openemr']);

            foreach ($dbCandidates as $candidateDb) {
                try {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$candidateDb};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    self::$pdo = $pdo;
                    self::ensureSchema(self::$pdo);
                    return self::$pdo;
                } catch (\Throwable $e) {
                    // Try with root and blank password for local XAMPP environments
                    try {
                        $dsnFallback = "mysql:host={$dbHost};port={$dbPort};dbname={$candidateDb};charset=utf8mb4";
                        $pdo = new PDO($dsnFallback, 'root', '', [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                        self::$pdo = $pdo;
                        self::ensureSchema(self::$pdo);
                        return self::$pdo;
                    } catch (\Throwable $ex2) {
                        continue;
                    }
                }
            }
        }

        throw new RuntimeException("Could not resolve OpenEMR database connection for IPD module.");
    }

    /**
     * Execute a SELECT query and fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Execute a SELECT query and fetch a single row
     */
    public static function fetchRow(string $sql, array $params = []): ?array
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Execute a SELECT query and fetch a single column value
     */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute an INSERT query and return last inserted ID
     */
    public static function insert(string $sql, array $params = []): int
    {
        $stmt = self::getPdo()->prepare($sql);
        $stmt->execute($params);
        return (int)self::getPdo()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): void
    {
        self::getPdo()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): void
    {
        if (self::getPdo()->inTransaction()) {
            self::getPdo()->commit();
        }
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): void
    {
        if (self::getPdo()->inTransaction()) {
            self::getPdo()->rollBack();
        }
    }

    /**
     * Ensures all IPD tables and seed records are present in the current database.
     * Idempotent: safe to run repeatedly.
     */
    public static function ensureSchema(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        try {
            $tableExists = $pdo->query("SHOW TABLES LIKE 'ipd_admissions'")->fetchColumn();
            if (!$tableExists) {
                $sqlPath = dirname(__DIR__) . '/table.sql';
                if (file_exists($sqlPath)) {
                    $sqlContent = file_get_contents($sqlPath);
                    $queries = preg_split('/;\s*[\r\n]+/', $sqlContent);
                    foreach ($queries as $q) {
                        $q = trim($q);
                        if (!empty($q) && !str_starts_with($q, '--')) {
                            try {
                                $pdo->exec($q);
                            } catch (\Throwable $e) {
                                error_log("IPD Schema Init Warning: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            $checked = true;
        } catch (\Throwable $e) {
            error_log("IPD ensureSchema error: " . $e->getMessage());
        }
    }
}
