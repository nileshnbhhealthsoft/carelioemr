CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(120) NOT NULL,
  `config_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_carelio_subscription_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mod_carelio_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_subscription_id` VARCHAR(191) NULL,
  `site_id` VARCHAR(191) NULL,
  `site_name` VARCHAR(191) NULL,
  `tenant_slug` VARCHAR(191) NULL,
  `customer_name` VARCHAR(191) NULL,
  `customer_email` VARCHAR(191) NULL,
  `plan_name` VARCHAR(120) NULL,
  `plan_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',
  `status` VARCHAR(40) NOT NULL DEFAULT 'ACTIVE',
  `current_period_start` DATETIME NULL,
  `current_period_end` DATETIME NULL,
  `expiration_reminder_sent_at` DATETIME NULL,
  `grace_started_at` DATETIME NULL,
  `grace_ends_at` DATETIME NULL,
  `deactivated_at` DATETIME NULL,
  `renewed_at` DATETIME NULL,
  `reactivated_at` DATETIME NULL,
  `last_synced_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_carelio_subscription_external` (`external_subscription_id`),
  KEY `idx_carelio_subscription_site` (`site_id`),
  KEY `idx_carelio_subscription_tenant` (`tenant_slug`),
  KEY `idx_carelio_subscription_status` (`status`),
  KEY `idx_carelio_subscription_period_end` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_events` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `subscription_id` INT NULL,
  `event_type` VARCHAR(120) NOT NULL,
  `event_payload` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_carelio_subscription_events_subscription` (`subscription_id`),
  KEY `idx_carelio_subscription_events_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_history` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `subscription_id` INT NULL,
  `action` VARCHAR(120) NOT NULL,
  `old_status` VARCHAR(40) NULL,
  `new_status` VARCHAR(40) NULL,
  `old_period_end` DATETIME NULL,
  `new_period_end` DATETIME NULL,
  `performed_by` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_carelio_subscription_history_subscription` (`subscription_id`),
  KEY `idx_carelio_subscription_history_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mod_carelio_subscription_config` (`config_key`, `config_value`)
VALUES
  ('module_version', '1.0.0'),
  ('module_enabled_at', NOW()),
  ('expiration_reminder_days', '2'),
  ('grace_period_days', '2'),
  ('default_status', 'ACTIVE')
ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`);
