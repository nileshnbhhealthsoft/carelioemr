<?php

namespace OpenEMR\Modules\SiteAdmin\Installer;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Gacl\GaclApi;
use PDO;
use RuntimeException;
use InvalidArgumentException;

class SiteAdminInstaller
{
    public const GROUP_NAME = 'Site Administrator';
    public const GROUP_VALUE = 'site_admin';
    public const PARENT_GROUP_VALUE = 'doc'; // Physicians
    public const MODULE_DIR = 'site_admin_config';
    public const MODULE_NAME = 'Site Admin Config';

    /**
     * Define full operational permissions allowed for Site Administrator
     */
    public const ALLOWED_ACOS = [
        'admin' => ['users', 'practice', 'superbill', 'calendar'],
        'acct' => ['bill', 'disc', 'eob', 'rep', 'rep_a'],
        'encounters' => ['auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed'],
        'inventory' => ['lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting'],
        'lists' => ['default', 'state', 'country', 'language', 'ethrace'],
        'patients' => ['appt', 'demo', 'med', 'trans', 'docs', 'docs_rm', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'pat_rep'],
        'sensitivities' => ['normal', 'high'],
        'nationnotes' => ['nn_configure'],
        'patientportal' => ['portal'],
        'groups' => ['gadd', 'gcalendar', 'glog', 'gdlog', 'gm'],
    ];

    /**
     * Forbidden permissions that must never be granted to Site Administrator
     */
    public const FORBIDDEN_ACOS = [
        'admin' => ['super', 'forms', 'acl', 'manage_modules', 'database', 'language', 'menu', 'batchcom', 'drugs'],
        'menus' => ['modle'],
    ];

    /**
     * Install or update the Site Administrator group, ACLs, and module registration.
     * Idempotent: can be executed multiple times safely.
     *
     * @param PDO|null $pdo Optional PDO instance for direct SQL execution
     * @return array Status information
     */
    /**
     * Install or update the Site Administrator group, ACLs, and module registration.
     * Idempotent: can be executed multiple times safely.
     *
     * @param PDO|null $pdo Optional PDO instance for direct SQL execution
     * @param string|null $siteDir Optional site directory path
     * @return array Status information
     */
    public static function install(?PDO $pdo = null, ?string $siteDir = null): array
    {
        $pdoInstance = $pdo ?? self::resolvePdo($siteDir);
        self::ensureCliEnvironment($pdoInstance, $siteDir);

        // 1. Ensure critical module schema exists independently of table.sql.
        // This keeps live installs safe even if optional seed data hits an environment-specific warning.
        self::ensureTemporaryPasswordSchema($pdoInstance);

        // 2. Ensure module schema and defaults exist via table.sql
        self::executeTableSql($pdoInstance);

        // 3. Re-check critical schema after table.sql for idempotent upgrades.
        self::ensureTemporaryPasswordSchema($pdoInstance);

        // 4. Deploy standard Carelio brand assets (logos, favicon) natively to tenant site directory
        self::deployBrandAssets($siteDir);

        // 5. Ensure Caribbean Geographic Demographics and list_options are synchronized.
        // Demographic sync is important, but it should not brick module installation on live systems.
        try {
            CaribbeanDemographicsLoader::sync($pdoInstance);
        } catch (\Throwable $e) {
            error_log('SiteAdminInstaller demographics sync warning: ' . $e->getMessage());
        }

        // 6. Initialize native OpenEMR GaclApi to sync sequences and clear cache
        $gacl = new GaclApi();

        // 5. Resolve Physicians parent group dynamically
        $parentGroupId = $gacl->get_group_id(self::PARENT_GROUP_VALUE, null, 'ARO');
        if (!$parentGroupId) {
            $parentGroupId = $pdoInstance->query("SELECT id FROM gacl_aro_groups WHERE value = 'doc' LIMIT 1")->fetchColumn();
            if (!$parentGroupId) {
                throw new RuntimeException("Could not resolve Physicians (doc) group in phpGACL.");
            }
        }
        $parentGroupId = (int) $parentGroupId;

        // 5. Ensure Site Administrator group exists
        $siteAdminGroupId = $gacl->get_group_id(self::GROUP_VALUE, null, 'ARO');
        if (!$siteAdminGroupId) {
            $siteAdminGroupId = $gacl->add_group(self::GROUP_VALUE, self::GROUP_NAME, $parentGroupId, 'ARO');
            if (!$siteAdminGroupId) {
                throw new RuntimeException("Failed to create Site Administrator group via GaclApi.");
            }
        } else {
            $siteAdminGroupId = (int) $siteAdminGroupId;
            // Ensure parent is correctly set to Physicians
            $gacl->edit_group($siteAdminGroupId, self::GROUP_VALUE, self::GROUP_NAME, $parentGroupId, 'ARO');
        }

        // 6. Define write ACL and map allowed ACOs
        $writeAclId = $pdoInstance->query("
            SELECT a.id FROM gacl_acl a 
            JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
            WHERE agm.group_id = {$siteAdminGroupId} AND a.return_value = 'write' 
            LIMIT 1
        ")->fetchColumn();

        if (!$writeAclId) {
            $success = $gacl->add_acl(
                self::ALLOWED_ACOS,
                null,
                [$siteAdminGroupId],
                null,
                null,
                1,
                1,
                'write',
                'Site Administrator full practice and clinical permissions',
                'system'
            );
            if (!$success) {
                throw new RuntimeException("Failed to add write ACL for Site Administrator via GaclApi.");
            }
            $writeAclId = (int) $pdoInstance->query("
                SELECT a.id FROM gacl_acl a 
                JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
                WHERE agm.group_id = {$siteAdminGroupId} AND a.return_value = 'write' 
                LIMIT 1
            ")->fetchColumn();
        } else {
            $writeAclId = (int) $writeAclId;
            // Verify all allowed ACOs are mapped
            $stmtInsertAco = $pdoInstance->prepare("
                INSERT IGNORE INTO gacl_aco_map (acl_id, section_value, value) VALUES (?, ?, ?)
            ");
            foreach (self::ALLOWED_ACOS as $section => $values) {
                foreach ($values as $val) {
                    $acoExists = $pdoInstance->query("
                        SELECT id FROM gacl_aco WHERE section_value = '{$section}' AND value = '{$val}' LIMIT 1
                    ")->fetchColumn();
                    if ($acoExists) {
                        $stmtInsertAco->execute([$writeAclId, $section, $val]);
                    }
                }
            }
        }

        // 7. Strictly purge any forbidden ACOs from Site Administrator write ACL
        $stmtDeleteAco = $pdoInstance->prepare("
            DELETE FROM gacl_aco_map 
            WHERE acl_id = ? AND section_value = ? AND value = ?
        ");
        foreach (self::FORBIDDEN_ACOS as $sec => $forbiddenVals) {
            foreach ($forbiddenVals as $fVal) {
                $stmtDeleteAco->execute([$writeAclId, $sec, $fVal]);
            }
        }

        // 8. Guarantee root 'admin' user has full super administration privileges
        self::ensureRootAdminPrivileges($pdoInstance, $gacl, $siteDir);

        // 9. Clear GACL cache
        if (class_exists(AclMain::class)) {
            AclMain::clearGaclCache();
        }

        return [
            'status' => 'success',
            'site_admin_group_id' => $siteAdminGroupId,
            'parent_group_id' => $parentGroupId,
            'acl_id' => $writeAclId,
            'module_active' => true,
        ];
    }

    /**
     * Assign a user to the Site Administrator role.
     * Enforces that root 'admin' can NEVER be assigned to Site Administrator.
     * Idempotently maps user into Site Administrator, Physicians, Clinicians,
     * and removes user from Super Administrators.
     *
     * @param string $username User login name
     * @param string|null $fullName User full name
     * @param PDO|null $pdo Optional PDO instance
     * @param string|null $siteDir Optional site directory path
     * @return bool
     */
    public static function assignUser(string $username, ?string $fullName = null, ?PDO $pdo = null, ?string $siteDir = null): bool
    {
        $pdoInstance = $pdo ?? self::resolvePdo($siteDir);
        self::ensureCliEnvironment($pdoInstance, $siteDir);
        if (empty($username)) {
            throw new InvalidArgumentException("Username cannot be empty.");
        }

        if (strcasecmp($username, 'admin') === 0) {
            throw new InvalidArgumentException("Safety Guard: Root administrator 'admin' cannot be assigned to Site Administrator.");
        }

        $gacl = new GaclApi();

        // 1. Ensure Site Administrator group is installed
        $siteAdminGroupId = (int) $gacl->get_group_id(self::GROUP_VALUE, null, 'ARO');
        if (!$siteAdminGroupId) {
            self::install($pdoInstance, $siteDir);
            $siteAdminGroupId = (int) $gacl->get_group_id(self::GROUP_VALUE, null, 'ARO');
        }

        // 2. Resolve target group IDs dynamically
        $physicianGroupId = (int) $gacl->get_group_id('doc', null, 'ARO');
        $clinicianGroupId = (int) $gacl->get_group_id('clin', null, 'ARO');
        $superAdminGroupId = (int) $gacl->get_group_id('admin', null, 'ARO');

        // 3. Ensure user ARO exists in phpGACL
        $userAroId = $gacl->get_object_id('users', $username, 'ARO');
        if (!$userAroId) {
            $nameToUse = $fullName ?: $username;
            $gacl->add_object('users', $nameToUse, $username, 10, 0, 'ARO');
            $userAroId = $gacl->get_object_id('users', $username, 'ARO');
            if (!$userAroId) {
                throw new RuntimeException("Failed to create ARO for user '{$username}'.");
            }
        }

        // 4. Map into Site Administrator, Physicians, Clinicians
        $gacl->add_group_object($siteAdminGroupId, 'users', $username, 'ARO');
        if ($physicianGroupId) {
            $gacl->add_group_object($physicianGroupId, 'users', $username, 'ARO');
        }
        if ($clinicianGroupId) {
            $gacl->add_group_object($clinicianGroupId, 'users', $username, 'ARO');
        }

        // 5. Ensure removed from Super Administrators group (admin)
        if ($superAdminGroupId) {
            $gacl->del_group_object($superAdminGroupId, 'users', $username, 'ARO');
        }

        // 6. Ensure main_menu_role = 'standard' for native standard menu
        $pdoInstance->prepare("
            UPDATE users SET main_menu_role = 'standard' WHERE username = ?
        ")->execute([$username]);

        // 7. Clear GACL cache
        if (class_exists(AclMain::class)) {
            AclMain::clearGaclCache();
        }

        return true;
    }

    /**
     * Guarantee root 'admin' user retains full Super Administrator privileges and is never restricted
     */
    public static function ensureRootAdminPrivileges(?PDO $pdo = null, ?GaclApi $gacl = null, ?string $siteDir = null): bool
    {
        $pdoInstance = $pdo ?? self::resolvePdo($siteDir);
        $gaclInstance = $gacl ?? new GaclApi();

        // 1. Ensure root 'admin' in users table has active = 1, authorized = 1, and unconstrained menu (empty main_menu_role)
        $pdoInstance->exec("
            UPDATE users 
            SET main_menu_role = '', active = 1, authorized = 1 
            WHERE LOWER(username) = 'admin'
        ");

        // 2. Ensure admin ARO exists in phpGACL
        $adminAroId = $gaclInstance->get_object_id('users', 'admin', 'ARO');
        if (!$adminAroId) {
            $gaclInstance->add_object('users', 'Administrator', 'admin', 10, 0, 'ARO');
        }

        // 3. Ensure mapped into Administrators group
        $adminGroupId = (int) $gaclInstance->get_group_id('admin', null, 'ARO');
        if ($adminGroupId) {
            $gaclInstance->add_group_object($adminGroupId, 'users', 'admin', 'ARO');
        }

        // 4. Ensure strictly excluded from site_admin group
        $siteAdminGroupId = (int) $gaclInstance->get_group_id(self::GROUP_VALUE, null, 'ARO');
        if ($siteAdminGroupId) {
            $gaclInstance->del_group_object($siteAdminGroupId, 'users', 'admin', 'ARO');
        }

        return true;
    }

    /**
     * Deploy standard Carelio brand assets (logos, favicon) natively to tenant site directory
     */
    public static function deployBrandAssets(?string $siteDir = null): void
    {
        $targetDir = self::resolveSiteDir($siteDir);
        if (empty($targetDir) || !is_dir($targetDir)) {
            return;
        }

        $assetsDir = dirname(__DIR__, 2) . '/assets';
        if (!is_dir($assetsDir)) {
            return;
        }

        $targets = [
            'images/logos/core/login/primary' => [
                'carelio_logo.svg' => 'logo.svg',
                'carelio_logo.png' => 'logo.png',
            ],
            'images/logos/core/favicon' => [
                'favicon.ico' => 'favicon.ico',
            ],
            'images/logos/core/menu/primary' => [
                'carelio_logo.svg' => 'logo.svg',
                'carelio_logo.png' => 'logo.png',
            ],
            'images/logos/portal/login/primary' => [
                'carelio_logo.svg' => 'logo.svg',
                'carelio_logo.png' => 'logo.png',
            ],
            'images/logos/portal/menu/primary' => [
                'carelio_logo.svg' => 'logo.svg',
                'carelio_logo.png' => 'logo.png',
            ],
            'images' => [
                'login_logo.gif' => 'login_logo.gif',
                'carelio_logo.png' => 'logo_1.png',
                'carelio_logo.png' => 'logo_2.png',
            ],
        ];

        foreach ($targets as $subpath => $fileMap) {
            $destDir = $targetDir . '/' . $subpath;
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            foreach ($fileMap as $srcFile => $destFile) {
                $src = $assetsDir . '/' . $srcFile;
                $dest = $destDir . '/' . $destFile;
                if (file_exists($src)) {
                    @copy($src, $dest);
                }
            }
        }
    }

    /**
     * Ensure MockArraySessionStorage in CLI mode so OpenEMR database connection factory doesn't fail,
     * and auto-detect/set OE_SITE_DIR if not already initialized.
     */
    public static function ensureCliEnvironment(?PDO $pdo = null, ?string $siteDir = null): void
    {
        $resolvedSiteDir = self::resolveSiteDir($siteDir);

        if (class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
            \OpenEMR\Core\OEGlobalsBag::getInstance()->set('connection_pooling_off', true);

            if (!empty($resolvedSiteDir)) {
                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $resolvedSiteDir);
                $GLOBALS['OE_SITE_DIR'] = $resolvedSiteDir;
            } elseif (empty(\OpenEMR\Core\OEGlobalsBag::getInstance()->get('OE_SITE_DIR')) && $pdo) {
                try {
                    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
                    if ($dbName) {
                        $baseSites = dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . 'sites';
                        $cleanSlug = str_replace('openemr_site_', '', (string)$dbName);
                        $candidates = [
                            $baseSites . DIRECTORY_SEPARATOR . $dbName,
                            $baseSites . DIRECTORY_SEPARATOR . 'site-' . str_replace('_', '-', $cleanSlug),
                            $baseSites . DIRECTORY_SEPARATOR . 'site_' . $cleanSlug,
                            $baseSites . DIRECTORY_SEPARATOR . $cleanSlug,
                            $baseSites . DIRECTORY_SEPARATOR . 'default',
                        ];
                        foreach ($candidates as $cand) {
                            if (file_exists($cand . '/sqlconf.php')) {
                                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $cand);
                                $GLOBALS['OE_SITE_DIR'] = $cand;
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if (php_sapi_name() === 'cli') {
            if (class_exists(\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage::class) &&
                class_exists(\OpenEMR\Common\Session\SessionWrapperFactory::class)) {
                try {
                    $session = \OpenEMR\Common\Session\SessionWrapperFactory::getInstance()->getActiveSession();
                } catch (\Throwable $e) {
                    $session = null;
                }
                if (!$session || !$session->isStarted()) {
                    \OpenEMR\Common\Session\SessionWrapperFactory::getInstance()->setActiveSession(
                        new \Symfony\Component\HttpFoundation\Session\Session(
                            new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
                        )
                    );
                }
            }
        }
    }

    /**
     * Resolve site directory path dynamically across all runtime environments
     */
    public static function resolveSiteDir(?string $siteDir = null): ?string
    {
        if (!empty($siteDir) && is_dir($siteDir) && file_exists($siteDir . '/sqlconf.php')) {
            return realpath($siteDir) ?: $siteDir;
        }

        if (class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
            $bagSiteDir = \OpenEMR\Core\OEGlobalsBag::getInstance()->get('OE_SITE_DIR');
            if (!empty($bagSiteDir) && is_dir($bagSiteDir) && file_exists($bagSiteDir . '/sqlconf.php')) {
                return realpath($bagSiteDir) ?: $bagSiteDir;
            }
        }

        if (!empty($GLOBALS['OE_SITE_DIR']) && is_dir($GLOBALS['OE_SITE_DIR']) && file_exists($GLOBALS['OE_SITE_DIR'] . '/sqlconf.php')) {
            return realpath($GLOBALS['OE_SITE_DIR']) ?: $GLOBALS['OE_SITE_DIR'];
        }

        $baseSites = dirname(__DIR__, 6) . DIRECTORY_SEPARATOR . 'sites';

        if (!empty($_SESSION['site_id'])) {
            $cleanSite = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_SESSION['site_id']);
            $sessionSite = $baseSites . DIRECTORY_SEPARATOR . $cleanSite;
            if (is_dir($sessionSite) && file_exists($sessionSite . '/sqlconf.php')) {
                return realpath($sessionSite) ?: $sessionSite;
            }
        }

        if (is_dir($baseSites . DIRECTORY_SEPARATOR . 'default') && file_exists($baseSites . DIRECTORY_SEPARATOR . 'default/sqlconf.php')) {
            return realpath($baseSites . DIRECTORY_SEPARATOR . 'default') ?: ($baseSites . DIRECTORY_SEPARATOR . 'default');
        }

        return null;
    }

    /**
     * Resolve PDO instance from OpenEMR Doctrine DBAL, globals, or site configuration.
     * Robust against all runtime contexts (Web UI, Zend Module Manager, CLI, Laravel Seeder).
     *
     * @param string|null $siteDir
     * @return PDO
     */
    public static function resolvePdo(?string $siteDir = null): PDO
    {
        // 1. Direct PDO from global variables
        if (isset($GLOBALS['dbh']) && $GLOBALS['dbh'] instanceof PDO) {
            return $GLOBALS['dbh'];
        }

        if (isset($GLOBALS['adodb']['db']) && is_object($GLOBALS['adodb']['db'])) {
            $connection = $GLOBALS['adodb']['db']->_connectionID;
            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        // 2. Resolve target site directory and ensure OE_SITE_DIR is registered
        $resolvedSiteDir = self::resolveSiteDir($siteDir);
        if (!empty($resolvedSiteDir)) {
            if (class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $resolvedSiteDir);
            }
            $GLOBALS['OE_SITE_DIR'] = $resolvedSiteDir;
        }

        // 3. Try to resolve via OpenEMR native Doctrine DBAL connection
        if (class_exists(\OpenEMR\BC\Database::class)) {
            try {
                $dbal = \OpenEMR\BC\Database::instance()->getDbalConnection();
                if (method_exists($dbal, 'getNativeConnection')) {
                    $native = $dbal->getNativeConnection();
                    if ($native instanceof PDO) {
                        return $native;
                    }
                }
            } catch (\Throwable $e) {
                // Proceed to next fallback
            }
        }

        // 4. Try via DatabaseConnectionOptions and DatabaseConnectionFactory
        if (!empty($resolvedSiteDir) && file_exists($resolvedSiteDir . '/sqlconf.php')) {
            if (class_exists(\OpenEMR\BC\DatabaseConnectionOptions::class) && class_exists(\OpenEMR\BC\DatabaseConnectionFactory::class)) {
                try {
                    $options = \OpenEMR\BC\DatabaseConnectionOptions::forSite($resolvedSiteDir);
                    $dbal = \OpenEMR\BC\DatabaseConnectionFactory::createDbal($options, false);
                    if (method_exists($dbal, 'getNativeConnection')) {
                        $native = $dbal->getNativeConnection();
                        if ($native instanceof PDO) {
                            return $native;
                        }
                    }
                } catch (\Throwable $e) {
                    // Proceed to next fallback
                }
            }
        }

        // 5. Direct PDO creation from sqlconf.php or globals
        $dbHost = $GLOBALS['host'] ?? null;
        $dbPort = $GLOBALS['port'] ?? null;
        $dbUser = $GLOBALS['login'] ?? null;
        $dbPass = $GLOBALS['pass'] ?? null;
        $dbName = $GLOBALS['dbase'] ?? null;
        $dbSocket = $GLOBALS['socket'] ?? null;

        if ((empty($dbUser) || empty($dbName)) && !empty($resolvedSiteDir) && file_exists($resolvedSiteDir . '/sqlconf.php')) {
            $conf = self::loadSqlconfVars($resolvedSiteDir . '/sqlconf.php');
            $dbName = $conf['dbase'] ?? $dbName;
            $dbUser = $conf['login'] ?? $dbUser;
            $dbPass = $conf['pass'] ?? $dbPass;
            $dbHost = $conf['host'] ?? $dbHost;
            $dbPort = $conf['port'] ?? $dbPort;
            $dbSocket = $conf['socket'] ?? $dbSocket;
        }

        if (!empty($dbUser) && !empty($dbName)) {
            $dsn = "mysql:dbname={$dbName};charset=utf8mb4";
            if (!empty($dbHost)) {
                $dsn .= ";host={$dbHost}";
                if (!empty($dbPort)) {
                    $dsn .= ";port={$dbPort}";
                }
            } elseif (!empty($dbSocket)) {
                $dsn .= ";unix_socket={$dbSocket}";
            } else {
                $dsn .= ";host=localhost;port=3306";
            }

            try {
                $pdo = new PDO($dsn, $dbUser, $dbPass ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                try {
                    $pdo->exec("SET NAMES 'utf8mb4', sql_mode = ''");
                } catch (\Throwable $e) {
                }
                return $pdo;
            } catch (\Throwable $e) {
                throw new RuntimeException("Unable to connect to database '{$dbName}' on '{$dbHost}': " . $e->getMessage(), 0, $e);
            }
        }

        throw new RuntimeException("Unable to resolve active PDO database connection: missing site credentials or configuration.");
    }

    /**
     * Load variables from sqlconf.php cleanly in an isolated scope
     */
    private static function loadSqlconfVars(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $sqlconf = null;
        $host = null;
        $port = null;
        $login = null;
        $pass = null;
        $dbase = null;
        $socket = null;

        require $filePath;

        if (isset($sqlconf) && is_array($sqlconf)) {
            return [
                'dbase' => $sqlconf['dbase'] ?? ($dbase ?? null),
                'login' => $sqlconf['login'] ?? ($login ?? null),
                'pass' => $sqlconf['pass'] ?? ($pass ?? null),
                'host' => $sqlconf['host'] ?? ($host ?? null),
                'port' => $sqlconf['port'] ?? ($port ?? 3306),
                'socket' => $sqlconf['socket'] ?? ($socket ?? null),
            ];
        }

        return [
            'dbase' => $dbase ?? null,
            'login' => $login ?? null,
            'pass' => $pass ?? null,
            'host' => $host ?? null,
            'port' => $port ?? 3306,
            'socket' => $socket ?? null,
        ];
    }

    /**
     * Execute custom module's table.sql against the database
     */
    public static function executeTableSql(PDO $pdo): void
    {
        $sqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'table.sql';
        if (!file_exists($sqlPath)) {
            $sqlPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'table.sql';
        }
        if (!file_exists($sqlPath)) {
            return;
        }

        $sql = file_get_contents($sqlPath);
        if (empty(trim($sql))) {
            return;
        }

        $embeddedDatasetMarker = '-- CARELIO_EMBEDDED_CARIBBEAN_DEMOGRAPHICS_SQL_BEGIN';
        $embeddedDatasetPosition = strpos($sql, $embeddedDatasetMarker);
        if ($embeddedDatasetPosition !== false) {
            $sql = substr($sql, 0, $embeddedDatasetPosition);
        }

        try {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (\Throwable $e) {
        }

        // Robust statement splitting respecting quoted strings and comments
        $tokens = preg_split('/(\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|--[^\r\n]*|;)/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $stmtBuffer = '';
        foreach ($tokens as $token) {
            if ($token === ';') {
                $trimmed = trim($stmtBuffer);
                if (!empty($trimmed)) {
                    try {
                        $res = $pdo->query($trimmed);
                        if ($res instanceof \PDOStatement) {
                            $res->closeCursor();
                        }
                    } catch (\Throwable $e) {
                        // Ignore non-fatal statement warnings
                    }
                }
                $stmtBuffer = '';
            } else {
                $stmtBuffer .= $token;
            }
        }
        $trimmed = trim($stmtBuffer);
        if (!empty($trimmed)) {
            try {
                $res = $pdo->query($trimmed);
                if ($res instanceof \PDOStatement) {
                    $res->closeCursor();
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Critical schema used by the temporary-password first-login gate.
     * Kept as direct PDO DDL so module install/enable can self-heal on live sites.
     */
    public static function ensureTemporaryPasswordSchema(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `mod_site_admin_temp_passwords` (
            `user_id` INT NOT NULL PRIMARY KEY,
            `username` VARCHAR(255) NOT NULL,
            `is_temporary` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `cleared_at` DATETIME NULL,
            `created_by` VARCHAR(255) NULL,
            KEY `idx_temp_password_username` (`username`),
            KEY `idx_temp_password_active` (`is_temporary`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Disable module hook: deactivate layout script and restore standard labels
     *
     * @param PDO|null $pdo
     * @param string|null $siteDir
     * @return array
     */
    public static function disable(?PDO $pdo = null, ?string $siteDir = null): array
    {
        $pdoInstance = $pdo ?? self::resolvePdo($siteDir);
        self::ensureCliEnvironment($pdoInstance, $siteDir);

        // Deactivate layout cascading script and reset labels
        CaribbeanDemographicsLoader::deactivate($pdoInstance);

        return [
            'status' => 'success',
            'message' => 'Site Admin Config disabled successfully.',
        ];
    }
}
