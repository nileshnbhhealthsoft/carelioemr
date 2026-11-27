-- Carelio Hospital EHR (HMS) - Phase 1: CORE Foundation Schema
-- Strict adherence to specification: prefix all new tables with hms_
-- Zero modification to OpenEMR core tables.

USE `openemr`;

-- 1. Location & Bed Hierarchy (Site -> Building -> Floor -> Ward -> Room -> Bed)
CREATE TABLE IF NOT EXISTS `hms_location` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NULL,
    `facility_id` INT NOT NULL DEFAULT 1,
    `location_type` ENUM('site', 'building', 'floor', 'ward', 'room', 'bed') NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `specialty` VARCHAR(100) NULL COMMENT 'e.g. general_medicine, surgery, pediatrics, maternity, icu',
    `care_level` VARCHAR(50) NULL COMMENT 'e.g. general, step_down, icu, hdu, observation',
    `accommodation_type` VARCHAR(50) NULL COMMENT 'e.g. public, private, semi_private, isolation',
    `status` VARCHAR(50) NOT NULL DEFAULT 'active' COMMENT 'active, maintenance, blocked, closed',
    `sort_order` INT NOT NULL DEFAULT 0,
    `valid_from` DATETIME NULL,
    `valid_to` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_loc_parent` (`parent_id`),
    INDEX `idx_hms_loc_facility` (`facility_id`),
    INDEX `idx_hms_loc_type` (`location_type`),
    INDEX `idx_hms_loc_status` (`status`),
    CONSTRAINT `fk_hms_location_parent` FOREIGN KEY (`parent_id`) REFERENCES `hms_location`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. HMS Module Registry (Tracks HMS modules, dependencies and phases)
CREATE TABLE IF NOT EXISTS `hms_module_registry` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    `phase` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `status` VARCHAR(50) NOT NULL DEFAULT 'drafting' COMMENT 'not_started, drafting, in_review, approved, active',
    `dependencies` JSON NULL COMMENT 'Array of required module codes',
    `description` TEXT NULL,
    `installed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_mod_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Install & Hospital Configuration Settings
CREATE TABLE IF NOT EXISTS `hms_install_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(100) NOT NULL UNIQUE,
    `config_value` TEXT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_cfg_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Reference Data Load History
CREATE TABLE IF NOT EXISTS `hms_reference_load` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dataset_key` VARCHAR(100) NOT NULL,
    `dataset_version` VARCHAR(50) NOT NULL,
    `records_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(50) NOT NULL DEFAULT 'loaded',
    `loaded_by` VARCHAR(100) NULL,
    `loaded_at` DATETIME NOT NULL,
    `details` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_hms_ref_key` (`dataset_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Sensitive Record Access & Restriction Flags
CREATE TABLE IF NOT EXISTS `hms_restricted_record` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `record_type` ENUM('patient', 'encounter') NOT NULL,
    `record_id` BIGINT NOT NULL COMMENT 'pid or encounter number',
    `sensitivity_level` VARCHAR(50) NOT NULL COMMENT 'normal, restricted, highly_restricted, staff_as_patient, protected_identity',
    `reason` VARCHAR(255) NULL,
    `flagged_by` BIGINT NOT NULL COMMENT 'users.id',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_restr_target` (`record_type`, `record_id`),
    INDEX `idx_hms_restr_level` (`sensitivity_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Clinical Content Governance & Sign-Off
CREATE TABLE IF NOT EXISTS `hms_content_approval` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content_type` VARCHAR(50) NOT NULL COMMENT 'form, template, order_set, alert_rule, document',
    `content_code` VARCHAR(100) NOT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `title` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'draft' COMMENT 'draft, pending_approval, approved, rejected, superseded',
    `author_id` BIGINT NOT NULL COMMENT 'users.id',
    `approved_by` BIGINT NULL COMMENT 'users.id (medical director)',
    `approved_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_appr_type_code` (`content_type`, `content_code`),
    INDEX `idx_hms_appr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. HMS Internal Event Queue / Event Bus
CREATE TABLE IF NOT EXISTS `hms_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(100) NOT NULL COMMENT 'admission, transfer, discharge, order_placed, result_ready',
    `pid` BIGINT NOT NULL COMMENT 'patient_data.pid',
    `encounter` BIGINT NULL COMMENT 'form_encounter.encounter',
    `facility_id` INT NULL COMMENT 'facility.id',
    `actor_id` BIGINT NOT NULL COMMENT 'users.id',
    `effective_time` DATETIME NOT NULL,
    `recorded_time` DATETIME NOT NULL,
    `payload` JSON NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'published' COMMENT 'pending, published, processed, failed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_hms_evt_pid` (`pid`),
    INDEX `idx_hms_evt_enc` (`encounter`),
    INDEX `idx_hms_evt_type` (`event_type`),
    INDEX `idx_hms_evt_time` (`effective_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 24-Hour Break-Glass Emergency Override Audit Log
CREATE TABLE IF NOT EXISTS `hms_breakglass_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pid` BIGINT NOT NULL,
    `encounter` BIGINT NULL,
    `user_id` BIGINT NOT NULL,
    `sensitivity_level` VARCHAR(50) NOT NULL,
    `reason` TEXT NOT NULL,
    `accessed_at` DATETIME NOT NULL,
    `reviewed_by` BIGINT NULL,
    `reviewed_at` DATETIME NULL,
    `review_status` VARCHAR(50) NOT NULL DEFAULT 'pending_review',
    `review_notes` TEXT NULL,
    `due_by` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bg_pid` (`pid`),
    INDEX `idx_bg_user` (`user_id`),
    INDEX `idx_bg_status` (`review_status`),
    INDEX `idx_bg_due` (`due_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Emergency Temporary MRN & Master Patient Index Reconciliation (Module 2: REG)
CREATE TABLE IF NOT EXISTS `hms_temp_identity` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `temp_mrn` VARCHAR(50) NOT NULL UNIQUE,
    `temp_pid` BIGINT NOT NULL,
    `estimated_gender` VARCHAR(20) NOT NULL DEFAULT 'unknown',
    `estimated_age` INT NULL,
    `triage_color` VARCHAR(20) NOT NULL DEFAULT 'red',
    `arrival_notes` TEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
    `reconciled_pid` BIGINT NULL,
    `reconciled_by` BIGINT NULL,
    `reconciled_at` DATETIME NULL,
    `created_by` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_temp_mrn` (`temp_mrn`),
    INDEX `idx_temp_pid` (`temp_pid`),
    INDEX `idx_temp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Pre-Admission Deposit Collection (Module 2: REG)
CREATE TABLE IF NOT EXISTS `hms_deposit` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `pid` BIGINT NOT NULL,
    `encounter` BIGINT NULL,
    `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'XCD',
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
    `payment_reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `ar_session_id` BIGINT NULL,
    `collected_by` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dep_pid` (`pid`),
    INDEX `idx_dep_receipt` (`receipt_number`),
    INDEX `idx_dep_ar` (`ar_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Patient Merge & Unmerge Audit Log (Module 2: REG)
CREATE TABLE IF NOT EXISTS `hms_merge_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `merge_type` ENUM('patient_patient', 'temp_reconciliation') NOT NULL DEFAULT 'patient_patient',
    `source_pid` BIGINT NOT NULL,
    `target_pid` BIGINT NOT NULL,
    `snapshot_data` LONGTEXT NOT NULL COMMENT 'JSON snapshot of moved encounter IDs, vital IDs, procedure order IDs, prescriptions',
    `reason` TEXT DEFAULT NULL,
    `status` ENUM('merged', 'unmerged') NOT NULL DEFAULT 'merged',
    `merged_by` BIGINT NOT NULL,
    `merged_at` DATETIME NOT NULL,
    `unmerged_by` BIGINT DEFAULT NULL,
    `unmerged_at` DATETIME DEFAULT NULL,
    `unmerge_reason` TEXT DEFAULT NULL,
    INDEX `idx_source_pid` (`source_pid`),
    INDEX `idx_target_pid` (`target_pid`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Patient Inpatient Treatment & Consent Capture (Module 2: REG)
CREATE TABLE IF NOT EXISTS `hms_consent` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pid` BIGINT NOT NULL,
    `consent_type` ENUM('general_treatment', 'financial_guarantee', 'hipaa_release', 'surgical_anesthesia') NOT NULL DEFAULT 'general_treatment',
    `status` ENUM('signed', 'refused', 'revoked') NOT NULL DEFAULT 'signed',
    `signer_name` VARCHAR(255) NOT NULL,
    `signer_relationship` VARCHAR(100) NOT NULL DEFAULT 'self',
    `witness_user_id` BIGINT NOT NULL,
    `signed_at` DATETIME NOT NULL,
    `scan_path` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    INDEX `idx_consent_pid` (`pid`),
    INDEX `idx_consent_type` (`consent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Physical Hospital Resources (Module 3: SCH)
CREATE TABLE IF NOT EXISTS `hms_resource` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `resource_code` VARCHAR(50) NOT NULL UNIQUE,
    `resource_name` VARCHAR(150) NOT NULL,
    `resource_type` ENUM('ROOM', 'THEATRE', 'EQUIPMENT', 'BED') NOT NULL DEFAULT 'ROOM',
    `location_id` BIGINT UNSIGNED NULL COMMENT 'FK to hms_location.id',
    `specialty` VARCHAR(100) NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    `created_by` BIGINT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_res_type` (`resource_type`),
    INDEX `idx_res_loc` (`location_id`),
    INDEX `idx_res_avail` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Recurring Treatment Series (Module 3: SCH)
CREATE TABLE IF NOT EXISTS `hms_session_series` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pid` BIGINT NOT NULL COMMENT 'FK to patient_data.pid',
    `resource_id` BIGINT UNSIGNED NULL COMMENT 'Preferred/assigned hms_resource.id',
    `provider_id` BIGINT NULL COMMENT 'Ordering clinician users.id',
    `treatment_type` ENUM('DIALYSIS', 'CHEMOTHERAPY', 'PHYSIOTHERAPY') NOT NULL,
    `title` VARCHAR(150) NULL,
    `total_sessions` INT UNSIGNED NOT NULL,
    `completed_sessions` INT UNSIGNED NOT NULL DEFAULT 0,
    `frequency` VARCHAR(50) NOT NULL DEFAULT '3x weekly',
    `session_duration_min` INT UNSIGNED NOT NULL DEFAULT 180,
    `fee_per_session` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `status` ENUM('ACTIVE', 'COMPLETED', 'PAUSED', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    `notes` TEXT NULL,
    `created_by` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_series_pid` (`pid`),
    INDEX `idx_series_type` (`treatment_type`),
    INDEX `idx_series_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Physical Resource Bookings & Conflict Ledger (Module 3: SCH)
CREATE TABLE IF NOT EXISTS `hms_resource_booking` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `resource_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK to hms_resource.id',
    `pid` BIGINT NOT NULL COMMENT 'FK to patient_data.pid',
    `encounter_id` BIGINT NULL COMMENT 'FK to form_encounter.encounter',
    `series_id` BIGINT UNSIGNED NULL COMMENT 'FK to hms_session_series.id',
    `session_number` INT UNSIGNED NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `booking_status` ENUM('REQUESTED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW') NOT NULL DEFAULT 'CONFIRMED',
    `purpose` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `charge_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `charge_posted` TINYINT(1) NOT NULL DEFAULT 0,
    `booked_by` BIGINT NOT NULL COMMENT 'FK to users.id',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bk_res_time` (`resource_id`, `start_time`, `end_time`),
    INDEX `idx_bk_pid` (`pid`),
    INDEX `idx_bk_series` (`series_id`),
    INDEX `idx_bk_status` (`booking_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
