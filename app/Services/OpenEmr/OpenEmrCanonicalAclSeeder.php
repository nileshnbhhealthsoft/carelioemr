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
        try {
            if (function_exists('base_path') && app()->has('path.base')) {
                return base_path('oemr');
            }
        } catch (Throwable $e) {}
        return 'E:/xampp/htdocs/1page/oemr';
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

        return true;
    }

    /**
     * Ensure the specified admin user exists as an ARO and is linked to the canonical 'admin' group
     */
    public function ensureAdminUserAclMapped(string $dbName, string $adminUser, string $adminFullName): void
    {
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

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
}

