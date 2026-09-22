-- CarelioEMR Site Admin Config Module Complete Database Seeder & Schema
-- Single, canonical SQL script containing full module schema, branding, GACL permissions, and user configuration.

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

-- 4. Module Registration in OpenEMR core modules table
INSERT INTO `modules` (
  `mod_name`, `mod_directory`, `mod_parent`, `mod_type`, `mod_active`, 
  `mod_ui_name`, `mod_relative_link`, `mod_ui_order`, `mod_ui_active`, 
  `mod_description`, `mod_nick_name`, `mod_enc_menu`, `permissions_item_table`, 
  `directory`, `date`, `sql_run`, `type`, `sql_version`, `acl_version`
)
SELECT 
  'Site Admin Config', 'site_admin_config', '', '', 1,
  'Site Admin Config', 'interface/modules/custom_modules/site_admin_config/', 0, 0,
  'CarelioEMR Site Admin Config Module', 'SiteAdminConfig', '', '',
  '', NOW(), 1, 0, '1.0.0', ''
WHERE NOT EXISTS (SELECT 1 FROM `modules` WHERE `mod_directory` = 'site_admin_config');

-- 5. Ensure Site Administrator group exists under Physicians in GACL
INSERT INTO `gacl_aro_groups` (`id`, `parent_id`, `name`, `value`, `lft`, `rgt`)
SELECT 
  COALESCE((SELECT MAX(`id`) FROM `gacl_aro_groups`), 100) + 1,
  COALESCE((SELECT MAX(`id`) FROM `gacl_aro_groups` WHERE `value` = 'doc'), 13),
  'Site Administrator',
  'site_admin',
  0,
  0
WHERE NOT EXISTS (SELECT 1 FROM `gacl_aro_groups` WHERE `value` = 'site_admin');

-- 6. Ensure GACL ACL rule exists for Site Administrator
INSERT INTO `gacl_acl` (`id`, `section_value`, `allow`, `enabled`, `return_value`, `note`, `updated_date`)
SELECT 
  COALESCE((SELECT MAX(`id`) FROM `gacl_acl`), 100) + 1,
  'system',
  1,
  1,
  'write',
  'Site Admin full practice and clinical permissions',
  UNIX_TIMESTAMP()
WHERE NOT EXISTS (
  SELECT 1 FROM `gacl_aro_groups_map` gm 
  JOIN `gacl_aro_groups` g ON gm.group_id = g.id 
  WHERE g.value = 'site_admin'
);

-- Link ACL to Site Administrator group
INSERT IGNORE INTO `gacl_aro_groups_map` (`acl_id`, `group_id`)
SELECT 
  (SELECT MAX(`id`) FROM `gacl_acl` WHERE `note` = 'Site Admin full practice and clinical permissions'),
  g.id
FROM `gacl_aro_groups` g
WHERE g.value = 'site_admin';

-- 7. Map Allowed ACO permissions to Site Administrator ACL
INSERT IGNORE INTO `gacl_aco_map` (`acl_id`, `section_value`, `value`)
SELECT 
  a.id AS acl_id,
  aco.section_value,
  aco.value
FROM `gacl_acl` a
JOIN `gacl_aro_groups_map` gm ON a.id = gm.acl_id
JOIN `gacl_aro_groups` g ON gm.group_id = g.id AND g.value = 'site_admin'
JOIN `gacl_aco` aco ON (
  (aco.section_value = 'admin' AND aco.value IN ('users', 'practice', 'superbill', 'calendar')) OR
  (aco.section_value = 'acct' AND aco.value IN ('bill', 'disc', 'eob', 'rep', 'rep_a')) OR
  (aco.section_value = 'encounters' AND aco.value IN ('auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed')) OR
  (aco.section_value = 'inventory' AND aco.value IN ('lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting')) OR
  (aco.section_value = 'lists' AND aco.value IN ('default', 'state', 'country', 'language', 'ethrace')) OR
  (aco.section_value = 'patients' AND aco.value IN ('appt', 'demo', 'med', 'trans', 'docs', 'docs_rm', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'pat_rep')) OR
  (aco.section_value = 'sensitivities' AND aco.value IN ('normal', 'high')) OR
  (aco.section_value = 'nationnotes' AND aco.value = 'nn_configure') OR
  (aco.section_value = 'patientportal' AND aco.value = 'portal') OR
  (aco.section_value = 'groups' AND aco.value IN ('gadd', 'gcalendar', 'glog', 'gdlog', 'gm'))
);

-- 8. Ensure forbidden ACOs are never assigned to Site Administrator
DELETE am FROM `gacl_aco_map` am
JOIN `gacl_aro_groups_map` gm ON am.acl_id = gm.acl_id
JOIN `gacl_aro_groups` g ON gm.group_id = g.id AND g.value = 'site_admin'
WHERE (am.section_value = 'admin' AND am.value IN ('super', 'forms', 'acl', 'manage_modules', 'database', 'language', 'menu', 'batchcom', 'drugs'))
   OR (am.section_value = 'menus' AND am.value = 'modle');

-- 9. Map all active authorized providers (non-admin) into Site Administrator group
INSERT IGNORE INTO `gacl_groups_aro_map` (`group_id`, `aro_id`)
SELECT 
  g.id AS group_id,
  a.id AS aro_id
FROM `gacl_aro_groups` g
JOIN `gacl_aro` a ON a.section_value = 'users'
JOIN `users` u ON u.username = a.value
WHERE g.value = 'site_admin'
  AND u.authorized = 1
  AND u.username != 'admin';

-- 10. Remove non-admin doctors from Administrator (superadmin) group if present
DELETE gm FROM `gacl_groups_aro_map` gm
JOIN `gacl_aro_groups` g ON gm.group_id = g.id AND g.value = 'admin'
JOIN `gacl_aro` a ON gm.aro_id = a.id AND a.section_value = 'users'
JOIN `users` u ON u.username = a.value
WHERE u.authorized = 1 AND u.username != 'admin';

-- 11. Update users main_menu_role to 'standard' for native menu isolation
UPDATE `users` 
SET `main_menu_role` = 'standard' 
WHERE `username` != 'admin' AND `authorized` = 1;
