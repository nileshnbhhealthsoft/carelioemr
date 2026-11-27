<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class ModuleCatalogService
{
    /**
     * Complete Module Catalog from Carelio Hospital EHR Specification
     * Section 17, Pages 34-36
     */
    public static function getSpecificationModules(): array
    {
        return [
            // Phase 1
            ['code' => 'CORE', 'name' => 'Shared Foundation & Bed Hierarchy', 'phase' => 1, 'status' => 'active', 'deps' => []],
            ['code' => 'REG', 'name' => 'Registration & Master Patient Index', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE']],
            ['code' => 'SCH', 'name' => 'Scheduling & Resource Appointments', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE', 'REG']],
            ['code' => 'CLN', 'name' => 'Clinical Documentation & Rounds', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE', 'REG']],
            ['code' => 'CPOE', 'name' => 'Orders & Results (CPOE)', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE', 'CLN']],
            ['code' => 'BILL', 'name' => 'Billing, Claims & Insurance Packages', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE', 'REG']],
            ['code' => 'PHM', 'name' => 'Pharmacy & Formulary Dispensing', 'phase' => 1, 'status' => 'active', 'deps' => ['CORE', 'CLN']],
            ['code' => 'HIM', 'name' => 'Records, Coding & Document Completion', 'phase' => 1, 'status' => 'drafting', 'deps' => ['CORE', 'REG']],
            ['code' => 'ADMIN', 'name' => 'Security, Break-Glass & Integration Engine', 'phase' => 1, 'status' => 'drafting', 'deps' => ['CORE']],
            
            // Phase 2
            ['code' => 'LIS', 'name' => 'Laboratory Information System (OpenELIS Global)', 'phase' => 2, 'status' => 'drafting', 'deps' => ['CORE', 'REG', 'CPOE']],
            ['code' => 'RIS', 'name' => 'Radiology & PACS (Orthanc & OHIF Viewer)', 'phase' => 2, 'status' => 'drafting', 'deps' => ['CORE', 'REG', 'CPOE']],
            ['code' => 'RPT', 'name' => 'Hospital Reporting & Quality Analytics', 'phase' => 2, 'status' => 'drafting', 'deps' => ['CORE']],
            ['code' => 'PORTAL', 'name' => 'Patient Portal & Telemedicine', 'phase' => 2, 'status' => 'drafting', 'deps' => ['CORE', 'REG', 'SCH']],
            
            // Phase 3
            ['code' => 'ADT', 'name' => 'Inpatient Bed Management & ADT Lifecycle', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'REG']],
            ['code' => 'eMAR', 'name' => 'Electronic Medication Administration Record', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'ADT', 'PHM']],
            ['code' => 'OR', 'name' => 'Surgical Theatre & Anesthesia (OT)', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'ADT', 'CPOE']],
            ['code' => 'CRIT', 'name' => 'Critical Care & ICU Device Flowsheets', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'ADT', 'CPOE', 'eMAR']],
            ['code' => 'OBS', 'name' => 'Maternity, Partogram & Neonatal Care', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'REG', 'ADT']],
            ['code' => 'INV', 'name' => 'Hospital Inventory & Consumable Requisitions', 'phase' => 3, 'status' => 'drafting', 'deps' => ['CORE', 'PHM']],
            
            // Phase 4
            ['code' => 'DIET', 'name' => 'Dietary, Nutrition & Meal Tickets', 'phase' => 4, 'status' => 'drafting', 'deps' => ['CORE', 'ADT', 'CLN']],
            ['code' => 'QA', 'name' => 'Quality, Incident Logs & Infection Control', 'phase' => 4, 'status' => 'drafting', 'deps' => ['CORE', 'LIS', 'ADT', 'RPT']],
            ['code' => 'FAC', 'name' => 'Facilities, Cleaning Tasks & Biomedical', 'phase' => 4, 'status' => 'drafting', 'deps' => ['CORE', 'ADT']],
            ['code' => 'FIN/HR', 'name' => 'Hospital ERP, Payroll & Nurse Rostering', 'phase' => 4, 'status' => 'drafting', 'deps' => ['CORE', 'BILL', 'INV']]
        ];
    }

    /**
     * Synchronize specification modules into hms_module_registry table
     */
    public static function syncRegistry(): void
    {
        $modules = self::getSpecificationModules();
        foreach ($modules as $m) {
            $deps = json_encode($m['deps']);
            $sql = "INSERT INTO `hms_module_registry` 
                    (`module_code`, `name`, `phase`, `status`, `dependencies`)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    `name` = VALUES(`name`),
                    `phase` = VALUES(`phase`),
                    `dependencies` = VALUES(`dependencies`)";
            sqlStatement($sql, [$m['code'], $m['name'], $m['phase'], $m['status'], $deps]);
        }
    }

    /**
     * Retrieve all registered modules
     */
    public static function getAll(): array
    {
        self::syncRegistry();
        $sql = "SELECT * FROM `hms_module_registry` ORDER BY `phase` ASC, `id` ASC";
        $rows = sqlStatement($sql);
        $result = [];
        while ($row = sqlFetchArray($rows)) {
            $row['deps_array'] = !empty($row['dependencies']) ? json_decode($row['dependencies'], true) : [];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Validate prerequisites before activating a module
     * Conforms to Specification Section 18.1 Acceptance Criteria
     */
    public static function validateDependencies(string $moduleCode): array
    {
        $target = sqlQuery("SELECT * FROM `hms_module_registry` WHERE `module_code` = ? LIMIT 1", [$moduleCode]);
        if (!$target) {
            return ['can_activate' => false, 'missing' => [], 'message' => "Module $moduleCode not found."];
        }

        $deps = !empty($target['dependencies']) ? json_decode($target['dependencies'], true) : [];
        if (empty($deps)) {
            return ['can_activate' => true, 'missing' => [], 'message' => 'No dependencies required.'];
        }

        $missing = [];
        foreach ($deps as $depCode) {
            $dep = sqlQuery("SELECT `status` FROM `hms_module_registry` WHERE `module_code` = ? LIMIT 1", [$depCode]);
            if (empty($dep['status']) || $dep['status'] !== 'active') {
                $missing[] = $depCode;
            }
        }

        if (!empty($missing)) {
            return [
                'can_activate' => false,
                'missing' => $missing,
                'message' => "Cannot activate " . $moduleCode . ": Missing active dependency (" . implode(', ', $missing) . ")."
            ];
        }

        return ['can_activate' => true, 'missing' => [], 'message' => 'All dependencies satisfied.'];
    }

    /**
     * Change module operational status with dependency guard
     */
    public static function setModuleStatus(string $moduleCode, string $newStatus, int $userId): array
    {
        $allowed = ['active', 'drafting', 'paused', 'retired'];
        if (!in_array($newStatus, $allowed, true)) {
            return ['success' => false, 'error' => "Invalid status: $newStatus"];
        }

        // Guard 1: When activating, verify forward dependencies
        if ($newStatus === 'active') {
            $val = self::validateDependencies($moduleCode);
            if (!$val['can_activate']) {
                return [
                    'success' => false,
                    'error' => $val['message'],
                    'missing' => $val['missing']
                ];
            }
        }

        // Guard 2: When pausing/retiring, check reverse dependencies
        if (in_array($newStatus, ['drafting', 'paused', 'retired'], true)) {
            $activeMods = sqlStatement("SELECT `module_code`, `dependencies` FROM `hms_module_registry` WHERE `status` = 'active' AND `module_code` != ?", [$moduleCode]);
            $blocking = [];
            while ($m = sqlFetchArray($activeMods)) {
                $d = !empty($m['dependencies']) ? json_decode($m['dependencies'], true) : [];
                if (in_array($moduleCode, $d, true)) {
                    $blocking[] = $m['module_code'];
                }
            }
            if (!empty($blocking)) {
                return [
                    'success' => false,
                    'error' => "Cannot change $moduleCode to $newStatus: Active module(s) (" . implode(', ', $blocking) . ") depend on it!"
                ];
            }
        }

        sqlStatement("UPDATE `hms_module_registry` SET `status` = ?, `installed_at` = IF(? = 'active', NOW(), `installed_at`) WHERE `module_code` = ?", [$newStatus, $newStatus, $moduleCode]);

        // Publish event to hms_event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('module_status_changed', 0, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$userId, json_encode(['module_code' => $moduleCode, 'new_status' => $newStatus])]
        );

        return ['success' => true, 'message' => "Module $moduleCode status updated to $newStatus."];
    }
}
