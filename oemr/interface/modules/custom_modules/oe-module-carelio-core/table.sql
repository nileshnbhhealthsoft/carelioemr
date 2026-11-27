-- Carelio Hospital EHR - Module 1: CORE Foundation Tables
CREATE TABLE IF NOT EXISTS `hms_location` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NULL,
    `facility_id` INT NOT NULL DEFAULT 1,
    `location_type` ENUM('site', 'building', 'floor', 'ward', 'room', 'bed') NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `specialty` VARCHAR(100) NULL,
    `care_level` VARCHAR(50) NULL,
    `accommodation_type` VARCHAR(50) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
    `sort_order` INT NOT NULL DEFAULT 0,
    `valid_from` DATETIME NULL,
    `valid_to` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_loc_parent` (`parent_id`),
    INDEX `idx_hms_loc_facility` (`facility_id`),
    INDEX `idx_hms_loc_type` (`location_type`),
    INDEX `idx_hms_loc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_module_registry` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    `phase` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `status` VARCHAR(50) NOT NULL DEFAULT 'drafting',
    `dependencies` JSON NULL,
    `description` TEXT NULL,
    `installed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_mod_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `hms_restricted_record` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `record_type` ENUM('patient', 'encounter') NOT NULL,
    `record_id` BIGINT NOT NULL,
    `sensitivity_level` VARCHAR(50) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `flagged_by` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_restr_target` (`record_type`, `record_id`),
    INDEX `idx_hms_restr_level` (`sensitivity_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_content_approval` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content_type` VARCHAR(50) NOT NULL,
    `content_code` VARCHAR(100) NOT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `title` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
    `author_id` BIGINT NOT NULL,
    `approved_by` BIGINT NULL,
    `approved_at` DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hms_appr_type_code` (`content_type`, `content_code`),
    INDEX `idx_hms_appr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_event` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(100) NOT NULL,
    `pid` BIGINT NOT NULL,
    `encounter` BIGINT NULL,
    `facility_id` INT NULL,
    `actor_id` BIGINT NOT NULL,
    `effective_time` DATETIME NOT NULL,
    `recorded_time` DATETIME NOT NULL,
    `payload` JSON NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_hms_evt_pid` (`pid`),
    INDEX `idx_hms_evt_enc` (`encounter`),
    INDEX `idx_hms_evt_type` (`event_type`),
    INDEX `idx_hms_evt_time` (`effective_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
