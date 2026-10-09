-- Claim Forms module tables. Safe to run more than once.

CREATE TABLE IF NOT EXISTS `claimforms_claim` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` BIGINT NOT NULL,
  `form_id` VARCHAR(64) NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'draft',
  `payload_json` MEDIUMTEXT NOT NULL,
  `total_cents` INT NOT NULL DEFAULT 0,
  `provider_user_id` BIGINT NULL,
  `created_by` BIGINT NULL,
  `revision` INT NOT NULL DEFAULT 0,
  `pdf_path` VARCHAR(255) NULL,
  `document_id` BIGINT NULL,
  `generated_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_form` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `claimforms_claim_encounter` (
  `claim_id` BIGINT UNSIGNED NOT NULL,
  `encounter` BIGINT NOT NULL,
  PRIMARY KEY (`claim_id`, `encounter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `claimforms_claim_line` (
  `claim_id` BIGINT UNSIGNED NOT NULL,
  `line_no` INT NOT NULL,
  `service_date` DATE NULL,
  `place_of_service` VARCHAR(16) NULL,
  `description` VARCHAR(255) NULL,
  `dx_pointer` TINYINT NULL,
  `amount_cents` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`claim_id`, `line_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `claimforms_event` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `claim_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT NULL,
  `action` VARCHAR(32) NOT NULL,
  `detail` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_claim` (`claim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
