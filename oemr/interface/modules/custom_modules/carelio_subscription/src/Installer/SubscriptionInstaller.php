<?php

namespace OpenEMR\Modules\CarelioSubscription\Installer;

use PDO;
use RuntimeException;

class SubscriptionInstaller
{
    public const MODULE_DIR = 'carelio_subscription';
    public const MODULE_NAME = 'Carelio Subscription';

    public static function install(?PDO $pdo = null): array
    {
        $pdoInstance = $pdo ?? self::resolvePdo();
        self::ensureSchema($pdoInstance);
        self::executeTableSql($pdoInstance);
        self::ensureSchema($pdoInstance);
        self::registerModule($pdoInstance, true);

        return [
            'status' => 'success',
            'message' => 'Carelio Subscription module installed successfully.',
        ];
    }

    public static function disable(?PDO $pdo = null): array
    {
        $pdoInstance = $pdo ?? self::resolvePdo();
        self::setConfig($pdoInstance, 'disabled_at', date('Y-m-d H:i:s'));

        return [
            'status' => 'success',
            'message' => 'Carelio Subscription module disabled successfully.',
        ];
    }

    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_config` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `config_key` VARCHAR(120) NOT NULL,
            `config_value` TEXT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_carelio_subscription_config_key` (`config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_carelio_subscriptions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `external_subscription_id` VARCHAR(191) NULL,
            `site_id` VARCHAR(191) NULL,
            `site_name` VARCHAR(191) NULL,
            `tenant_slug` VARCHAR(191) NULL,
            `customer_name` VARCHAR(191) NULL,
            `customer_email` VARCHAR(191) NULL,
            `plan_name` VARCHAR(120) NULL,
            `plan_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
            `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',
            `status` VARCHAR(40) NOT NULL DEFAULT 'ACTIVE',
            `current_period_start` DATETIME NULL,
            `current_period_end` DATETIME NULL,
            `expiration_reminder_sent_at` DATETIME NULL,
            `grace_started_at` DATETIME NULL,
            `grace_ends_at` DATETIME NULL,
            `deactivated_at` DATETIME NULL,
            `renewed_at` DATETIME NULL,
            `reactivated_at` DATETIME NULL,
            `last_synced_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_carelio_subscription_external` (`external_subscription_id`),
            KEY `idx_carelio_subscription_site` (`site_id`),
            KEY `idx_carelio_subscription_tenant` (`tenant_slug`),
            KEY `idx_carelio_subscription_status` (`status`),
            KEY `idx_carelio_subscription_period_end` (`current_period_end`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'site_id', "`site_id` VARCHAR(191) NULL AFTER `external_subscription_id`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'site_name', "`site_name` VARCHAR(191) NULL AFTER `site_id`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'plan_amount', "`plan_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `plan_name`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'currency', "`currency` VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER `plan_amount`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'billing_cycle', "`billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER `currency`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'expiration_reminder_sent_at', "`expiration_reminder_sent_at` DATETIME NULL AFTER `current_period_end`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'grace_started_at', "`grace_started_at` DATETIME NULL AFTER `expiration_reminder_sent_at`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'grace_ends_at', "`grace_ends_at` DATETIME NULL AFTER `grace_started_at`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'deactivated_at', "`deactivated_at` DATETIME NULL AFTER `grace_ends_at`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'renewed_at', "`renewed_at` DATETIME NULL AFTER `deactivated_at`");
        self::ensureColumn($pdo, 'mod_carelio_subscriptions', 'reactivated_at', "`reactivated_at` DATETIME NULL AFTER `renewed_at`");
        $pdo->exec("UPDATE `mod_carelio_subscriptions` SET `status` = UPPER(`status`) WHERE `status` <> UPPER(`status`)");
        self::ensureIndex($pdo, 'mod_carelio_subscriptions', 'idx_carelio_subscription_site', "`site_id`");
        self::ensureIndex($pdo, 'mod_carelio_subscriptions', 'idx_carelio_subscription_period_end', "`current_period_end`");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_events` (
            `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
            `subscription_id` INT NULL,
            `event_type` VARCHAR(120) NOT NULL,
            `event_payload` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_carelio_subscription_events_subscription` (`subscription_id`),
            KEY `idx_carelio_subscription_events_type` (`event_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_history` (
            `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
            `subscription_id` INT NULL,
            `action` VARCHAR(120) NOT NULL,
            `old_status` VARCHAR(40) NULL,
            `new_status` VARCHAR(40) NULL,
            `old_period_end` DATETIME NULL,
            `new_period_end` DATETIME NULL,
            `performed_by` VARCHAR(191) NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_carelio_subscription_history_subscription` (`subscription_id`),
            KEY `idx_carelio_subscription_history_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::setConfig($pdo, 'module_version', '1.0.0');
        self::setConfig($pdo, 'expiration_reminder_days', '2');
        self::setConfig($pdo, 'grace_period_days', '2');
        self::setConfig($pdo, 'default_status', 'ACTIVE');
        self::setConfig($pdo, 'schema_checked_at', date('Y-m-d H:i:s'));
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);

        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
        }
    }

    private static function ensureIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?");
        $stmt->execute([$table, $index]);

        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    }

    private static function executeTableSql(PDO $pdo): void
    {
        $sqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'table.sql';
        if (!file_exists($sqlPath)) {
            $sqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'table.sql';
        }
        if (!file_exists($sqlPath)) {
            return;
        }

        $sql = file_get_contents($sqlPath);
        if (!is_string($sql) || trim($sql) === '') {
            return;
        }

        foreach (self::splitSql($sql) as $statement) {
            try {
                $result = $pdo->query($statement);
                if ($result instanceof \PDOStatement) {
                    $result->closeCursor();
                }
            } catch (\Throwable $e) {
                error_log('Carelio Subscription table.sql warning: ' . $e->getMessage());
            }
        }
    }

    private static function splitSql(string $sql): array
    {
        $tokens = preg_split('/(\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|--[^\r\n]*|;)/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $statements = [];
        $buffer = '';

        foreach ($tokens as $token) {
            if ($token === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $token;
        }

        $statement = trim($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private static function registerModule(PDO $pdo, bool $active): void
    {
        $stmt = $pdo->prepare("INSERT INTO `modules` (
            `mod_name`, `mod_directory`, `mod_parent`, `mod_type`, `mod_active`,
            `mod_ui_name`, `mod_relative_link`, `mod_ui_order`, `mod_ui_active`,
            `mod_description`, `mod_nick_name`, `mod_enc_menu`, `permissions_item_table`,
            `directory`, `date`, `sql_run`, `type`, `sql_version`, `acl_version`
        )
        SELECT
            ?, ?, '', '', ?,
            ?, ?, 0, 0,
            ?, ?, '', '',
            '', NOW(), 1, 0, '1.0.0', ''
        WHERE NOT EXISTS (SELECT 1 FROM `modules` WHERE `mod_directory` = ?)");

        $stmt->execute([
            self::MODULE_NAME,
            self::MODULE_DIR,
            $active ? 1 : 0,
            self::MODULE_NAME,
            'interface/modules/custom_modules/' . self::MODULE_DIR . '/',
            'CarelioEMR subscription management module',
            'CarelioSubscription',
            self::MODULE_DIR,
        ]);

        $update = $pdo->prepare("UPDATE `modules`
            SET `mod_name` = ?,
                `mod_ui_name` = ?,
                `mod_relative_link` = ?,
                `mod_description` = ?,
                `mod_nick_name` = ?,
                `sql_run` = 1,
                `type` = 0,
                `sql_version` = '1.0.0'
            WHERE `mod_directory` = ?");
        $update->execute([
            self::MODULE_NAME,
            self::MODULE_NAME,
            'interface/modules/custom_modules/' . self::MODULE_DIR . '/',
            'CarelioEMR subscription management module',
            'CarelioSubscription',
            self::MODULE_DIR,
        ]);
    }

    private static function setConfig(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare("INSERT INTO `mod_carelio_subscription_config` (`config_key`, `config_value`)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`)");
        $stmt->execute([$key, $value]);
    }

    private static function resolvePdo(): PDO
    {
        $host = $GLOBALS['host'] ?? 'localhost';
        $port = $GLOBALS['port'] ?? '3306';
        $dbase = $GLOBALS['dbase'] ?? null;
        $login = $GLOBALS['login'] ?? null;
        $pass = $GLOBALS['pass'] ?? '';

        if (empty($dbase) || empty($login)) {
            throw new RuntimeException('OpenEMR database connection globals are not available.');
        }

        return new PDO("mysql:host={$host};port={$port};dbname={$dbase};charset=utf8mb4", $login, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
