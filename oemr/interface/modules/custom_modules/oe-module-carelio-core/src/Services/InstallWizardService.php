<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class InstallWizardService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Get a specific configuration value
     */
    public static function getConfig(string $key, $default = null)
    {
        $row = sqlQuery("SELECT `config_value` FROM `hms_install_config` WHERE `config_key` = ? LIMIT 1", [$key]);
        if (!empty($row['config_value'])) {
            $decoded = json_decode($row['config_value'], true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row['config_value'];
        }
        return $default;
    }

    /**
     * Save configuration key-value
     */
    public static function saveConfig(string $key, $value, string $category = 'general', string $description = ''): void
    {
        $valStr = is_array($value) ? json_encode($value) : (string)$value;
        $sql = "INSERT INTO `hms_install_config` (`config_key`, `config_value`, `category`, `description`)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`), `category` = VALUES(`category`), `description` = VALUES(`description`), `updated_at` = NOW()";
        sqlStatement($sql, [$key, $valStr, $category, $description]);
    }

    /**
     * Retrieve current wizard state across all 9 steps with defaults
     */
    public static function getWizardState(): array
    {
        return [
            'is_completed' => self::getConfig('wizard_status', self::STATUS_PENDING) === self::STATUS_COMPLETED,
            'completed_at' => self::getConfig('wizard_completed_at', null),
            'completed_by' => self::getConfig('wizard_completed_by', null),
            
            // Step 1: Hospital Profile
            'step1' => self::getConfig('wizard_step1_profile', [
                'hospital_name' => 'Owen King EU (OKEU) National Hospital',
                'country' => 'LC',
                'currency' => 'XCD',
                'timezone' => 'America/St_Lucia',
                'site_mode' => 'single',
                'staff_language' => 'en',
                'patient_language' => 'en'
            ]),

            // Step 2: Modules
            'step2' => self::getConfig('wizard_step2_modules', [
                'selected_modules' => ['CORE', 'REG', 'SCH', 'CLN', 'CPOE', 'BILL', 'PHM']
            ]),

            // Step 3: Ward Types
            'step3' => self::getConfig('wizard_step3_ward_types', [
                'General Medicine / Mixed',
                'General Surgery / Post-Op',
                'Maternity / Antenatal / Postnatal',
                'Intensive Care Unit (ICU / HDU)',
                'Emergency & Triage Observation',
                'Pediatrics / Adolescents'
            ]),

            // Step 4: Clinical Services
            'step4' => self::getConfig('wizard_step4_clinical_services', [
                'Emergency Triage & Resuscitation',
                'Maternity & Labor Delivery',
                'Hemodialysis & Renal Care',
                'Endoscopy & Day Procedures',
                'Chemotherapy Day Unit',
                'Telemedicine & Remote Consults'
            ]),

            // Step 5: Locations & Beds
            'step5' => self::getConfig('wizard_step5_locations', [
                'baseline_model' => 'OKEU_NATIONAL_HOSPITAL',
                'total_beds_baseline' => 39,
                'buildings_count' => 1,
                'floors_count' => 4,
                'wards_count' => 4
            ]),

            // Step 6: Roles & Permissions
            'step6' => self::getConfig('wizard_step6_roles', [
                'roles_enabled' => [
                    'Consultant / Attending Physician',
                    'Resident / Intern Doctor',
                    'Inpatient Ward Nurse',
                    'Hospital Pharmacist',
                    'Laboratory & Radiology Technologist',
                    'Admissions & Front Desk Clerk',
                    'Billing & Claims Officer',
                    'Hospital Medical Records (HIM)',
                    'Privacy & Compliance Officer'
                ],
                'enforce_breakglass' => true,
                'five_tier_sensitivity' => true
            ]),

            // Step 7: Reference Data
            'step7' => self::getConfig('wizard_step7_reference_data', [
                'procedure_code_set' => 'SNOMED_CT',
                'diagnosis_code_set' => 'ICD10',
                'formulary_standard' => 'RxNorm',
                'insurers' => ['National Health Scheme (Saint Lucia)', 'Sagicor Life', 'Guardian General', 'Beacon Insurance', 'Self-Pay Cash']
            ]),

            // Step 8: Integrations
            'step8' => self::getConfig('wizard_step8_integrations', [
                'enable_fhir_r4' => true,
                'enable_rest_api' => true,
                'lis_engine' => 'OpenELIS Global (HL7 v2)',
                'ris_pacs' => 'Orthanc PACS with embedded OHIF Web Viewer',
                'orthanc_url' => 'http://localhost:8042'
            ]),

            // Step 9: Self-Check results
            'step9' => self::getConfig('wizard_step9_selfcheck', [
                'passed' => false,
                'checks' => []
            ])
        ];
    }

    /**
     * Execute full hospital configuration install & apply to OpenEMR
     */
    public static function applyWizard(array $payload, int $userId): array
    {
        // 1. Save each step into hms_install_config
        foreach ($payload as $stepKey => $stepData) {
            self::saveConfig('wizard_' . $stepKey, $stepData, 'wizard', 'Wizard ' . $stepKey . ' configuration');
        }

        // 2. Apply OpenEMR 8.4.1 Settings specified in Section 3
        // rest_api, rest_fhir_api, rest_portal_api set to 1
        sqlStatement("INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES ('rest_api', '1') ON DUPLICATE KEY UPDATE `gl_value` = '1'");
        sqlStatement("INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES ('rest_fhir_api', '1') ON DUPLICATE KEY UPDATE `gl_value` = '1'");
        sqlStatement("INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES ('rest_portal_api', '1') ON DUPLICATE KEY UPDATE `gl_value` = '1'");
        sqlStatement("INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES ('inhouse_pharmacy', '1') ON DUPLICATE KEY UPDATE `gl_value` = '1'");

        // Procedures coded from SNOMED CT; CPT4 set inactive (Section 3 of spec)
        sqlStatement("UPDATE `code_types` SET `ct_active` = 0 WHERE `ct_key` IN ('CPT4', 'HCPCS')");
        sqlStatement("UPDATE `code_types` SET `ct_active` = 1 WHERE `ct_key` IN ('SNOMED-CT', 'ICD10')");

        // 3. Ensure Bed Hierarchy is seeded if empty
        $bedCount = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_location` WHERE `location_type` = 'bed'");
        if (empty($bedCount['cnt']) || (int)$bedCount['cnt'] === 0) {
            LocationHierarchyService::seedSaintLuciaHospital(1);
        }

        // 4. Run Self-Check Tests
        $selfCheck = self::runSelfCheck();

        // 5. Mark wizard complete
        self::saveConfig('wizard_status', self::STATUS_COMPLETED, 'system', 'Wizard completion status');
        self::saveConfig('wizard_completed_at', date('Y-m-d H:i:s'), 'system', 'Timestamp of installation');
        self::saveConfig('wizard_completed_by', (string)$userId, 'system', 'User ID who executed wizard');
        self::saveConfig('wizard_step9_selfcheck', $selfCheck, 'system', 'Self-check validation results');

        // 6. Log internal event in hms_event
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('hospital_installed', 0, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$userId, json_encode(['action' => 'install_wizard_completed', 'checks' => $selfCheck])]
        );

        return [
            'success' => true,
            'message' => 'Carelio Hospital EHR Wizard completed successfully!',
            'self_check' => $selfCheck
        ];
    }

    /**
     * Run automated self-check validation tests
     */
    public static function runSelfCheck(): array
    {
        $checks = [];

        // Check 1: hms_location hierarchy
        $locTotal = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_location`");
        $checks['location_hierarchy'] = [
            'title' => 'Hospital Bed & Ward Location Hierarchy',
            'status' => ((int)$locTotal['cnt'] >= 30) ? 'pass' : 'warn',
            'detail' => $locTotal['cnt'] . ' physical nodes active in hms_location'
        ];

        // Check 2: Module registry
        $modTotal = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_module_registry`");
        $checks['module_registry'] = [
            'title' => 'HMS Module Registry & Dependency Catalog',
            'status' => ((int)$modTotal['cnt'] >= 23) ? 'pass' : 'fail',
            'detail' => $modTotal['cnt'] . ' hospital modules cataloged'
        ];

        // Check 3: API & FHIR settings
        $fhir = sqlQuery("SELECT `gl_value` FROM `globals` WHERE `gl_name` = 'rest_fhir_api'");
        $checks['fhir_api'] = [
            'title' => 'FHIR R4 & REST API Gateway',
            'status' => (!empty($fhir['gl_value']) && $fhir['gl_value'] == '1') ? 'pass' : 'fail',
            'detail' => 'OpenEMR FHIR R4 enabled for module interoperability'
        ];

        // Check 4: SNOMED CT procedure activation
        $snomed = sqlQuery("SELECT `ct_active` FROM `code_types` WHERE `ct_key` = 'SNOMED-CT'");
        $checks['snomed_ct'] = [
            'title' => 'SNOMED CT Procedure Code Standard (Caribbean Profile)',
            'status' => (!empty($snomed['ct_active']) && $snomed['ct_active'] == 1) ? 'pass' : 'fail',
            'detail' => 'SNOMED CT activated (CPT4 deactivated)'
        ];

        // Check 5: Sensitive record table
        $sensTable = sqlQuery("SHOW TABLES LIKE 'hms_restricted_record'");
        $checks['sensitivity_storage'] = [
            'title' => '5-Tier Chart Sensitivity Model Table',
            'status' => !empty($sensTable) ? 'pass' : 'fail',
            'detail' => 'hms_restricted_record verified in database'
        ];

        // Check 6: Clinical Governance sign-off gate
        $apprTable = sqlQuery("SHOW TABLES LIKE 'hms_content_approval'");
        $checks['content_approval'] = [
            'title' => 'Medical Director Clinical Content Approval Gate',
            'status' => !empty($apprTable) ? 'pass' : 'fail',
            'detail' => 'hms_content_approval verified in database'
        ];

        // Check 7: Hospital Event Queue
        $eventTable = sqlQuery("SHOW TABLES LIKE 'hms_event'");
        $checks['event_broker'] = [
            'title' => 'Internal Event Bus (hms_event)',
            'status' => !empty($eventTable) ? 'pass' : 'fail',
            'detail' => 'Asynchronous event bus active'
        ];

        $allPass = true;
        foreach ($checks as $c) {
            if ($c['status'] === 'fail') {
                $allPass = false;
                break;
            }
        }

        return [
            'passed' => $allPass,
            'items' => $checks
        ];
    }
}
