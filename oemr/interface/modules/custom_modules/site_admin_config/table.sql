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

-- -------------------------------------------------------------------------
-- CREATE "SITE ADMINISTRATOR" ACL GROUP & PERMISSIONS
-- -------------------------------------------------------------------------

SET @group_name  = 'Site Administrator';
SET @group_value = 'site_admin';
SET @return_val  = 'write';
SET @acl_note    = 'Site Admin full practice and clinical permissions';

-- Find Parent Group (root 'users' group) and right boundary
SELECT @parent_id := id, @parent_rgt := rgt 
FROM `gacl_aro_groups` 
WHERE `value` = 'users' OR `parent_id` = 0 
ORDER BY `parent_id` ASC 
LIMIT 1;

-- Check if group already exists, or calculate next ID
SELECT @site_admin_group_id := id FROM `gacl_aro_groups` WHERE `value` = @group_value LIMIT 1;

SET @site_admin_group_id = IFNULL(@site_admin_group_id, (SELECT IFNULL(MAX(`id`), 0) + 1 FROM `gacl_aro_groups`));

-- Update group sequence
UPDATE `gacl_aro_groups_id_seq` 
SET `id` = @site_admin_group_id 
WHERE @site_admin_group_id > (SELECT IFNULL(MAX(`id`), 0) FROM `gacl_aro_groups_id_seq`);

-- Shift nested set boundaries only if inserting a new group
UPDATE `gacl_aro_groups` 
SET `rgt` = `rgt` + 2 
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM `gacl_aro_groups` WHERE `value` = @group_value) t) 
  AND `rgt` >= @parent_rgt;

UPDATE `gacl_aro_groups` 
SET `lft` = `lft` + 2 
WHERE NOT EXISTS (SELECT 1 FROM (SELECT id FROM `gacl_aro_groups` WHERE `value` = @group_value) t) 
  AND `lft` > @parent_rgt;

-- Insert the ARO group with valid tree coordinates
INSERT INTO `gacl_aro_groups` (`id`, `parent_id`, `name`, `value`, `lft`, `rgt`)
SELECT @site_admin_group_id, @parent_id, @group_name, @group_value, @parent_rgt, @parent_rgt + 1
WHERE NOT EXISTS (SELECT 1 FROM `gacl_aro_groups` WHERE `value` = @group_value);

-- Fetch confirmed group ID
SELECT @site_admin_group_id := id FROM `gacl_aro_groups` WHERE `value` = @group_value LIMIT 1;

-- Create ACL Rule entry in gacl_acl
SELECT @acl_id := a.id 
FROM `gacl_acl` a 
JOIN `gacl_aro_groups_map` gm ON a.id = gm.acl_id 
WHERE gm.group_id = @site_admin_group_id LIMIT 1;

SET @new_acl_id = IFNULL(@acl_id, (SELECT IFNULL(MAX(`id`), 0) + 1 FROM `gacl_acl`));

UPDATE `gacl_acl_seq` 
SET `id` = @new_acl_id 
WHERE @new_acl_id > (SELECT IFNULL(MAX(`id`), 0) FROM `gacl_acl_seq`);

INSERT INTO `gacl_acl` (`id`, `section_value`, `allow`, `enabled`, `return_value`, `note`, `updated_date`)
SELECT @new_acl_id, 'system', 1, 1, @return_val, @acl_note, UNIX_TIMESTAMP()
WHERE NOT EXISTS (
  SELECT 1 FROM `gacl_aro_groups_map` gm 
  WHERE gm.group_id = @site_admin_group_id
);

-- Link ACL rule to the Site Administrator group
INSERT IGNORE INTO `gacl_aro_groups_map` (`acl_id`, `group_id`)
VALUES (@new_acl_id, @site_admin_group_id);

-- Map allowed ACO permissions to the ACL
INSERT IGNORE INTO `gacl_aco_map` (`acl_id`, `section_value`, `value`)
SELECT 
  @new_acl_id,
  aco.section_value,
  aco.value
FROM `gacl_aco` aco
WHERE 
  (aco.section_value = 'admin' AND aco.value IN ('users', 'practice', 'superbill', 'calendar')) OR
  (aco.section_value = 'acct' AND aco.value IN ('bill', 'disc', 'eob', 'rep', 'rep_a')) OR
  (aco.section_value = 'encounters' AND aco.value IN ('auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed')) OR
  (aco.section_value = 'inventory' AND aco.value IN ('lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting')) OR
  (aco.section_value = 'lists' AND aco.value IN ('default', 'state', 'country', 'language', 'ethrace')) OR
  (aco.section_value = 'patients' AND aco.value IN ('appt', 'demo', 'med', 'trans', 'docs', 'docs_rm', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'pat_rep')) OR
  (aco.section_value = 'sensitivities' AND aco.value IN ('normal', 'high')) OR
  (aco.section_value = 'nationnotes' AND aco.value = 'nn_configure') OR
  (aco.section_value = 'patientportal' AND aco.value = 'portal') OR
  (aco.section_value = 'groups' AND aco.value IN ('gadd', 'gcalendar', 'glog', 'gdlog', 'gm'));

-- -------------------------------------------------------------------------
-- CREATE USER "siteadmin" & MAP TO SITE ADMINISTRATOR
-- -------------------------------------------------------------------------

SET @new_username = 'siteadmin';
SET @first_name   = 'Site';
SET @last_name    = 'Administrator';
-- Bcrypt hash for password: siteadmin@220926
SET @pwd_hash     = '$2y$10$5zKBO.yABp4GOzT.34hi2.8z2m9OG4b8ADHtInSrzACQmQXia8wOO';

-- 1. Insert User into `users` table
INSERT INTO `users` (
  `uuid`, `username`, `fname`, `lname`, `authorized`, 
  `facility_id`, `see_auth`, `active`, `cal_ui`, 
  `main_menu_role`, `patient_menu_role`, `date_created`
) 
SELECT 
  UNHEX(REPLACE(UUID(), '-', '')), 
  @new_username, 
  @first_name, 
  @last_name, 
  1, 
  IFNULL((SELECT id FROM `facility` ORDER BY `primary_business_entity` DESC, `id` ASC LIMIT 1), 1),
  1, 
  1, 
  1, 
  'standard', 
  'standard', 
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = @new_username);

SELECT @new_user_id := id FROM `users` WHERE `username` = @new_username LIMIT 1;

-- 2. Insert/Update Authentication credentials in `users_secure`
INSERT INTO `users_secure` (
  `id`, `username`, `password`, `last_update_password`, 
  `login_fail_counter`, `total_login_fail_counter`, `auto_block_emailed`
)
VALUES (@new_user_id, @new_username, @pwd_hash, NOW(), 0, 0, 0)
ON DUPLICATE KEY UPDATE 
  `password` = @pwd_hash, 
  `login_fail_counter` = 0, 
  `total_login_fail_counter` = 0, 
  `last_login_fail` = NULL, 
  `auto_block_emailed` = 0, 
  `last_update_password` = NOW();

-- 3. Insert into OpenEMR `groups` table (Required for login authentication)
INSERT INTO `groups` (`name`, `user`)
SELECT 'Default', @new_username
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `user` = @new_username);

-- 4. Create User Access Request Object (ARO) in `gacl_aro`
SELECT @user_aro_id := id FROM `gacl_aro` WHERE `section_value` = 'users' AND `value` = @new_username LIMIT 1;

SET @new_aro_id = IFNULL(@user_aro_id, (SELECT IFNULL(MAX(`id`), 0) + 1 FROM `gacl_aro`));

UPDATE `gacl_aro_seq` 
SET `id` = @new_aro_id 
WHERE @new_aro_id > (SELECT IFNULL(MAX(`id`), 0) FROM `gacl_aro_seq`);

INSERT INTO `gacl_aro` (`id`, `section_value`, `value`, `order_value`, `name`, `hidden`)
SELECT @new_aro_id, 'users', @new_username, 10, CONCAT(@first_name, ' ', @last_name), 0
WHERE NOT EXISTS (SELECT 1 FROM `gacl_aro` WHERE `section_value` = 'users' AND `value` = @new_username);

SELECT @confirmed_aro_id := id FROM `gacl_aro` WHERE `section_value` = 'users' AND `value` = @new_username LIMIT 1;

-- 5. Map the User to the "Site Administrator" (site_admin) Group
INSERT IGNORE INTO `gacl_groups_aro_map` (`group_id`, `aro_id`)
VALUES (@site_admin_group_id, @confirmed_aro_id);


-- ============================================================================
-- 12. Default Open Tabs (Calendar & Message Center)
-- ============================================================================
UPDATE `list_options` SET `activity` = 1 WHERE `list_id` = 'default_open_tabs' AND `option_id` IN ('cal', 'msg');
UPDATE `list_options` SET `activity` = 0 WHERE `list_id` = 'default_open_tabs' AND `option_id` NOT IN ('cal', 'msg');

-- ============================================================================
-- 13. Spanish Language Support
-- ============================================================================
INSERT IGNORE INTO `lang_languages` (`lang_id`, `lang_code`, `lang_description`, `lang_is_rtl`) VALUES (2, 'es', 'Spanish (Latin American)', 0);

-- ============================================================================
-- 14. Hide Unused Demographic Fields (uor = 0 : Hidden)
-- ============================================================================
UPDATE `layout_options`
SET `uor` = 0
WHERE `form_id` = 'DEM'
  AND `field_id` IN (
    'ssn', 'ss', 'drivers_license', 'mothers_name', 'guardiansname',
    'usertext1', 'usertext2', 'usertext3', 'usertext4',
    'userdate1', 'userdate2', 'userssn1', 'userssn2',
    'pubpid', 'referral_source', 'tribal_affiliate', 'ethnic_group'
  );

-- ============================================================================
-- 15. Demographics Location Fields Layout Ordering & Types
-- ============================================================================
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'country', `uor` = 1, `seq` = 4, `title` = 'Country' WHERE `form_id` = 'DEM' AND `field_id` = 'country_code';
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'country', `uor` = 1, `seq` = 4, `title` = 'Country' WHERE `form_id` = 'DEM' AND `field_id` = 'country';
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'state', `uor` = 1, `seq` = 5, `title` = 'State' WHERE `form_id` = 'DEM' AND `field_id` = 'state';
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'county', `uor` = 1, `seq` = 6, `title` = 'County' WHERE `form_id` = 'DEM' AND `field_id` = 'county';
UPDATE `layout_options` SET `seq` = 7 WHERE `form_id` = 'DEM' AND `field_id` = 'postal_code';

-- ============================================================================
-- 16. Location Lists (Country, State, County) Options Data
-- ============================================================================
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('country', 'LC', 'Saint Lucia', 10, 0, 0, 'CB', '', '', 1),
  ('country', 'BS', 'Bahamas', 11, 0, 0, 'CB', '', '', 1),
  ('country', 'BB', 'Barbados', 12, 0, 0, 'CB', '', '', 1),
  ('country', 'JM', 'Jamaica', 13, 0, 0, 'CB', '', '', 1),
  ('country', 'BM', 'Bermuda', 14, 0, 0, 'CB', '', '', 1),
  ('country', 'DM', 'Dominica', 15, 0, 0, 'CB', '', '', 1),
  ('country', 'TT', 'Trinidad and Tobago', 16, 0, 0, 'CB', '', '', 1),
  ('country', 'AG', 'Antigua and Barbuda', 17, 0, 0, 'CB', '', '', 1),
  ('country', 'KN', 'Saint Kitts and Nevis', 18, 0, 0, 'CB', '', '', 1),
  ('country', 'VC', 'Saint Vincent & Grenadines', 19, 0, 0, 'CB', '', '', 1),
  ('country', 'GD', 'Grenada', 20, 0, 0, 'CB', '', '', 1),
  ('country', 'KY', 'Cayman Islands', 21, 0, 0, 'CB', '', '', 1),
  ('country', 'US', 'United States', 1, 0, 0, 'US', '', '', 1),
  ('country', 'USA', 'United States of America', 2, 0, 0, 'US', '', '', 1),
  ('country', 'IN', 'India', 30, 0, 0, 'IN', '', '', 1),
  ('country', 'AE', 'United Arab Emirates', 40, 0, 0, 'AE', '', '', 1),
  ('country', 'GB', 'United Kingdom', 50, 0, 0, 'EU', '', '', 1),
  ('country', 'IE', 'Ireland', 51, 0, 0, 'EU', '', '', 1),
  ('country', 'DE', 'Germany', 52, 0, 0, 'EU', '', '', 1),
  ('country', 'FR', 'France', 53, 0, 0, 'EU', '', '', 1),
  ('country', 'NL', 'Netherlands', 54, 0, 0, 'EU', '', '', 1),
  ('country', 'ES', 'Spain', 55, 0, 0, 'EU', '', '', 1),
  ('country', 'IT', 'Italy', 56, 0, 0, 'EU', '', '', 1),
  ('country', 'CH', 'Switzerland', 57, 0, 0, 'EU', '', '', 1),
  ('country', 'BE', 'Belgium', 58, 0, 0, 'EU', '', '', 1),
  ('country', 'SE', 'Sweden', 59, 0, 0, 'EU', '', '', 1),
  ('country', 'ZA', 'South Africa', 70, 0, 0, 'AF', '', '', 1),
  ('country', 'NG', 'Nigeria', 71, 0, 0, 'AF', '', '', 1),
  ('country', 'KE', 'Kenya', 72, 0, 0, 'AF', '', '', 1),
  ('country', 'GH', 'Ghana', 73, 0, 0, 'AF', '', '', 1),
  ('country', 'EG', 'Egypt', 74, 0, 0, 'AF', '', '', 1),
  ('country', 'AU', 'Australia', 80, 0, 0, 'AU', '', '', 1),
  ('state', 'LC_CAS', 'Castries', 1, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_GRO', 'Gros Islet', 2, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_VIF', 'Vieux Fort', 3, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_SOU', 'Soufrière', 4, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_DEN', 'Dennery', 5, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_MIC', 'Micoud', 6, 0, 0, 'LC', '', '', 1),
  ('state', 'JM_KIN', 'Kingston', 10, 0, 0, 'JM', '', '', 1),
  ('state', 'JM_AND', 'St. Andrew', 11, 0, 0, 'JM', '', '', 1),
  ('state', 'JM_CAT', 'St. Catherine', 12, 0, 0, 'JM', '', '', 1),
  ('state', 'JM_JAM', 'St. James (Montego Bay)', 13, 0, 0, 'JM', '', '', 1),
  ('state', 'BB_MIC', 'St. Michael (Bridgetown)', 20, 0, 0, 'BB', '', '', 1),
  ('state', 'BB_CHR', 'Christ Church', 21, 0, 0, 'BB', '', '', 1),
  ('state', 'BB_JAM', 'St. James', 22, 0, 0, 'BB', '', '', 1),
  ('state', 'BS_NP', 'New Providence (Nassau)', 30, 0, 0, 'BS', '', '', 1),
  ('state', 'BS_GB', 'Grand Bahama (Freeport)', 31, 0, 0, 'BS', '', '', 1),
  ('state', 'TT_POS', 'Port of Spain', 40, 0, 0, 'TT', '', '', 1),
  ('state', 'TT_SFO', 'San Fernando', 41, 0, 0, 'TT', '', '', 1),
  ('state', 'TT_TOB', 'Tobago', 42, 0, 0, 'TT', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `activity` = VALUES(`activity`);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('state', 'AL', 'Alabama', 101, 0, 0, 'US', '', '', 1),
  ('state', 'AK', 'Alaska', 102, 0, 0, 'US', '', '', 1),
  ('state', 'AZ', 'Arizona', 103, 0, 0, 'US', '', '', 1),
  ('state', 'AR', 'Arkansas', 104, 0, 0, 'US', '', '', 1),
  ('state', 'CA', 'California', 105, 0, 0, 'US', '', '', 1),
  ('state', 'CO', 'Colorado', 106, 0, 0, 'US', '', '', 1),
  ('state', 'CT', 'Connecticut', 107, 0, 0, 'US', '', '', 1),
  ('state', 'DE', 'Delaware', 108, 0, 0, 'US', '', '', 1),
  ('state', 'FL', 'Florida', 109, 0, 0, 'US', '', '', 1),
  ('state', 'GA', 'Georgia', 110, 0, 0, 'US', '', '', 1),
  ('state', 'HI', 'Hawaii', 111, 0, 0, 'US', '', '', 1),
  ('state', 'ID', 'Idaho', 112, 0, 0, 'US', '', '', 1),
  ('state', 'IL', 'Illinois', 113, 0, 0, 'US', '', '', 1),
  ('state', 'IN_US', 'Indiana (US)', 114, 0, 0, 'US', '', '', 1),
  ('state', 'IA', 'Iowa', 115, 0, 0, 'US', '', '', 1),
  ('state', 'KS', 'Kansas', 116, 0, 0, 'US', '', '', 1),
  ('state', 'KY_US', 'Kentucky', 117, 0, 0, 'US', '', '', 1),
  ('state', 'LA', 'Louisiana', 118, 0, 0, 'US', '', '', 1),
  ('state', 'ME', 'Maine', 119, 0, 0, 'US', '', '', 1),
  ('state', 'MD', 'Maryland', 120, 0, 0, 'US', '', '', 1),
  ('state', 'MA', 'Massachusetts', 121, 0, 0, 'US', '', '', 1),
  ('state', 'MI', 'Michigan', 122, 0, 0, 'US', '', '', 1),
  ('state', 'MN', 'Minnesota', 123, 0, 0, 'US', '', '', 1),
  ('state', 'MS', 'Mississippi', 124, 0, 0, 'US', '', '', 1),
  ('state', 'MO', 'Missouri', 125, 0, 0, 'US', '', '', 1),
  ('state', 'MT', 'Montana', 126, 0, 0, 'US', '', '', 1),
  ('state', 'NE', 'Nebraska', 127, 0, 0, 'US', '', '', 1),
  ('state', 'NV', 'Nevada', 128, 0, 0, 'US', '', '', 1),
  ('state', 'NH', 'New Hampshire', 129, 0, 0, 'US', '', '', 1),
  ('state', 'NJ', 'New Jersey', 130, 0, 0, 'US', '', '', 1),
  ('state', 'NM', 'New Mexico', 131, 0, 0, 'US', '', '', 1),
  ('state', 'NY', 'New York', 132, 0, 0, 'US', '', '', 1),
  ('state', 'NC', 'North Carolina', 133, 0, 0, 'US', '', '', 1),
  ('state', 'ND', 'North Dakota', 134, 0, 0, 'US', '', '', 1),
  ('state', 'OH', 'Ohio', 135, 0, 0, 'US', '', '', 1),
  ('state', 'OK', 'Oklahoma', 136, 0, 0, 'US', '', '', 1),
  ('state', 'OR', 'Oregon', 137, 0, 0, 'US', '', '', 1),
  ('state', 'PA', 'Pennsylvania', 138, 0, 0, 'US', '', '', 1),
  ('state', 'RI', 'Rhode Island', 139, 0, 0, 'US', '', '', 1),
  ('state', 'SC', 'South Carolina', 140, 0, 0, 'US', '', '', 1),
  ('state', 'SD', 'South Dakota', 141, 0, 0, 'US', '', '', 1),
  ('state', 'TN', 'Tennessee', 142, 0, 0, 'US', '', '', 1),
  ('state', 'TX', 'Texas', 143, 0, 0, 'US', '', '', 1),
  ('state', 'UT', 'Utah', 144, 0, 0, 'US', '', '', 1),
  ('state', 'VT', 'Vermont', 145, 0, 0, 'US', '', '', 1),
  ('state', 'VA', 'Virginia', 146, 0, 0, 'US', '', '', 1),
  ('state', 'WA', 'Washington', 147, 0, 0, 'US', '', '', 1),
  ('state', 'WV', 'West Virginia', 148, 0, 0, 'US', '', '', 1),
  ('state', 'WI', 'Wisconsin', 149, 0, 0, 'US', '', '', 1),
  ('state', 'WY', 'Wyoming', 150, 0, 0, 'US', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `activity` = VALUES(`activity`);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('state', 'DC', 'District of Columbia', 151, 0, 0, 'US', '', '', 1),
  ('state', 'PR', 'Puerto Rico', 152, 0, 0, 'US', '', '', 1),
  ('state', 'IN_MH', 'Maharashtra', 201, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_DL', 'Delhi (NCT)', 202, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_KA', 'Karnataka', 203, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_TN', 'Tamil Nadu', 204, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_TG', 'Telangana', 205, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_GJ', 'Gujarat', 206, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_UP', 'Uttar Pradesh', 207, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_WB', 'West Bengal', 208, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_KL', 'Kerala', 209, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_RJ', 'Rajasthan', 210, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_AP', 'Andhra Pradesh', 211, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_MP', 'Madhya Pradesh', 212, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_PB', 'Punjab', 213, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_HR', 'Haryana', 214, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_BR', 'Bihar', 215, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_OD', 'Odisha', 216, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_AS', 'Assam', 217, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_JK', 'Jammu & Kashmir', 218, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_GA', 'Goa', 219, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_UT', 'Uttarakhand', 220, 0, 0, 'IN', '', '', 1),
  ('state', 'IN_CH', 'Chandigarh', 221, 0, 0, 'IN', '', '', 1),
  ('state', 'AE_DXB', 'Dubai', 301, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_AUH', 'Abu Dhabi', 302, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_SHJ', 'Sharjah', 303, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_AJM', 'Ajman', 304, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_RAK', 'Ras Al Khaimah', 305, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_FUJ', 'Fujairah', 306, 0, 0, 'AE', '', '', 1),
  ('state', 'AE_UAQ', 'Umm Al Quwain', 307, 0, 0, 'AE', '', '', 1),
  ('state', 'GB_ENG', 'England', 401, 0, 0, 'GB', '', '', 1),
  ('state', 'GB_SCT', 'Scotland', 402, 0, 0, 'GB', '', '', 1),
  ('state', 'GB_WLS', 'Wales', 403, 0, 0, 'GB', '', '', 1),
  ('state', 'GB_NIR', 'Northern Ireland', 404, 0, 0, 'GB', '', '', 1),
  ('state', 'IE_LEI', 'Leinster (Dublin)', 410, 0, 0, 'IE', '', '', 1),
  ('state', 'IE_MUN', 'Munster (Cork)', 411, 0, 0, 'IE', '', '', 1),
  ('state', 'IE_CON', 'Connacht (Galway)', 412, 0, 0, 'IE', '', '', 1),
  ('state', 'IE_ULS', 'Ulster', 413, 0, 0, 'IE', '', '', 1),
  ('state', 'DE_BY', 'Bavaria (Bayern)', 420, 0, 0, 'DE', '', '', 1),
  ('state', 'DE_BE', 'Berlin', 421, 0, 0, 'DE', '', '', 1),
  ('state', 'DE_NW', 'North Rhine-Westphalia', 422, 0, 0, 'DE', '', '', 1),
  ('state', 'DE_BW', 'Baden-Württemberg', 423, 0, 0, 'DE', '', '', 1),
  ('state', 'DE_HE', 'Hesse (Frankfurt)', 424, 0, 0, 'DE', '', '', 1),
  ('state', 'FR_IDF', 'Île-de-France (Paris)', 430, 0, 0, 'FR', '', '', 1),
  ('state', 'FR_ARA', 'Auvergne-Rhône-Alpes', 431, 0, 0, 'FR', '', '', 1),
  ('state', 'FR_PAC', 'Provence-Alpes-Côte d\'Azur', 432, 0, 0, 'FR', '', '', 1),
  ('state', 'ES_MD', 'Community of Madrid', 440, 0, 0, 'ES', '', '', 1),
  ('state', 'ES_CT', 'Catalonia (Barcelona)', 441, 0, 0, 'ES', '', '', 1),
  ('state', 'ES_AN', 'Andalusia', 442, 0, 0, 'ES', '', '', 1),
  ('state', 'ZA_GP', 'Gauteng (Johannesburg/Pretoria)', 501, 0, 0, 'ZA', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `activity` = VALUES(`activity`);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  -- United States Major Counties
  ('county', 'FL_MIAMI_DADE', 'Miami-Dade County', 30, 0, 0, 'FL', '', '', 1),
  ('county', 'FL_BROWARD', 'Broward County', 31, 0, 0, 'FL', '', '', 1),
  ('county', 'FL_PALM_BEACH', 'Palm Beach County', 32, 0, 0, 'FL', '', '', 1),
  ('county', 'FL_ORANGE', 'Orange County', 33, 0, 0, 'FL', '', '', 1),
  ('county', 'FL_HILLSBOROUGH', 'Hillsborough County', 34, 0, 0, 'FL', '', '', 1),
  ('county', 'CA_LOS_ANGELES', 'Los Angeles County', 40, 0, 0, 'CA', '', '', 1),
  ('county', 'CA_ORANGE', 'Orange County', 41, 0, 0, 'CA', '', '', 1),
  ('county', 'CA_SAN_DIEGO', 'San Diego County', 42, 0, 0, 'CA', '', '', 1),
  ('county', 'CA_SANTA_CLARA', 'Santa Clara County', 43, 0, 0, 'CA', '', '', 1),
  ('county', 'CA_SAN_FRANCISCO', 'San Francisco County', 44, 0, 0, 'CA', '', '', 1),
  ('county', 'NY_NEW_YORK', 'New York County (Manhattan)', 50, 0, 0, 'NY', '', '', 1),
  ('county', 'NY_KINGS', 'Kings County (Brooklyn)', 51, 0, 0, 'NY', '', '', 1),
  ('county', 'NY_QUEENS', 'Queens County', 52, 0, 0, 'NY', '', '', 1),
  ('county', 'NY_BRONX', 'Bronx County', 53, 0, 0, 'NY', '', '', 1),
  ('county', 'NY_NASSAU', 'Nassau County', 54, 0, 0, 'NY', '', '', 1),
  ('county', 'TX_HARRIS', 'Harris County (Houston)', 60, 0, 0, 'TX', '', '', 1),
  ('county', 'TX_DALLAS', 'Dallas County', 61, 0, 0, 'TX', '', '', 1),
  ('county', 'TX_TARRANT', 'Tarrant County (Fort Worth)', 62, 0, 0, 'TX', '', '', 1),
  ('county', 'TX_TRAVIS', 'Travis County (Austin)', 63, 0, 0, 'TX', '', '', 1),
  ('county', 'TX_BEXAR', 'Bexar County (San Antonio)', 64, 0, 0, 'TX', '', '', 1),
  -- International Districts
  ('county', 'IN_MUMBAI', 'Mumbai District', 70, 0, 0, 'IN_MH', '', '', 1),
  ('county', 'IN_PUNE', 'Pune District', 71, 0, 0, 'IN_MH', '', '', 1),
  ('county', 'IN_NAGPUR', 'Nagpur District', 72, 0, 0, 'IN_MH', '', '', 1),
  ('county', 'IN_THANE', 'Thane District', 73, 0, 0, 'IN_MH', '', '', 1),
  ('county', 'IN_BLR_URBAN', 'Bengaluru Urban', 74, 0, 0, 'IN_KA', '', '', 1),
  ('county', 'IN_MYSURU', 'Mysuru District', 75, 0, 0, 'IN_KA', '', '', 1),
  ('county', 'IN_CHENNAI', 'Chennai District', 76, 0, 0, 'IN_TN', '', '', 1),
  ('county', 'IN_HYDERABAD', 'Hyderabad District', 77, 0, 0, 'IN_TG', '', '', 1),
  ('county', 'IN_AHMEDABAD', 'Ahmedabad District', 78, 0, 0, 'IN_GJ', '', '', 1),
  ('county', 'AE_DXB_DEIRA', 'Deira', 80, 0, 0, 'AE_DXB', '', '', 1),
  ('county', 'AE_DXB_BURDUBAI', 'Bur Dubai', 81, 0, 0, 'AE_DXB', '', '', 1),
  ('county', 'AE_DXB_DOWNTOWN', 'Downtown / Business Bay', 82, 0, 0, 'AE_DXB', '', '', 1),
  ('county', 'AE_DXB_JUMEIRAH', 'Jumeirah', 83, 0, 0, 'AE_DXB', '', '', 1),
  ('county', 'AE_AUH_CITY', 'Abu Dhabi City Central', 84, 0, 0, 'AE_AUH', '', '', 1),
  ('county', 'AE_AUH_ALAIN', 'Al Ain Region', 85, 0, 0, 'AE_AUH', '', '', 1),
  ('county', 'AE_AUH_DHAFRA', 'Al Dhafra Region', 86, 0, 0, 'AE_AUH', '', '', 1),
  ('county', 'AU_SYDNEY', 'Greater Sydney', 90, 0, 0, 'AU_NSW', '', '', 1),
  ('county', 'AU_HUNTER', 'Hunter Region', 91, 0, 0, 'AU_NSW', '', '', 1),
  ('county', 'AU_MELBOURNE', 'Greater Melbourne', 92, 0, 0, 'AU_VIC', '', '', 1),
  ('county', 'AU_GEELONG', 'Geelong', 93, 0, 0, 'AU_VIC', '', '', 1),
  ('county', 'AU_BRISBANE', 'Greater Brisbane', 94, 0, 0, 'AU_QLD', '', '', 1),
  ('county', 'AU_GOLDCOAST', 'Gold Coast', 95, 0, 0, 'AU_QLD', '', '', 1),
  ('county', 'AU_PERTH', 'Perth Metropolitan', 96, 0, 0, 'AU_WA', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `notes` = VALUES(`notes`), `activity` = VALUES(`activity`);

-- ============================================================================
-- 17. Client-Side Location Cascading Dropdowns & Dynamic Terminology Script
-- ============================================================================
INSERT INTO `layout_options` (
  `form_id`, `field_id`, `group_id`, `title`, `seq`, `data_type`, `uor`,
  `fld_length`, `max_length`, `list_id`, `titlecols`, `datacols`,
  `default_value`, `edit_options`, `description`
) VALUES (
  'DEM', 'location_cascading_script', 2, '', 8, 31, 1,
  0, 0, '', 0, 4,
  '', '', '<div style="display:none;" id="carelio_cascading_locations_container">
<script>
(function() {
    var countryStates = {"LC":[{"id":"LC_CAS","title":"Castries"},{"id":"LC_GRO","title":"Gros Islet"},{"id":"LC_VIF","title":"Vieux Fort"},{"id":"LC_SOU","title":"Soufrière"},{"id":"LC_DEN","title":"Dennery"},{"id":"LC_MIC","title":"Micoud"}],"JM":[{"id":"JM_KIN","title":"Kingston"},{"id":"JM_AND","title":"St. Andrew"},{"id":"JM_CAT","title":"St. Catherine"},{"id":"JM_JAM","title":"St. James (Montego Bay)"}],"BB":[{"id":"BB_MIC","title":"St. Michael (Bridgetown)"},{"id":"BB_CHR","title":"Christ Church"},{"id":"BB_JAM","title":"St. James"}],"BS":[{"id":"BS_NP","title":"New Providence (Nassau)"},{"id":"BS_GB","title":"Grand Bahama (Freeport)"}],"TT":[{"id":"TT_POS","title":"Port of Spain"},{"id":"TT_SFO","title":"San Fernando"},{"id":"TT_TOB","title":"Tobago"}],"US":[{"id":"AL","title":"Alabama"},{"id":"AK","title":"Alaska"},{"id":"AZ","title":"Arizona"},{"id":"AR","title":"Arkansas"},{"id":"CA","title":"California"},{"id":"CO","title":"Colorado"},{"id":"CT","title":"Connecticut"},{"id":"DE","title":"Delaware"},{"id":"FL","title":"Florida"},{"id":"GA","title":"Georgia"},{"id":"HI","title":"Hawaii"},{"id":"ID","title":"Idaho"},{"id":"IL","title":"Illinois"},{"id":"IN_US","title":"Indiana (US)"},{"id":"IA","title":"Iowa"},{"id":"KS","title":"Kansas"},{"id":"KY_US","title":"Kentucky"},{"id":"LA","title":"Louisiana"},{"id":"ME","title":"Maine"},{"id":"MD","title":"Maryland"},{"id":"MA","title":"Massachusetts"},{"id":"MI","title":"Michigan"},{"id":"MN","title":"Minnesota"},{"id":"MS","title":"Mississippi"},{"id":"MO","title":"Missouri"},{"id":"MT","title":"Montana"},{"id":"NE","title":"Nebraska"},{"id":"NV","title":"Nevada"},{"id":"NH","title":"New Hampshire"},{"id":"NJ","title":"New Jersey"},{"id":"NM","title":"New Mexico"},{"id":"NY","title":"New York"},{"id":"NC","title":"North Carolina"},{"id":"ND","title":"North Dakota"},{"id":"OH","title":"Ohio"},{"id":"OK","title":"Oklahoma"},{"id":"OR","title":"Oregon"},{"id":"PA","title":"Pennsylvania"},{"id":"RI","title":"Rhode Island"},{"id":"SC","title":"South Carolina"},{"id":"SD","title":"South Dakota"},{"id":"TN","title":"Tennessee"},{"id":"TX","title":"Texas"},{"id":"UT","title":"Utah"},{"id":"VT","title":"Vermont"},{"id":"VA","title":"Virginia"},{"id":"WA","title":"Washington"},{"id":"WV","title":"West Virginia"},{"id":"WI","title":"Wisconsin"},{"id":"WY","title":"Wyoming"},{"id":"DC","title":"District of Columbia"},{"id":"PR","title":"Puerto Rico"}],"IN":[{"id":"IN_MH","title":"Maharashtra"},{"id":"IN_DL","title":"Delhi (NCT)"},{"id":"IN_KA","title":"Karnataka"},{"id":"IN_TN","title":"Tamil Nadu"},{"id":"IN_TG","title":"Telangana"},{"id":"IN_GJ","title":"Gujarat"},{"id":"IN_UP","title":"Uttar Pradesh"},{"id":"IN_WB","title":"West Bengal"},{"id":"IN_KL","title":"Kerala"},{"id":"IN_RJ","title":"Rajasthan"},{"id":"IN_AP","title":"Andhra Pradesh"},{"id":"IN_MP","title":"Madhya Pradesh"},{"id":"IN_PB","title":"Punjab"},{"id":"IN_HR","title":"Haryana"},{"id":"IN_BR","title":"Bihar"},{"id":"IN_OD","title":"Odisha"},{"id":"IN_AS","title":"Assam"},{"id":"IN_JK","title":"Jammu & Kashmir"},{"id":"IN_GA","title":"Goa"},{"id":"IN_UT","title":"Uttarakhand"},{"id":"IN_CH","title":"Chandigarh"}],"AE":[{"id":"AE_DXB","title":"Dubai"},{"id":"AE_AUH","title":"Abu Dhabi"},{"id":"AE_SHJ","title":"Sharjah"},{"id":"AE_AJM","title":"Ajman"},{"id":"AE_RAK","title":"Ras Al Khaimah"},{"id":"AE_FUJ","title":"Fujairah"},{"id":"AE_UAQ","title":"Umm Al Quwain"}],"GB":[{"id":"GB_ENG","title":"England"},{"id":"GB_SCT","title":"Scotland"},{"id":"GB_WLS","title":"Wales"},{"id":"GB_NIR","title":"Northern Ireland"}],"IE":[{"id":"IE_LEI","title":"Leinster (Dublin)"},{"id":"IE_MUN","title":"Munster (Cork)"},{"id":"IE_CON","title":"Connacht (Galway)"},{"id":"IE_ULS","title":"Ulster"}],"DE":[{"id":"DE_BY","title":"Bavaria (Bayern)"},{"id":"DE_BE","title":"Berlin"},{"id":"DE_NW","title":"North Rhine-Westphalia"},{"id":"DE_BW","title":"Baden-Württemberg"},{"id":"DE_HE","title":"Hesse (Frankfurt)"}],"FR":[{"id":"FR_IDF","title":"Île-de-France (Paris)"},{"id":"FR_ARA","title":"Auvergne-Rhône-Alpes"},{"id":"FR_PAC","title":"Provence-Alpes-Côte d\'Azur"}],"ES":[{"id":"ES_MD","title":"Community of Madrid"},{"id":"ES_CT","title":"Catalonia (Barcelona)"},{"id":"ES_AN","title":"Andalusia"}],"ZA":[{"id":"ZA_GP","title":"Gauteng (Johannesburg/Pretoria)"},{"id":"ZA_WC","title":"Western Cape (Cape Town)"},{"id":"ZA_KZN","title":"KwaZulu-Natal (Durban)"},{"id":"ZA_EC","title":"Eastern Cape"}],"NG":[{"id":"NG_LA","title":"Lagos"},{"id":"NG_FC","title":"Federal Capital Territory (Abuja)"},{"id":"NG_KN","title":"Kano"},{"id":"NG_RI","title":"Rivers (Port Harcourt)"}],"KE":[{"id":"KE_NBO","title":"Nairobi County"},{"id":"KE_MBA","title":"Mombasa County"},{"id":"KE_KIS","title":"Kisumu County"},{"id":"KE_KIA","title":"Kiambu County"}],"GH":[{"id":"GH_AA","title":"Greater Accra"},{"id":"GH_AH","title":"Ashanti (Kumasi)"}],"EG":[{"id":"EG_CAI","title":"Cairo Governorate"},{"id":"EG_ALX","title":"Alexandria Governorate"}],"AU":[{"id":"AU_NSW","title":"New South Wales"},{"id":"AU_VIC","title":"Victoria"},{"id":"AU_QLD","title":"Queensland"},{"id":"AU_WA","title":"Western Australia"},{"id":"AU_SA","title":"South Australia"},{"id":"AU_TAS","title":"Tasmania"},{"id":"AU_ACT","title":"Australian Capital Territory"},{"id":"AU_NT","title":"Northern Territory"}]};

    var stateCounties = {
        "FL":[{"id":"FL_MIAMI_DADE","title":"Miami-Dade County"},{"id":"FL_BROWARD","title":"Broward County"},{"id":"FL_PALM_BEACH","title":"Palm Beach County"},{"id":"FL_ORANGE","title":"Orange County"},{"id":"FL_HILLSBOROUGH","title":"Hillsborough County"}],
        "CA":[{"id":"CA_LOS_ANGELES","title":"Los Angeles County"},{"id":"CA_ORANGE","title":"Orange County"},{"id":"CA_SAN_DIEGO","title":"San Diego County"},{"id":"CA_SANTA_CLARA","title":"Santa Clara County"},{"id":"CA_SAN_FRANCISCO","title":"San Francisco County"}],
        "NY":[{"id":"NY_NEW_YORK","title":"New York County (Manhattan)"},{"id":"NY_KINGS","title":"Kings County (Brooklyn)"},{"id":"NY_QUEENS","title":"Queens County"},{"id":"NY_BRONX","title":"Bronx County"},{"id":"NY_NASSAU","title":"Nassau County"}],
        "TX":[{"id":"TX_HARRIS","title":"Harris County (Houston)"},{"id":"TX_DALLAS","title":"Dallas County"},{"id":"TX_TARRANT","title":"Tarrant County (Fort Worth)"},{"id":"TX_TRAVIS","title":"Travis County (Austin)"},{"id":"TX_BEXAR","title":"Bexar County (San Antonio)"}],
        "IN_MH":[{"id":"IN_MUMBAI","title":"Mumbai District"},{"id":"IN_PUNE","title":"Pune District"},{"id":"IN_NAGPUR","title":"Nagpur District"},{"id":"IN_THANE","title":"Thane District"}],
        "IN_KA":[{"id":"IN_BLR_URBAN","title":"Bengaluru Urban"},{"id":"IN_MYSURU","title":"Mysuru District"}],
        "IN_TN":[{"id":"IN_CHENNAI","title":"Chennai District"}],
        "IN_TG":[{"id":"IN_HYDERABAD","title":"Hyderabad District"}],
        "IN_GJ":[{"id":"IN_AHMEDABAD","title":"Ahmedabad District"}],
        "AE_DXB":[{"id":"AE_DXB_DEIRA","title":"Deira"},{"id":"AE_DXB_BURDUBAI","title":"Bur Dubai"},{"id":"AE_DXB_DOWNTOWN","title":"Downtown / Business Bay"},{"id":"AE_DXB_JUMEIRAH","title":"Jumeirah"}],
        "AE_AUH":[{"id":"AE_AUH_CITY","title":"Abu Dhabi City Central"},{"id":"AE_AUH_ALAIN","title":"Al Ain Region"},{"id":"AE_AUH_DHAFRA","title":"Al Dhafra Region"}],
        "AU_NSW":[{"id":"AU_SYDNEY","title":"Greater Sydney"},{"id":"AU_HUNTER","title":"Hunter Region"}],
        "AU_VIC":[{"id":"AU_MELBOURNE","title":"Greater Melbourne"},{"id":"AU_GEELONG","title":"Geelong"}],
        "AU_QLD":[{"id":"AU_BRISBANE","title":"Greater Brisbane"},{"id":"AU_GOLDCOAST","title":"Gold Coast"}],
        "AU_WA":[{"id":"AU_PERTH","title":"Perth Metropolitan"}]
    };

    var stateToCountry = {"LC_CAS":"LC","LC_GRO":"LC","LC_VIF":"LC","LC_SOU":"LC","LC_DEN":"LC","LC_MIC":"LC","JM_KIN":"JM","JM_AND":"JM","JM_CAT":"JM","JM_JAM":"JM","BB_MIC":"BB","BB_CHR":"BB","BB_JAM":"BB","BS_NP":"BS","BS_GB":"BS","TT_POS":"TT","TT_SFO":"TT","TT_TOB":"TT","AL":"US","AK":"US","AZ":"US","AR":"US","CA":"US","CO":"US","CT":"US","DE":"US","FL":"US","GA":"US","HI":"US","ID":"US","IL":"US","IN_US":"US","IA":"US","KS":"US","KY_US":"US","LA":"US","ME":"US","MD":"US","MA":"US","MI":"US","MN":"US","MS":"US","MO":"US","MT":"US","NE":"US","NV":"US","NH":"US","NJ":"US","NM":"US","NY":"US","NC":"US","ND":"US","OH":"US","OK":"US","OR":"US","PA":"US","RI":"US","SC":"US","SD":"US","TN":"US","TX":"US","UT":"US","VT":"US","VA":"US","WA":"US","WV":"US","WI":"US","WY":"US","DC":"US","PR":"US","IN_MH":"IN","IN_DL":"IN","IN_KA":"IN","IN_TN":"IN","IN_TG":"IN","IN_GJ":"IN","IN_UP":"IN","IN_WB":"IN","IN_KL":"IN","IN_RJ":"IN","IN_AP":"IN","IN_MP":"IN","IN_PB":"IN","IN_HR":"IN","IN_BR":"IN","IN_OD":"IN","IN_AS":"IN","IN_JK":"IN","IN_GA":"IN","IN_UT":"IN","IN_CH":"IN","AE_DXB":"AE","AE_AUH":"AE","AE_SHJ":"AE","AE_AJM":"AE","AE_RAK":"AE","AE_FUJ":"AE","AE_UAQ":"AE","GB_ENG":"GB","GB_SCT":"GB","GB_WLS":"GB","GB_NIR":"GB","IE_LEI":"IE","IE_MUN":"IE","IE_CON":"IE","IE_ULS":"IE","DE_BY":"DE","DE_BE":"DE","DE_NW":"DE","DE_BW":"DE","DE_HE":"DE","FR_IDF":"FR","FR_ARA":"FR","FR_PAC":"FR","ES_MD":"ES","ES_CT":"ES","ES_AN":"ES","ZA_GP":"ZA","ZA_WC":"ZA","ZA_KZN":"ZA","ZA_EC":"ZA","NG_LA":"NG","NG_FC":"NG","NG_KN":"NG","NG_RI":"NG","KE_NBO":"KE","KE_MBA":"KE","KE_KIS":"KE","KE_KIA":"KE","GH_AA":"GH","GH_AH":"GH","EG_CAI":"EG","EG_ALX":"EG","AU_NSW":"AU","AU_VIC":"AU","AU_QLD":"AU","AU_WA":"AU","AU_SA":"AU","AU_TAS":"AU","AU_ACT":"AU","AU_NT":"AU"};

    var countyToState = {
        "FL_MIAMI_DADE":"FL","FL_BROWARD":"FL","FL_PALM_BEACH":"FL","FL_ORANGE":"FL","FL_HILLSBOROUGH":"FL",
        "CA_LOS_ANGELES":"CA","CA_ORANGE":"CA","CA_SAN_DIEGO":"CA","CA_SANTA_CLARA":"CA","CA_SAN_FRANCISCO":"CA",
        "NY_NEW_YORK":"NY","NY_KINGS":"NY","NY_QUEENS":"NY","NY_BRONX":"NY","NY_NASSAU":"NY",
        "TX_HARRIS":"TX","TX_DALLAS":"TX","TX_TARRANT":"TX","TX_TRAVIS":"TX","TX_BEXAR":"TX",
        "IN_MUMBAI":"IN_MH","IN_PUNE":"IN_MH","IN_NAGPUR":"IN_MH","IN_THANE":"IN_MH",
        "IN_BLR_URBAN":"IN_KA","IN_MYSURU":"IN_KA","IN_CHENNAI":"IN_TN","IN_HYDERABAD":"IN_TG","IN_AHMEDABAD":"IN_GJ",
        "AE_DXB_DEIRA":"AE_DXB","AE_DXB_BURDUBAI":"AE_DXB","AE_DXB_DOWNTOWN":"AE_DXB","AE_DXB_JUMEIRAH":"AE_DXB",
        "AE_AUH_CITY":"AE_AUH","AE_AUH_ALAIN":"AE_AUH","AE_AUH_DHAFRA":"AE_AUH",
        "AU_SYDNEY":"AU_NSW","AU_HUNTER":"AU_NSW","AU_MELBOURNE":"AU_VIC","AU_GEELONG":"AU_VIC",
        "AU_BRISBANE":"AU_QLD","AU_GOLDCOAST":"AU_QLD","AU_PERTH":"AU_WA"
    };

    var allStates = [{"id":"LC_CAS","title":"Castries"},{"id":"LC_GRO","title":"Gros Islet"},{"id":"LC_VIF","title":"Vieux Fort"},{"id":"LC_SOU","title":"Soufrière"},{"id":"LC_DEN","title":"Dennery"},{"id":"LC_MIC","title":"Micoud"},{"id":"JM_KIN","title":"Kingston"},{"id":"JM_AND","title":"St. Andrew"},{"id":"JM_CAT","title":"St. Catherine"},{"id":"JM_JAM","title":"St. James (Montego Bay)"},{"id":"BB_MIC","title":"St. Michael (Bridgetown)"},{"id":"BB_CHR","title":"Christ Church"},{"id":"BB_JAM","title":"St. James"},{"id":"BS_NP","title":"New Providence (Nassau)"},{"id":"BS_GB","title":"Grand Bahama (Freeport)"},{"id":"TT_POS","title":"Port of Spain"},{"id":"TT_SFO","title":"San Fernando"},{"id":"TT_TOB","title":"Tobago"},{"id":"AL","title":"Alabama"},{"id":"AK","title":"Alaska"},{"id":"AZ","title":"Arizona"},{"id":"AR","title":"Arkansas"},{"id":"CA","title":"California"},{"id":"CO","title":"Colorado"},{"id":"CT","title":"Connecticut"},{"id":"DE","title":"Delaware"},{"id":"FL","title":"Florida"},{"id":"GA","title":"Georgia"},{"id":"HI","title":"Hawaii"},{"id":"ID","title":"Idaho"},{"id":"IL","title":"Illinois"},{"id":"IN_US","title":"Indiana (US)"},{"id":"IA","title":"Iowa"},{"id":"KS","title":"Kansas"},{"id":"KY_US","title":"Kentucky"},{"id":"LA","title":"Louisiana"},{"id":"ME","title":"Maine"},{"id":"MD","title":"Maryland"},{"id":"MA","title":"Massachusetts"},{"id":"MI","title":"Michigan"},{"id":"MN","title":"Minnesota"},{"id":"MS","title":"Mississippi"},{"id":"MO","title":"Missouri"},{"id":"MT","title":"Montana"},{"id":"NE","title":"Nebraska"},{"id":"NV","title":"Nevada"},{"id":"NH","title":"New Hampshire"},{"id":"NJ","title":"New Jersey"},{"id":"NM","title":"New Mexico"},{"id":"NY","title":"New York"},{"id":"NC","title":"North Carolina"},{"id":"ND","title":"North Dakota"},{"id":"OH","title":"Ohio"},{"id":"OK","title":"Oklahoma"},{"id":"OR","title":"Oregon"},{"id":"PA","title":"Pennsylvania"},{"id":"RI","title":"Rhode Island"},{"id":"SC","title":"South Carolina"},{"id":"SD","title":"South Dakota"},{"id":"TN","title":"Tennessee"},{"id":"TX","title":"Texas"},{"id":"UT","title":"Utah"},{"id":"VT","title":"Vermont"},{"id":"VA","title":"Virginia"},{"id":"WA","title":"Washington"},{"id":"WV","title":"West Virginia"},{"id":"WI","title":"Wisconsin"},{"id":"WY","title":"Wyoming"},{"id":"DC","title":"District of Columbia"},{"id":"PR","title":"Puerto Rico"},{"id":"IN_MH","title":"Maharashtra"},{"id":"IN_DL","title":"Delhi (NCT)"},{"id":"IN_KA","title":"Karnataka"},{"id":"IN_TN","title":"Tamil Nadu"},{"id":"IN_TG","title":"Telangana"},{"id":"IN_GJ","title":"Gujarat"},{"id":"IN_UP","title":"Uttar Pradesh"},{"id":"IN_WB","title":"West Bengal"},{"id":"IN_KL","title":"Kerala"},{"id":"IN_RJ","title":"Rajasthan"},{"id":"IN_AP","title":"Andhra Pradesh"},{"id":"IN_MP","title":"Madhya Pradesh"},{"id":"IN_PB","title":"Punjab"},{"id":"IN_HR","title":"Haryana"},{"id":"IN_BR","title":"Bihar"},{"id":"IN_OD","title":"Odisha"},{"id":"IN_AS","title":"Assam"},{"id":"IN_JK","title":"Jammu & Kashmir"},{"id":"IN_GA","title":"Goa"},{"id":"IN_UT","title":"Uttarakhand"},{"id":"IN_CH","title":"Chandigarh"},{"id":"AE_DXB","title":"Dubai"},{"id":"AE_AUH","title":"Abu Dhabi"},{"id":"AE_SHJ","title":"Sharjah"},{"id":"AE_AJM","title":"Ajman"},{"id":"AE_RAK","title":"Ras Al Khaimah"},{"id":"AE_FUJ","title":"Fujairah"},{"id":"AE_UAQ","title":"Umm Al Quwain"},{"id":"GB_ENG","title":"England"},{"id":"GB_SCT","title":"Scotland"},{"id":"GB_WLS","title":"Wales"},{"id":"GB_NIR","title":"Northern Ireland"},{"id":"IE_LEI","title":"Leinster (Dublin)"},{"id":"IE_MUN","title":"Munster (Cork)"},{"id":"IE_CON","title":"Connacht (Galway)"},{"id":"IE_ULS","title":"Ulster"},{"id":"DE_BY","title":"Bavaria (Bayern)"},{"id":"DE_BE","title":"Berlin"},{"id":"DE_NW","title":"North Rhine-Westphalia"},{"id":"DE_BW","title":"Baden-Württemberg"},{"id":"DE_HE","title":"Hesse (Frankfurt)"},{"id":"FR_IDF","title":"Île-de-France (Paris)"},{"id":"FR_ARA","title":"Auvergne-Rhône-Alpes"},{"id":"FR_PAC","title":"Provence-Alpes-Côte d\'Azur"},{"id":"ES_MD","title":"Community of Madrid"},{"id":"ES_CT","title":"Catalonia (Barcelona)"},{"id":"ES_AN","title":"Andalusia"},{"id":"ZA_GP","title":"Gauteng (Johannesburg/Pretoria)"},{"id":"ZA_WC","title":"Western Cape (Cape Town)"},{"id":"ZA_KZN","title":"KwaZulu-Natal (Durban)"},{"id":"ZA_EC","title":"Eastern Cape"},{"id":"NG_LA","title":"Lagos"},{"id":"NG_FC","title":"Federal Capital Territory (Abuja)"},{"id":"NG_KN","title":"Kano"},{"id":"NG_RI","title":"Rivers (Port Harcourt)"},{"id":"KE_NBO","title":"Nairobi County"},{"id":"KE_MBA","title":"Mombasa County"},{"id":"KE_KIS","title":"Kisumu County"},{"id":"KE_KIA","title":"Kiambu County"},{"id":"GH_AA","title":"Greater Accra"},{"id":"GH_AH","title":"Ashanti (Kumasi)"},{"id":"EG_CAI","title":"Cairo Governorate"},{"id":"EG_ALX","title":"Alexandria Governorate"},{"id":"AU_NSW","title":"New South Wales"},{"id":"AU_VIC","title":"Victoria"},{"id":"AU_QLD","title":"Queensland"},{"id":"AU_WA","title":"Western Australia"},{"id":"AU_SA","title":"South Australia"},{"id":"AU_TAS","title":"Tasmania"},{"id":"AU_ACT","title":"Australian Capital Territory"},{"id":"AU_NT","title":"Northern Territory"}];

    var terminologyMap = {
        "LC": { state: "Parish / District", county: "Community" },
        "BS": { state: "Parish / District", county: "Community" },
        "BB": { state: "Parish / District", county: "Community" },
        "JM": { state: "Parish / District", county: "Community" },
        "BM": { state: "Parish / District", county: "Community" },
        "DM": { state: "Parish / District", county: "Community" },
        "TT": { state: "Parish / District", county: "Community" },
        "AG": { state: "Parish / District", county: "Community" },
        "KN": { state: "Parish / District", county: "Community" },
        "VC": { state: "Parish / District", county: "Community" },
        "GD": { state: "Parish / District", county: "Community" },
        "KY": { state: "Parish / District", county: "Community" },
        "US": { state: "State", county: "County" },
        "USA": { state: "State", county: "County" }
    };
    var defaultTerminology = { state: "State", county: "County" };

    function updateTerminology(countryCode) {
        var terms = terminologyMap[countryCode] || defaultTerminology;

        function setFieldLabel(fieldId, text) {
            var labelIds = ["label_" + fieldId, "label_id_text_" + fieldId, "label_id_" + fieldId];
            var foundAny = false;
            for (var i = 0; i < labelIds.length; i++) {
                var el = document.getElementById(labelIds[i]);
                if (el) {
                    foundAny = true;
                    var hadColon = el.textContent.trim().endsWith(":");
                    var colon = hadColon ? ":" : "";
                    var reqChild = el.querySelector ? el.querySelector(".required-indicator, .text-danger, sup") : null;
                    if (reqChild) {
                        el.innerHTML = reqChild.outerHTML + " " + text + colon;
                    } else {
                        el.textContent = text + colon;
                    }
                }
            }
            if (!foundAny) {
                var inputEl = document.getElementById("form_" + fieldId);
                if (inputEl) {
                    var row = inputEl.closest ? (inputEl.closest(".form-group") || inputEl.closest(".row") || inputEl.parentElement) : inputEl.parentElement;
                    if (row && row.querySelector) {
                        var el = row.querySelector(".label_custom, [id^=\'label_\'], label");
                        if (el) {
                            var hadColon = el.textContent.trim().endsWith(":");
                            var colon = hadColon ? ":" : "";
                            var reqChild = el.querySelector(".required-indicator, .text-danger, sup");
                            if (reqChild) {
                                el.innerHTML = reqChild.outerHTML + " " + text + colon;
                            } else {
                                el.textContent = text + colon;
                            }
                        }
                    }
                }
            }
        }

        setFieldLabel("state", terms.state);
        setFieldLabel("county", terms.county);
    }

    function setupLocationCascade() {
        var countryEl = document.getElementById("form_country_code") || document.getElementById("form_country");
        var stateEl = document.getElementById("form_state");
        var countyEl = document.getElementById("form_county");
        if (!countryEl || !stateEl) return;
        if (countryEl.getAttribute("data-cascade-ready") === "1") return;
        countryEl.setAttribute("data-cascade-ready", "1");

        function populateSelect(selectEl, items, desiredVal, emptyLabel) {
            if (!selectEl) return;
            var currentVal = (desiredVal !== undefined && desiredVal !== null) ? desiredVal : selectEl.value;
            selectEl.innerHTML = "";
            var optEmpty = document.createElement("option");
            optEmpty.value = "";
            optEmpty.textContent = emptyLabel || " ";
            selectEl.appendChild(optEmpty);
            var found = false;
            if (items && items.length > 0) {
                for (var i = 0; i < items.length; i++) {
                    var opt = document.createElement("option");
                    opt.value = items[i].id;
                    opt.textContent = items[i].title;
                    if (items[i].id === currentVal) {
                        opt.selected = true;
                        found = true;
                    }
                    selectEl.appendChild(opt);
                }
            }
            if (!found && currentVal) {
                var optCustom = document.createElement("option");
                optCustom.value = currentVal;
                optCustom.textContent = currentVal;
                optCustom.selected = true;
                selectEl.appendChild(optCustom);
            }
            if (window.jQuery && window.jQuery(selectEl).data("select2")) {
                window.jQuery(selectEl).trigger("change.select2");
            }
        }

        function syncStates(countryVal, preserveVal) {
            var targetVal = preserveVal ? stateEl.value : "";
            var list = countryVal ? (countryStates[countryVal] || []) : allStates;
            var terms = terminologyMap[countryVal] || defaultTerminology;
            var placeholder = (terms.state === "State") ? "Select State / Locality" : ("Select " + terms.state);
            populateSelect(stateEl, list, targetVal, placeholder);
            syncCounties(stateEl.value, preserveVal);
        }

        function syncCounties(stateVal, preserveVal) {
            if (!countyEl) return;
            var targetVal = preserveVal ? countyEl.value : "";
            var list = stateVal ? (stateCounties[stateVal] || []) : [];
            var cVal = countryEl ? countryEl.value : "";
            var terms = terminologyMap[cVal] || defaultTerminology;
            var placeholder = (terms.county === "County") ? "Select County / District" : ("Select " + terms.county);
            populateSelect(countyEl, list, targetVal, placeholder);
        }

        var initC = countryEl.value;
        var initS = stateEl.value;
        var initCo = countyEl ? countyEl.value : "";
        if (!initC && initS && stateToCountry[initS]) {
            initC = stateToCountry[initS];
            countryEl.value = initC;
            if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                window.jQuery(countryEl).trigger("change.select2");
            }
        }
        if (!initS && initCo && countyToState[initCo]) {
            initS = countyToState[initCo];
            stateEl.value = initS;
            if (!initC && stateToCountry[initS]) {
                initC = stateToCountry[initS];
                countryEl.value = initC;
                if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                    window.jQuery(countryEl).trigger("change.select2");
                }
            }
        }

        updateTerminology(initC);
        if (initC) syncStates(initC, true);
        else if (initS) syncCounties(initS, true);

        countryEl.addEventListener("change", function() {
            updateTerminology(this.value);
            syncStates(this.value, false);
        });

        stateEl.addEventListener("change", function() {
            var stVal = this.value;
            if (stVal && stateToCountry[stVal] && countryEl.value !== stateToCountry[stVal]) {
                countryEl.value = stateToCountry[stVal];
                if (window.jQuery && window.jQuery(countryEl).data("select2")) {
                    window.jQuery(countryEl).trigger("change.select2");
                }
                updateTerminology(countryEl.value);
            }
            syncCounties(stVal, false);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupLocationCascade);
    } else {
        setupLocationCascade();
    }
    setTimeout(setupLocationCascade, 250);
    setTimeout(setupLocationCascade, 800);
})();
</script>
</div>'
) ON DUPLICATE KEY UPDATE
  `group_id` = VALUES(`group_id`),
  `title` = VALUES(`title`),
  `seq` = VALUES(`seq`),
  `data_type` = VALUES(`data_type`),
  `uor` = VALUES(`uor`),
  `description` = VALUES(`description`);

-- ============================================================================
-- 18. Whitelabel Branding Globals (CarelioEMR External Links & About Page)
-- ============================================================================
INSERT INTO `globals` (`gl_name`, `gl_index`, `gl_value`) VALUES
  ('online_support_link', 0, ''),
  ('user_manual_link', 0, ''),
  ('main_menu_logo_link', 0, ''),
  ('main_menu_logo_title', 0, 'CarelioEMR'),
  ('display_acknowledgements', 0, '0'),
  ('display_donations_link', 0, '0'),
  ('display_review_link', 0, '0')
ON DUPLICATE KEY UPDATE `gl_value` = VALUES(`gl_value`);

-- ============================================================================
-- 19. Location Terminology Configuration Table (Caribbean vs US & Default)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `mod_site_admin_location_terminology` (
  `country_code` VARCHAR(10) NOT NULL,
  `region_type` VARCHAR(50) NOT NULL DEFAULT 'DEFAULT',
  `state_label` VARCHAR(100) NOT NULL DEFAULT 'State',
  `county_label` VARCHAR(100) NOT NULL DEFAULT 'County',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate Caribbean countries dynamically from list_options where mapping = 'CB'
INSERT INTO `mod_site_admin_location_terminology` (`country_code`, `region_type`, `state_label`, `county_label`)
SELECT `option_id`, 'CARIBBEAN', 'Parish / District', 'Community'
FROM `list_options`
WHERE `list_id` = 'country' AND `mapping` = 'CB'
ON DUPLICATE KEY UPDATE 
  `region_type` = VALUES(`region_type`), 
  `state_label` = VALUES(`state_label`), 
  `county_label` = VALUES(`county_label`);

-- Explicitly configure United States terminology
INSERT INTO `mod_site_admin_location_terminology` (`country_code`, `region_type`, `state_label`, `county_label`)
VALUES 
  ('US', 'US', 'State', 'County'),
  ('USA', 'US', 'State', 'County')
ON DUPLICATE KEY UPDATE 
  `region_type` = VALUES(`region_type`), 
  `state_label` = VALUES(`state_label`), 
  `county_label` = VALUES(`county_label`);
