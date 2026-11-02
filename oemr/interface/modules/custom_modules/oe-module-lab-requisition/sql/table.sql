-- Lab Requisition module tables. Safe to run more than once.

CREATE TABLE IF NOT EXISTS `labreq_order` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` BIGINT NOT NULL,
  `encounter` BIGINT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `is_urgent` TINYINT(1) NOT NULL DEFAULT 0,
  `is_stat` TINYINT(1) NOT NULL DEFAULT 0,
  `patient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `dob` VARCHAR(20) NOT NULL DEFAULT '',
  `sex` VARCHAR(20) NOT NULL DEFAULT '',
  `reg_no` VARCHAR(64) NOT NULL DEFAULT '',
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `doctor` VARCHAR(255) NOT NULL DEFAULT '',
  `contact` VARCHAR(64) NOT NULL DEFAULT '',
  `order_date` VARCHAR(20) NOT NULL DEFAULT '',
  `specimen_line1` VARCHAR(255) NOT NULL DEFAULT '',
  `specimen_line2` VARCHAR(255) NOT NULL DEFAULT '',
  `signed` VARCHAR(255) NOT NULL DEFAULT '',
  `clinical_details` TEXT NULL,
  `histology_site` VARCHAR(255) NOT NULL DEFAULT '',
  `created_by` BIGINT NULL,
  `finalized_by` BIGINT NULL,
  `finalized_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per ticked box. kind is 'specimen' or 'test'; detail holds the "state" text where the paper form has a line.
CREATE TABLE IF NOT EXISTS `labreq_order_item` (
  `order_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(16) NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `detail` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`order_id`, `kind`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `labreq_event` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT NULL,
  `action` VARCHAR(32) NOT NULL,
  `detail` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
