<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OpenEMR\Modules\SiteAdmin\Installer\SiteAdminInstaller;
use PDO;

class OpenemrTenantAclAndBrandingSeeder extends Seeder
{
    /**
     * Run the database seeds on the default or specified database connection.
     */
    public function run(?string $connection = null): void
    {
        $pdo = DB::connection($connection)->getPdo();
        $this->runOnPdo($pdo);
    }

    /**
     * Execute native branding and invoke Site Administrator module installer on target PDO instance.
     */
    public function runOnPdo(PDO $pdo, ?string $doctorUsername = null, ?string $doctorFullName = null): void
    {
        // -------------------------------------------------------------
        // 1. Native Branding via globals Table
        // -------------------------------------------------------------
        $branding = [
            'openemr_name' => 'CarelioEMR',
            'login_tagline_text' => 'CarelioEMR - Advanced Clinical & Medical Practice Management EHR',
            'show_tagline_on_login' => '1',
            'main_menu_logo_title' => 'CarelioEMR',
            'display_main_menu_logo' => '1',
            'show_primary_logo' => '1',
            'login_page_layout' => 'login/layouts/vertical_band.html.twig',
            'portal_custom_title' => 'CarelioEMR Patient Portal',
        ];

        $stmtGlobal = $pdo->prepare("
            INSERT INTO globals (gl_name, gl_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE gl_value = VALUES(gl_value)
        ");

        foreach ($branding as $name => $value) {
            $stmtGlobal->execute([$name, $value]);
        }

        // -------------------------------------------------------------
        // 2. Delegate Site Administrator ACL & Runtime Setup to Custom Module
        // -------------------------------------------------------------
        $this->ensureModuleClassesLoaded();

        // Idempotently install Site Administrator group and ACLs via custom module
        SiteAdminInstaller::install($pdo);

        // -------------------------------------------------------------
        // 3. Map Target Doctor User(s) via Custom Module
        // -------------------------------------------------------------
        $doctorsToMap = [];
        if (!empty($doctorUsername)) {
            $doctorsToMap[] = [
                'username' => $doctorUsername,
                'name' => $doctorFullName ?: $doctorUsername
            ];
        } else {
            // Map all non-admin authorized providers in this tenant
            $stmtDocs = $pdo->query("SELECT username, CONCAT(COALESCE(fname,''), ' ', COALESCE(lname,'')) AS full_name FROM users WHERE username != 'admin' AND authorized = 1");
            while ($row = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
                $doctorsToMap[] = [
                    'username' => $row['username'],
                    'name' => trim($row['full_name']) ?: $row['username']
                ];
            }
        }

        foreach ($doctorsToMap as $doc) {
            if (strcasecmp($doc['username'], 'admin') === 0) {
                continue; // Safety guard: never map root admin
            }
            SiteAdminInstaller::assignUser($doc['username'], $doc['name'], $pdo);
        }
    }

    /**
     * Ensure OpenEMR autoloader and module classes are loaded in Laravel context
     */
    protected function ensureModuleClassesLoaded(): void
    {
        if (!class_exists(SiteAdminInstaller::class)) {
            $oemrAutoload = base_path('oemr/vendor/autoload.php');
            if (file_exists($oemrAutoload)) {
                require_once $oemrAutoload;
            }

            $moduleBootstrap = base_path('oemr/interface/modules/custom_modules/test/openemr.bootstrap.php');
            if (file_exists($moduleBootstrap)) {
                require_once $moduleBootstrap;
            }

            $installerClass = base_path('oemr/interface/modules/custom_modules/test/src/Installer/SiteAdminInstaller.php');
            if (file_exists($installerClass)) {
                require_once $installerClass;
            }
        }
    }
}
