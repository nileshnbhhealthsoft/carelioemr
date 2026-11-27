-- Carelio Hospital EHR - Module 3: SCH Tables
USE `openemr`;

CREATE TABLE IF NOT EXISTS `hms_resource` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `resource_code` VARCHAR(50) NOT NULL UNIQUE,
    `resource_name` VARCHAR(150) NOT NULL,
    `resource_type` ENUM('ROOM', 'THEATRE', 'EQUIPMENT', 'BED') NOT NULL DEFAULT 'ROOM',
    `location_id` BIGINT UNSIGNED NULL,
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

CREATE TABLE IF NOT EXISTS `hms_session_series` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pid` BIGINT NOT NULL,
    `resource_id` BIGINT UNSIGNED NULL,
    `provider_id` BIGINT NULL,
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

CREATE TABLE IF NOT EXISTS `hms_resource_booking` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `resource_id` BIGINT UNSIGNED NOT NULL,
    `pid` BIGINT NOT NULL,
    `encounter_id` BIGINT NULL,
    `series_id` BIGINT UNSIGNED NULL,
    `session_number` INT UNSIGNED NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `booking_status` ENUM('REQUESTED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW') NOT NULL DEFAULT 'CONFIRMED',
    `purpose` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `charge_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `charge_posted` TINYINT(1) NOT NULL DEFAULT 0,
    `booked_by` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bk_res_time` (`resource_id`, `start_time`, `end_time`),
    INDEX `idx_bk_pid` (`pid`),
    INDEX `idx_bk_series` (`series_id`),
    INDEX `idx_bk_status` (`booking_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
