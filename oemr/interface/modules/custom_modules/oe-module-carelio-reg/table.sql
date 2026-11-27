-- Carelio Hospital EHR - Module 2: REG Tables
USE `openemr`;

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

CREATE TABLE IF NOT EXISTS `hms_merge_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `merge_type` ENUM('patient_patient', 'temp_reconciliation') NOT NULL DEFAULT 'patient_patient',
    `source_pid` BIGINT NOT NULL,
    `target_pid` BIGINT NOT NULL,
    `snapshot_data` LONGTEXT NOT NULL,
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
