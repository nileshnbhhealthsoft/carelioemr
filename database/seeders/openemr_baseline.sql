-- Baseline OpenEMR Table Schema & phpGACL ACL Tables Dump
-- Version: OpenEMR Multi-Tenant Baseline

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `facility` varchar(255) DEFAULT 'Main Clinic',
  `authorized` varchar(10) DEFAULT '1',
  `info` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `users_secure`
CREATE TABLE IF NOT EXISTS `users_secure` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `gacl_aro` (Access Control Objects)
CREATE TABLE IF NOT EXISTS `gacl_aro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_value` varchar(150) NOT NULL DEFAULT 'users',
  `value` varchar(150) NOT NULL,
  `order_value` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `hidden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_aro` (`section_value`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `gacl_aro_groups` (Access Control Groups)
CREATE TABLE IF NOT EXISTS `gacl_aro_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `value` varchar(150) NOT NULL,
  `order_value` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gacl_value_aro_groups` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default OpenEMR ACL Groups (No Super Admin for Tenant)
INSERT IGNORE INTO `gacl_aro_groups` (`id`, `parent_id`, `name`, `value`, `order_value`) VALUES
(1, 0, 'Clinicians', 'clinicians', 1),
(2, 0, 'Physicians', 'physicians', 2),
(3, 0, 'Practice Managers', 'practice_managers', 3),
(4, 0, 'Front Desk Staff', 'front_desk', 4);

-- Table structure for `gacl_aro_groups_map`
CREATE TABLE IF NOT EXISTS `gacl_aro_groups_map` (
  `acl_id` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `aro_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`,`aro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `gacl_acl` (Access Control List Rules)
CREATE TABLE IF NOT EXISTS `gacl_acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_value` varchar(150) NOT NULL DEFAULT 'system',
  `allow` int(11) NOT NULL DEFAULT 1,
  `enabled` int(11) NOT NULL DEFAULT 1,
  `return_value` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Restrictive ACL Permission Rules (Restricting Admin Menu, Global Settings, System Logs)
INSERT IGNORE INTO `gacl_acl` (`id`, `section_value`, `allow`, `enabled`, `note`) VALUES
(1, 'patients', 1, 1, 'Access Patient Data & SOAP Charting'),
(2, 'encounters', 1, 1, 'Access Encounters & Vitals'),
(3, 'prescriptions', 1, 1, 'Access e-Prescriptions'),
(4, 'appointments', 1, 1, 'Access Calendar & Appointments'),
(100, 'admin_menu', 0, 0, 'RESTRICTED: Admin Menu Access Disabled for Tenant'),
(101, 'global_settings', 0, 0, 'RESTRICTED: System Configuration Disabled for Tenant'),
(102, 'system_logs', 0, 0, 'RESTRICTED: System Audit Logs Disabled for Tenant');

-- Table structure for `facility`
CREATE TABLE IF NOT EXISTS `facility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `globals` (System Configuration Values)
CREATE TABLE IF NOT EXISTS `globals` (
  `gl_name` varchar(255) NOT NULL,
  `gl_index` int(11) NOT NULL DEFAULT 0,
  `gl_value` text DEFAULT NULL,
  PRIMARY KEY (`gl_name`,`gl_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `globals` (`gl_name`, `gl_index`, `gl_value`) VALUES
('openemr_name', 0, 'AuraEMR Tenant Workstation'),
('rest_api', 0, '1'),
('allow_super_admin_escalation', 0, '0');

SET FOREIGN_KEY_CHECKS=1;
