-- Carelio Hospital EHR - Module 5: CPOE (Orders & Results)
-- Conforming to Specification Section 18.5 (Pages 39-40)

CREATE TABLE IF NOT EXISTS `hms_order_set` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `set_code` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `specialty` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `created_by` BIGINT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_os_code` (`set_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_order_set_item` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_set_id` BIGINT UNSIGNED NOT NULL,
    `item_type` ENUM('lab', 'imaging', 'medication', 'nursing') NOT NULL DEFAULT 'lab',
    `item_code` VARCHAR(64) NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `dosage_or_instructions` VARCHAR(255) NULL,
    `priority` ENUM('routine', 'urgent', 'stat') NOT NULL DEFAULT 'routine',
    `is_high_risk` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    INDEX `idx_osi_set` (`order_set_id`),
    CONSTRAINT `fk_osi_set` FOREIGN KEY (`order_set_id`) REFERENCES `hms_order_set`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_alert_rule` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_code` VARCHAR(100) NOT NULL UNIQUE,
    `test_code` VARCHAR(64) NOT NULL,
    `test_name` VARCHAR(150) NOT NULL,
    `panic_low` DECIMAL(10,3) NULL,
    `panic_high` DECIMAL(10,3) NULL,
    `units` VARCHAR(31) NULL,
    `escalation_minutes` INT NOT NULL DEFAULT 30,
    `escalate_to_user_id` BIGINT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ar_test` (`test_code`),
    INDEX `idx_ar_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_alert_ack` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_id` BIGINT UNSIGNED NULL,
    `procedure_result_id` BIGINT NULL,
    `pid` BIGINT NOT NULL,
    `encounter` BIGINT NULL,
    `test_name` VARCHAR(150) NOT NULL,
    `result_value` VARCHAR(100) NOT NULL,
    `severity` ENUM('critical', 'abnormal', 'post_discharge') NOT NULL DEFAULT 'critical',
    `target_user_id` BIGINT NOT NULL,
    `escalated_to_user_id` BIGINT NULL,
    `status` ENUM('unacknowledged', 'acknowledged', 'escalated') NOT NULL DEFAULT 'unacknowledged',
    `triggered_at` DATETIME NOT NULL,
    `due_by` DATETIME NOT NULL,
    `acknowledged_by` BIGINT NULL,
    `acknowledged_at` DATETIME NULL,
    `escalated_at` DATETIME NULL,
    `ack_notes` TEXT NULL,
    INDEX `idx_aa_status` (`status`),
    INDEX `idx_aa_target` (`target_user_id`),
    INDEX `idx_aa_due` (`due_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hms_cpoe_cosign_queue` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pid` BIGINT NOT NULL,
    `encounter` BIGINT NOT NULL,
    `order_type` ENUM('procedure', 'medication') NOT NULL DEFAULT 'procedure',
    `order_ref_id` BIGINT NULL,
    `order_summary` VARCHAR(255) NOT NULL,
    `ordered_by` BIGINT NOT NULL,
    `attending_id` BIGINT NULL,
    `status` ENUM('pending_cosign', 'cosigned', 'rejected') NOT NULL DEFAULT 'pending_cosign',
    `cosigned_by` BIGINT NULL,
    `cosigned_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cs_status` (`status`),
    INDEX `idx_cs_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
