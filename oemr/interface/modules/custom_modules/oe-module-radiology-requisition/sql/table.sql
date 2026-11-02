-- Radiology Requisition module tables. Safe to run more than once.

CREATE TABLE IF NOT EXISTS `radreq_order` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` BIGINT NOT NULL,
  `encounter` BIGINT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `patient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `mrn` VARCHAR(64) NOT NULL DEFAULT '',
  `dob` DATE NULL,
  `sex` VARCHAR(16) NOT NULL DEFAULT '',
  `encounter_ref` VARCHAR(64) NOT NULL DEFAULT '',
  `order_datetime` DATETIME NULL,
  `facility` VARCHAR(255) NOT NULL DEFAULT '',
  `contact` VARCHAR(64) NOT NULL DEFAULT '',
  `allergies` VARCHAR(255) NOT NULL DEFAULT '',
  `history` TEXT NULL,
  `indication` TEXT NULL,
  `diagnosis` VARCHAR(255) NOT NULL DEFAULT '',
  `priority` VARCHAR(24) NOT NULL DEFAULT 'Routine',
  `setting` VARCHAR(24) NOT NULL DEFAULT 'Outpatient',
  `additional` TEXT NULL,
  `clinician` VARCHAR(255) NOT NULL DEFAULT '',
  `provider_id` VARCHAR(64) NOT NULL DEFAULT '',
  `provider_contact` VARCHAR(64) NOT NULL DEFAULT '',
  `destination` VARCHAR(255) NOT NULL DEFAULT '',
  `created_by` BIGINT NULL,
  `finalized_by` BIGINT NULL,
  `finalized_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per requested study. safety_json holds {"question text": "unknown|no|yes|na"}.
CREATE TABLE IF NOT EXISTS `radreq_study` (
  `order_id` BIGINT UNSIGNED NOT NULL,
  `study_no` INT NOT NULL,
  `modality` VARCHAR(32) NOT NULL,
  `examination` VARCHAR(128) NOT NULL DEFAULT '',
  `laterality` VARCHAR(24) NOT NULL DEFAULT 'Not applicable',
  `contrast_protocol` VARCHAR(48) NOT NULL DEFAULT 'Not applicable',
  `instructions` VARCHAR(255) NOT NULL DEFAULT '',
  `other_examination` VARCHAR(255) NOT NULL DEFAULT '',
  `creatinine` VARCHAR(64) NOT NULL DEFAULT '',
  `egfr` VARCHAR(32) NOT NULL DEFAULT '',
  `renal_date` DATE NULL,
  `safety_notes` VARCHAR(255) NOT NULL DEFAULT '',
  `safety_json` TEXT NULL,
  PRIMARY KEY (`order_id`, `study_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `radreq_event` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT NULL,
  `action` VARCHAR(32) NOT NULL,
  `detail` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
