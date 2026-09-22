-- CarelioEMR Site Admin Config Module Database Schema & Native Defaults

-- 1. Module Tracking Table
CREATE TABLE IF NOT EXISTS `mod_site_admin_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `installed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Native CarelioEMR Branding in globals
INSERT INTO `globals` (`gl_name`, `gl_value`) VALUES
  ('openemr_name', 'CarelioEMR'),
  ('login_tagline_text', 'CarelioEMR - Connected Data. Better Care.'),
  ('show_tagline_on_login', '1'),
  ('main_menu_logo_title', 'CarelioEMR'),
  ('display_main_menu_logo', '1'),
  ('show_primary_logo', '1'),
  ('primary_logo_width', 'w-100'),
  ('login_page_layout', 'login/layouts/vertical_band.html.twig'),
  ('portal_custom_title', 'CarelioEMR Patient Portal')
ON DUPLICATE KEY UPDATE `gl_value` = VALUES(`gl_value`);

-- 3. Automatic Migration for Legacy test Module Record if present
UPDATE `modules` SET 
  `mod_name` = 'Site Admin Config',
  `mod_directory` = 'site_admin_config',
  `mod_ui_name` = 'Site Admin Config',
  `mod_relative_link` = 'interface/modules/custom_modules/site_admin_config/',
  `mod_description` = 'CarelioEMR Site Admin Config Module',
  `mod_nick_name` = 'SiteAdminConfig',
  `mod_active` = 1,
  `type` = 0,
  `sql_run` = 1
WHERE `mod_directory` = 'test';
