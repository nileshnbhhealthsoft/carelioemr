<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

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
     * Execute native branding and phpGACL Site Administrator configuration on a target PDO instance.
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
        // 2. Native phpGACL: Ensure 'Site Administrator' ARO Group
        // -------------------------------------------------------------
        // Group ID 19, Parent ID 10 (Physicians), value: 'site_admin'
        $groupId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'site_admin' OR name = 'Site Administrator' LIMIT 1")->fetchColumn();

        if (!$groupId) {
            // Find parent group ID for Physicians (standard OpenEMR ID 10)
            $parentId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'doc' OR name = 'Physicians' LIMIT 1")->fetchColumn();
            $parentId = $parentId ? (int) $parentId : 10;

            $parentRgt = $pdo->query("SELECT rgt FROM gacl_aro_groups WHERE id = {$parentId}")->fetchColumn();
            $parentRgt = $parentRgt !== false ? (int) $parentRgt : 0;

            if ($parentRgt > 0) {
                $pdo->prepare("UPDATE gacl_aro_groups SET rgt = rgt + 2 WHERE rgt >= ?")->execute([$parentRgt]);
                $pdo->prepare("UPDATE gacl_aro_groups SET lft = lft + 2 WHERE lft > ?")->execute([$parentRgt]);
            }

            $nextGroupId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_groups")->fetchColumn();
            if ($nextGroupId < 19) {
                $nextGroupId = 19;
            }

            $stmtGroup = $pdo->prepare("
                INSERT INTO gacl_aro_groups (id, parent_id, name, value, lft, rgt) 
                VALUES (?, ?, 'Site Administrator', 'site_admin', ?, ?)
            ");
            $stmtGroup->execute([$nextGroupId, $parentId, $parentRgt, $parentRgt + 1]);
            $groupId = $nextGroupId;

            $pdo->exec("UPDATE gacl_aro_groups_id_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_groups)");
        } else {
            $groupId = (int) $groupId;
            $pdo->prepare("UPDATE gacl_aro_groups SET name = 'Site Administrator', value = 'site_admin' WHERE id = ?")->execute([$groupId]);
        }

        // -------------------------------------------------------------
        // 3. Define ACO Permissions for Site Administrator Group
        // -------------------------------------------------------------
        // Permitted: Clinic users, practice settings, superbill/coding, calendar, clinical data
        // Strictly OMITTED: admin:super, admin:acl, admin:forms, admin:manage_modules, menus:modle
        $writeAcos = [
            'admin' => ['users', 'practice', 'superbill', 'calendar', 'batchcom', 'drugs', 'language', 'menu'],
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

        // Ensure write ACL entry exists for Site Admin group
        $writeAclId = $pdo->query("
            SELECT a.id FROM gacl_acl a 
            JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
            WHERE agm.group_id = {$groupId} AND a.return_value = 'write' 
            LIMIT 1
        ")->fetchColumn();

        if (!$writeAclId) {
            $nextAclId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl")->fetchColumn();
            $stmtAcl = $pdo->prepare("
                INSERT INTO gacl_acl (id, section_value, allow, enabled, return_value, note, updated_date) 
                VALUES (?, 'system', 1, 1, 'write', 'Site Administrator full practice and clinical permissions', ?)
            ");
            $stmtAcl->execute([$nextAclId, time()]);
            $pdo->prepare("INSERT IGNORE INTO gacl_aro_groups_map (acl_id, group_id) VALUES (?, ?)")->execute([$nextAclId, $groupId]);
            $writeAclId = $nextAclId;
            $pdo->exec("UPDATE gacl_acl_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl)");
        }

        // Map ACOs to write ACL entry
        $stmtInsertAcoMap = $pdo->prepare("INSERT IGNORE INTO gacl_aco_map (acl_id, section_value, value) VALUES (?, ?, ?)");
        foreach ($writeAcos as $section => $values) {
            foreach ($values as $val) {
                // Ensure ACO exists in gacl_aco
                $acoExists = $pdo->query("SELECT id FROM gacl_aco WHERE section_value = '{$section}' AND value = '{$val}' LIMIT 1")->fetchColumn();
                if ($acoExists) {
                    $stmtInsertAcoMap->execute([$writeAclId, $section, $val]);
                }
            }
        }

        // -------------------------------------------------------------
        // 4. Map Tenant Doctor User(s) to Site Administrator Group
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
            $u = $doc['username'];
            $name = $doc['name'];

            $aroId = $pdo->query("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = " . $pdo->quote($u) . " LIMIT 1")->fetchColumn();
            if (!$aroId) {
                $nextAroId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro")->fetchColumn();
                $stmtAro = $pdo->prepare("INSERT INTO gacl_aro (id, section_value, value, order_value, name, hidden) VALUES (?, 'users', ?, 10, ?, 0)");
                $stmtAro->execute([$nextAroId, $u, $name]);
                $aroId = $nextAroId;
                $pdo->exec("UPDATE gacl_aro_seq SET id = (SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro)");
            } else {
                $aroId = (int) $aroId;
            }

            // Resolve standard group IDs dynamically
            $physicianGroupId = (int) $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'doc' OR name = 'Physicians' LIMIT 1")->fetchColumn();
            $clinicianGroupId = (int) $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'clin' OR name = 'Clinicians' LIMIT 1")->fetchColumn();
            $superAdminGroupId = (int) $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'admin' OR name = 'Administrators' LIMIT 1")->fetchColumn();

            // Map into Site Administrator group, plus Physicians and Clinicians
            $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)")->execute([$groupId, $aroId]);
            if ($physicianGroupId) {
                $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)")->execute([$physicianGroupId, $aroId]);
            }
            if ($clinicianGroupId) {
                $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)")->execute([$clinicianGroupId, $aroId]);
            }

            // Ensure removed from Super Administrators group (so Config, System, ACL, Forms, Modules are naturally hidden)
            if ($superAdminGroupId && $superAdminGroupId !== $groupId) {
                $pdo->prepare("DELETE FROM gacl_groups_aro_map WHERE group_id = ? AND aro_id = ?")->execute([$superAdminGroupId, $aroId]);
            }
        }

        // Restore standard main_menu_role for all users (using native upstream standard.json)
        $pdo->exec("UPDATE users SET main_menu_role = 'standard'");
    }
}
