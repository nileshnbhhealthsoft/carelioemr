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
    public const MODULE_DIR = 'test';
    public const MODULE_NAME = 'Site Administrator';

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
    public static function install(?PDO $pdo = null, ?string $siteDir = null): array
    {
        $pdoInstance = $pdo ?? self::resolvePdo();
        self::ensureCliEnvironment($pdoInstance, $siteDir);

        // 1. Ensure module tracking table exists
        $pdoInstance->exec("
            CREATE TABLE IF NOT EXISTS `mod_site_admin_config` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `installed_at` DATETIME NOT NULL,
                `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Ensure module is registered and active in modules table
        $stmtMod = $pdoInstance->prepare("
            SELECT mod_id, mod_active FROM modules 
            WHERE mod_directory = ? LIMIT 1
        ");
        $stmtMod->execute([self::MODULE_DIR]);
        $modRow = $stmtMod->fetch(PDO::FETCH_ASSOC);

        if (!$modRow) {
            $insertMod = $pdoInstance->prepare("
                INSERT INTO modules (
                    mod_name, mod_directory, mod_parent, mod_type, mod_active, 
                    mod_ui_name, mod_relative_link, mod_ui_order, mod_ui_active, 
                    mod_description, mod_nick_name, mod_enc_menu, permissions_item_table, 
                    directory, date, sql_run, type, sql_version, acl_version
                ) VALUES (
                    ?, ?, '', '', 1,
                    ?, 'interface/modules/custom_modules/test/', 0, 0,
                    'CarelioEMR Site Administrator Module', 'SiteAdmin', '', '',
                    '', NOW(), 1, 0, '1.0.0', ''
                )
            ");
            $insertMod->execute([self::MODULE_NAME, self::MODULE_DIR, self::MODULE_NAME]);
        } else {
            $updateMod = $pdoInstance->prepare("
                UPDATE modules SET 
                    mod_active = 1,
                    type = 0,
                    sql_run = 1
                WHERE mod_directory = ?
            ");
            $updateMod->execute([self::MODULE_DIR]);
        }

        // 3. Initialize native OpenEMR GaclApi
        $gacl = new GaclApi();

        // 4. Resolve Physicians parent group dynamically
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

        // 8. Clear GACL cache
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
     * @return bool
     */
    public static function assignUser(string $username, ?string $fullName = null, ?PDO $pdo = null, ?string $siteDir = null): bool
    {
        $pdoInstance = $pdo ?? self::resolvePdo();
        self::ensureCliEnvironment($pdoInstance, $siteDir);
        if (empty($username)) {
            throw new InvalidArgumentException("Username cannot be empty.");
        }

        if (strcasecmp($username, 'admin') === 0) {
            throw new InvalidArgumentException("Safety Guard: Root administrator 'admin' cannot be assigned to Site Administrator.");
        }

        $pdoInstance = $pdo ?? self::resolvePdo();
        $gacl = new GaclApi();

        // 1. Ensure Site Administrator group is installed
        $siteAdminGroupId = (int) $gacl->get_group_id(self::GROUP_VALUE, null, 'ARO');
        if (!$siteAdminGroupId) {
            self::install($pdoInstance);
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
     * Ensure MockArraySessionStorage in CLI mode so OpenEMR database connection factory doesn't fail,
     * and auto-detect/set OE_SITE_DIR if not already initialized.
     */
    public static function ensureCliEnvironment(?PDO $pdo = null, ?string $siteDir = null): void
    {
        if (class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
            \OpenEMR\Core\OEGlobalsBag::getInstance()->set('connection_pooling_off', true);

            if (!empty($siteDir)) {
                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $siteDir);
            } elseif (empty(\OpenEMR\Core\OEGlobalsBag::getInstance()->get('OE_SITE_DIR')) && $pdo) {
                try {
                    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
                    if ($dbName) {
                        $baseSites = dirname(__DIR__, 6) . '/sites';
                        $cleanSlug = str_replace('openemr_site_', '', $dbName);
                        $candidates = [
                            $baseSites . '/' . $dbName,
                            $baseSites . '/site-' . str_replace('_', '-', $cleanSlug),
                            $baseSites . '/site_' . $cleanSlug,
                            $baseSites . '/' . $cleanSlug,
                            $baseSites . '/default',
                        ];
                        foreach ($candidates as $cand) {
                            if (file_exists($cand . '/sqlconf.php')) {
                                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $cand);
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
     * Resolve PDO instance from OpenEMR globals or Active Connection
     *
     * @return PDO
     */
    private static function resolvePdo(): PDO
    {
        if (isset($GLOBALS['adodb']['db']) && is_object($GLOBALS['adodb']['db'])) {
            $connection = $GLOBALS['adodb']['db']->_connectionID;
            if ($connection instanceof PDO) {
                return $connection;
            }
        }

        if (isset($GLOBALS['dbh']) && $GLOBALS['dbh'] instanceof PDO) {
            return $GLOBALS['dbh'];
        }

        throw new RuntimeException("Unable to resolve active PDO database connection.");
    }
}
