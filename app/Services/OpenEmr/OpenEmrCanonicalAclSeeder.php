<?php

namespace App\Services\OpenEmr;

use OpenEMR\Core\OEGlobalsBag;
use Installer;
use PDO;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

class OpenEmrCanonicalAclSeeder
{
    /**
     * Get path to OpenEMR base directory safely across CLI and web contexts
     */
    public function getOpenEmrBasePath(): string
    {
        return rtrim(
            (string) config('oemr.base_path', base_path('oemr')),
            '/\\'
        );
    }

    /**
     * Get a PDO connection to a tenant database dynamically
     */
    public function getTenantPdo(string $dbName): PDO
    {
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        return new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * Seed canonical OpenEMR 8.3.0 ACL and official accounts for a tenant database
     */
    public function seedCanonicalAcl(
        string $sitePath,
        string $tenantSlug,
        string $dbName,
        string $adminUser = 'admin',
        string $adminFullName = 'Administrator'
    ): bool {
        $openEmrBasePath = $this->getOpenEmrBasePath();
        $installerClass = $openEmrBasePath . '/library/classes/Installer.class.php';
        $additionalUsersSql = $openEmrBasePath . '/sql/official_additional_users.sql';

        if (!file_exists($installerClass)) {
            throw new RuntimeException("Canonical Installer class not found at: {$installerClass}");
        }

        require_once $openEmrBasePath . '/vendor/autoload.php';
        require_once $installerClass;

        if (class_exists(\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage::class) && class_exists(\OpenEMR\Common\Session\SessionWrapperFactory::class)) {
            \OpenEMR\Common\Session\SessionWrapperFactory::getInstance()->setActiveSession(
                new \Symfony\Component\HttpFoundation\Session\Session(
                    new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
                )
            );
        }

        // Ensure OE_SITE_DIR points to the tenant's isolated site directory
        OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $sitePath);
        OEGlobalsBag::getInstance()->set('connection_pooling_off', true);

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $params = [
            'server' => $host,
            'port' => (string) $port,
            'login' => $user,
            'pass' => $pass,
            'dbname' => $dbName,
            'site' => $tenantSlug,
            'iuser' => $adminUser,
            'iuname' => $adminFullName,
            'iufname' => 'Admin',
            'iuserpass' => 'NoLongerUsed',
            'igroup' => 'Default',
            'additional_users' => $additionalUsersSql,
        ];

        $installer = new Installer($params, new NullLogger());

        if (!$installer->user_database_connection()) {
            throw new RuntimeException("Installer failed to connect to tenant database {$dbName}: " . $installer->error_message);
        }

        // 1. Install canonical phpGACL hierarchy (13 sections, 65 ACOs, 7 ARO groups, permission rules)
        if (!$installer->install_gacl()) {
            throw new RuntimeException("Installer::install_gacl() failed for {$dbName}: " . $installer->error_message);
        }

        // 2. Install official system service users into GACL
        if (file_exists($additionalUsersSql)) {
            if (!$installer->install_additional_users()) {
                throw new RuntimeException("Installer::install_additional_users() failed for {$dbName}: " . $installer->error_message);
            }
        }

        // 3. Configure Care Coordination module ACL
        $installer->on_care_coordination();

        // 4. Ensure admin user is mapped into the canonical 'admin' ARO group dynamically
        $this->ensureAdminUserAclMapped($dbName, $adminUser, $adminFullName);

        // 5. Ensure safe 'Tenant Administrators' group exists
        $pdo = $this->getTenantPdo($dbName);
        $this->ensureTenantAdminGroup($pdo);

        return true;
    }

    /**
     * Ensure the specified admin user exists as an ARO and is linked to the canonical 'admin' group
     */
    public function ensureAdminUserAclMapped(string $dbName, string $adminUser, string $adminFullName): void
    {
        $pdo = $this->getTenantPdo($dbName);

        // 1. Resolve canonical 'admin' group ID dynamically by value or canonical name
        $adminGroupId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'admin' OR name = 'Administrators' ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$adminGroupId) {
            throw new RuntimeException("Canonical 'admin' / 'Administrators' ARO group not found in gacl_aro_groups for {$dbName}");
        }

        // 2. Ensure ARO exists for user in 'users' section
        $stmtAro = $pdo->prepare("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = ? LIMIT 1");
        $stmtAro->execute([$adminUser]);
        $aroId = $stmtAro->fetchColumn();

        if (!$aroId) {
            $nextAroId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro")->fetchColumn();
            $stmtInsertAro = $pdo->prepare("INSERT INTO gacl_aro (id, section_value, value, order_value, name, hidden) VALUES (?, 'users', ?, 10, ?, 0)");
            $stmtInsertAro->execute([$nextAroId, $adminUser, $adminFullName]);
            $aroId = $nextAroId;
            $pdo->exec("UPDATE gacl_aro_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro)");
        }

        // 3. Map ARO into admin group
        $stmtMap = $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)");
        $stmtMap->execute([$adminGroupId, $aroId]);
    }

    /**
     * Ensure the safe 'Tenant Administrators' group exists with correct ACL rules (idempotent)
     */
    public function ensureTenantAdminGroup(PDO $pdo): int
    {
        // 0. Clean up any invalid/dead root group placeholders with lft=0, rgt=0 and no members
        $pdo->exec("DELETE FROM gacl_aro_groups WHERE parent_id = 0 AND lft = 0 AND rgt = 0 AND id NOT IN (SELECT DISTINCT group_id FROM gacl_groups_aro_map)");

        // 1. Check if group already exists
        $groupStmt = $pdo->prepare("SELECT id FROM gacl_aro_groups WHERE value = 'tenant_admin' OR name = 'Tenant Administrators' LIMIT 1");
        $groupStmt->execute();
        $groupId = $groupStmt->fetchColumn();

        if (!$groupId) {
            $parentStmt = $pdo->query("SELECT id, rgt FROM gacl_aro_groups WHERE value = 'users' OR parent_id = 0 ORDER BY id ASC LIMIT 1");
            $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
            if (!$parent) {
                throw new RuntimeException("Parent 'users' ARO group not found");
            }
            $parentId = (int) $parent['id'];
            $parentRgt = (int) $parent['rgt'];

            $pdo->prepare("UPDATE gacl_aro_groups SET rgt = rgt + 2 WHERE rgt >= ?")->execute([$parentRgt]);
            $pdo->prepare("UPDATE gacl_aro_groups SET lft = lft + 2 WHERE lft > ?")->execute([$parentRgt]);

            $nextGroupId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_groups")->fetchColumn();
            $stmtInsertGroup = $pdo->prepare("INSERT INTO gacl_aro_groups (id, parent_id, name, value, lft, rgt) VALUES (?, ?, 'Tenant Administrators', 'tenant_admin', ?, ?)");
            $stmtInsertGroup->execute([$nextGroupId, $parentId, $parentRgt, $parentRgt + 1]);
            $groupId = $nextGroupId;
            $pdo->exec("UPDATE gacl_aro_groups_id_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_groups)");
        } else {
            $groupId = (int) $groupId;
        }

        // 2. Define safe permissions: physician/clinical + practice administration without super/acl/modules/database
        $writeAcos = [
            'acct' => ['bill', 'disc', 'eob', 'rep', 'rep_a'],
            'admin' => ['calendar', 'forms', 'practice', 'superbill', 'users', 'batchcom', 'language', 'drugs', 'menu'],
            'encounters' => ['auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed'],
            'inventory' => ['lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting'],
            'lists' => ['default', 'state', 'country', 'language', 'ethrace'],
            'patients' => ['appt', 'demo', 'med', 'trans', 'docs', 'docs_rm', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'pat_rep'],
            'sensitivities' => ['normal', 'high'],
            'nationnotes' => ['nn_configure'],
            'patientportal' => ['portal'],
            'menus' => ['modle'],
            'groups' => ['gadd', 'gcalendar', 'glog', 'gdlog', 'gm'],
        ];

        $viewAcos = [
            'patients' => ['pat_rep'],
        ];

        // Ensure write ACL
        $writeAclId = $pdo->query("
            SELECT a.id FROM gacl_acl a 
            JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
            WHERE agm.group_id = {$groupId} AND a.return_value = 'write' 
            LIMIT 1
        ")->fetchColumn();

        if (!$writeAclId) {
            $nextAclId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO gacl_acl (id, section_value, allow, enabled, return_value, note, updated_date) VALUES (?, 'system', 1, 1, 'write', 'Tenant Administrators full practice and clinical permissions', ?)");
            $stmt->execute([$nextAclId, time()]);
            $stmtMap = $pdo->prepare("INSERT IGNORE INTO gacl_aro_groups_map (acl_id, group_id) VALUES (?, ?)");
            $stmtMap->execute([$nextAclId, $groupId]);
            $writeAclId = $nextAclId;
            $pdo->exec("UPDATE gacl_acl_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl)");
        }

        $stmtAco = $pdo->prepare("INSERT IGNORE INTO gacl_aco_map (acl_id, section_value, value) VALUES (?, ?, ?)");
        foreach ($writeAcos as $sec => $vals) {
            foreach ($vals as $val) {
                $stmtAco->execute([$writeAclId, $sec, $val]);
            }
        }
        // Strict guard: ensure super, acl, manage_modules, database are NEVER present in tenant admin ACL
        $pdo->prepare("DELETE FROM gacl_aco_map WHERE acl_id = ? AND section_value = 'admin' AND value IN ('super', 'acl', 'manage_modules', 'database')")->execute([$writeAclId]);

        // Ensure view ACL
        $viewAclId = $pdo->query("
            SELECT a.id FROM gacl_acl a 
            JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
            WHERE agm.group_id = {$groupId} AND a.return_value = 'view' 
            LIMIT 1
        ")->fetchColumn();

        if (!$viewAclId) {
            $nextAclId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO gacl_acl (id, section_value, allow, enabled, return_value, note, updated_date) VALUES (?, 'system', 1, 1, 'view', 'Things that tenant administrators can only read', ?)");
            $stmt->execute([$nextAclId, time()]);
            $stmtMap = $pdo->prepare("INSERT IGNORE INTO gacl_aro_groups_map (acl_id, group_id) VALUES (?, ?)");
            $stmtMap->execute([$nextAclId, $groupId]);
            $viewAclId = $nextAclId;
            $pdo->exec("UPDATE gacl_acl_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl)");
        }

        foreach ($viewAcos as $sec => $vals) {
            foreach ($vals as $val) {
                $stmtAco->execute([$viewAclId, $sec, $val]);
            }
        }

        return $groupId;
    }

    /**
     * Map a doctor / customer user to the safe Tenant Administrators group
     */
    public function ensureTenantAdminUserAclMapped(string $dbName, string $username, string $fullName): void
    {
        $pdo = $this->getTenantPdo($dbName);
        $groupId = $this->ensureTenantAdminGroup($pdo);

        // 1. Ensure ARO exists for customer user
        $stmtAro = $pdo->prepare("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = ? LIMIT 1");
        $stmtAro->execute([$username]);
        $aroId = $stmtAro->fetchColumn();

        if (!$aroId) {
            $nextAroId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro")->fetchColumn();
            $stmtInsertAro = $pdo->prepare("INSERT INTO gacl_aro (id, section_value, value, order_value, name, hidden) VALUES (?, 'users', ?, 10, ?, 0)");
            $stmtInsertAro->execute([$nextAroId, $username, $fullName]);
            $aroId = $nextAroId;
            $pdo->exec("UPDATE gacl_aro_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro)");
        }

        // 2. Remove user from full Administrators / superuser groups if previously mapped
        $superGroupIds = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value IN ('admin', 'breakglass') OR name IN ('Administrators', 'Emergency Login')")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($superGroupIds)) {
            $inClause = implode(',', array_map('intval', $superGroupIds));
            $pdo->prepare("DELETE FROM gacl_groups_aro_map WHERE aro_id = ? AND group_id IN ({$inClause})")->execute([$aroId]);
        }

        // 3. Map into safe Tenant Administrators group
        $stmtMap = $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)");
        $stmtMap->execute([$groupId, $aroId]);
    }
}

