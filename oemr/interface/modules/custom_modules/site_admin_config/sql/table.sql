-- CarelioEMR Site Admin Config Module Complete Database Seeder & Schema
-- Single, canonical SQL script containing full module schema, branding, GACL permissions, and user configuration.

-- 1. Module Tracking Table
CREATE TABLE IF NOT EXISTS `mod_site_admin_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `installed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.1 Carelio Caribbean Geographic Demographics Schema 1.0.0
CREATE TABLE IF NOT EXISTS carelio_geo_countries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  iso2 CHAR(2) NOT NULL,
  iso3 CHAR(3) NOT NULL,
  iso_numeric CHAR(3) NOT NULL,
  name VARCHAR(200) NOT NULL,
  source_standard VARCHAR(100) NOT NULL,
  source_ref VARCHAR(160) NOT NULL,
  source_url VARCHAR(1024) NOT NULL,
  status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT geo_country_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
  CONSTRAINT geo_country_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
  UNIQUE KEY uq_geo_country_iso2 (iso2),
  UNIQUE KEY uq_geo_country_iso3 (iso3),
  UNIQUE KEY uq_geo_country_numeric (iso_numeric),
  UNIQUE KEY uq_geo_country_source (source_ref),
  CONSTRAINT geo_country_codes CHECK (iso2 REGEXP '^[A-Z]{2}$' AND iso3 REGEXP '^[A-Z]{3}$' AND iso_numeric REGEXP '^[0-9]{3}$'),
  CONSTRAINT geo_country_name CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0';

CREATE TABLE IF NOT EXISTS carelio_geo_admin_areas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  country_id BIGINT UNSIGNED NOT NULL,
  parent_admin_area_id BIGINT UNSIGNED NULL,
  admin_level TINYINT UNSIGNED NOT NULL,
  admin_code VARCHAR(100) NULL,
  name VARCHAR(200) NOT NULL,
  admin_type VARCHAR(60) NOT NULL,
  name_key VARCHAR(200) GENERATED ALWAYS AS (LCASE(TRIM(name))) STORED,
  parent_key BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(parent_admin_area_id,0)) STORED,
  source_standard VARCHAR(100) NOT NULL,
  source_ref VARCHAR(160) NOT NULL,
  source_url VARCHAR(1024) NOT NULL,
  status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT geo_admin_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
  CONSTRAINT geo_admin_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
  UNIQUE KEY uq_geo_admin_country_id (country_id,id),
  UNIQUE KEY uq_geo_admin_source (country_id,source_ref),
  UNIQUE KEY uq_geo_admin_name (country_id,parent_key,admin_level,name_key),
  KEY ix_geo_admin_parent (country_id,parent_admin_area_id),
  CONSTRAINT fk_geo_admin_country FOREIGN KEY (country_id) REFERENCES carelio_geo_countries(id),
  CONSTRAINT fk_geo_admin_parent FOREIGN KEY (country_id,parent_admin_area_id) REFERENCES carelio_geo_admin_areas(country_id,id),
  CONSTRAINT geo_admin_level CHECK (admin_level <= 4 AND ((admin_level <= 1 AND parent_admin_area_id IS NULL) OR (admin_level > 1 AND parent_admin_area_id IS NOT NULL))),
  CONSTRAINT geo_admin_name CHECK (CHAR_LENGTH(TRIM(name)) > 0 AND CHAR_LENGTH(TRIM(admin_type)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0';

CREATE TABLE IF NOT EXISTS carelio_geo_localities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  country_id BIGINT UNSIGNED NOT NULL,
  admin_area_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  locality_type VARCHAR(60) NOT NULL,
  name_key VARCHAR(200) GENERATED ALWAYS AS (LCASE(TRIM(name))) STORED,
  source_standard VARCHAR(100) NOT NULL,
  source_ref VARCHAR(160) NOT NULL,
  source_url VARCHAR(1024) NOT NULL,
  status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
  active TINYINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT geo_locality_provenance CHECK (CHAR_LENGTH(TRIM(source_ref)) > 0 AND CHAR_LENGTH(TRIM(source_standard)) > 0 AND source_url LIKE 'https://%'),
  CONSTRAINT geo_locality_active CHECK (active IN (0,1) AND (status <> 'DEPRECATED' OR active = 0)),
  UNIQUE KEY uq_geo_locality_source (country_id,source_ref),
  UNIQUE KEY uq_geo_locality_name (country_id,admin_area_id,name_key),
  KEY ix_geo_locality_admin (country_id,admin_area_id),
  CONSTRAINT fk_geo_locality_country FOREIGN KEY (country_id) REFERENCES carelio_geo_countries(id),
  CONSTRAINT fk_geo_locality_admin FOREIGN KEY (country_id,admin_area_id) REFERENCES carelio_geo_admin_areas(country_id,id),
  CONSTRAINT geo_locality_name CHECK (CHAR_LENGTH(TRIM(name)) > 0 AND CHAR_LENGTH(TRIM(locality_type)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0';

CREATE TABLE IF NOT EXISTS carelio_geo_dataset_versions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  dataset_name VARCHAR(160) NOT NULL,
  dataset_version VARCHAR(32) NOT NULL,
  source TEXT NOT NULL,
  source_url VARCHAR(1024) NOT NULL,
  release_date DATE NOT NULL,
  imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  record_count BIGINT UNSIGNED NOT NULL,
  checksum CHAR(64) NOT NULL,
  status ENUM('VERIFIED','REVIEW_REQUIRED','DEPRECATED') NOT NULL,
  country_count BIGINT UNSIGNED NOT NULL,
  admin_area_count BIGINT UNSIGNED NOT NULL,
  locality_count BIGINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_geo_dataset_version (dataset_name,dataset_version),
  CONSTRAINT geo_dataset_checksum CHECK (checksum REGEXP '^[0-9a-f]{64}$'),
  CONSTRAINT geo_dataset_count CHECK (record_count = country_count + admin_area_count + locality_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Carelio geographic schema 1.0.0';

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
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'state', `uor` = 1, `seq` = 5, `title` = 'Parish / District' WHERE `form_id` = 'DEM' AND `field_id` = 'state';
UPDATE `layout_options` SET `data_type` = 26, `list_id` = 'county', `uor` = 1, `seq` = 6, `title` = 'Community' WHERE `form_id` = 'DEM' AND `field_id` = 'county';
UPDATE `layout_options` SET `seq` = 7 WHERE `form_id` = 'DEM' AND `field_id` = 'postal_code';
-- ============================================================================
-- 16. Location Lists (Country, State, County) Options Data
-- ============================================================================
-- ============================================================================
-- Official Caribbean Demographics list_options Data
-- ============================================================================
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('country', 'LC', 'Saint Lucia', 10, 0, 0, 'CB', '', '', 1),
  ('country', 'AI', 'Anguilla', 11, 0, 0, 'CB', '', '', 1),
  ('country', 'AG', 'Antigua and Barbuda', 12, 0, 0, 'CB', '', '', 1),
  ('country', 'BS', 'Bahamas', 13, 0, 0, 'CB', '', '', 1),
  ('country', 'BB', 'Barbados', 14, 0, 0, 'CB', '', '', 1),
  ('country', 'BM', 'Bermuda', 14, 0, 0, 'CB', '', '', 1),
  ('country', 'BZ', 'Belize', 15, 0, 0, 'CB', '', '', 1),
  ('country', 'VG', 'British Virgin Islands', 16, 0, 0, 'CB', '', '', 1),
  ('country', 'KY', 'Cayman Islands', 17, 0, 0, 'CB', '', '', 1),
  ('country', 'CU', 'Cuba', 18, 0, 0, 'CB', '', '', 1),
  ('country', 'CW', 'Curaçao', 19, 0, 0, 'CB', '', '', 1),
  ('country', 'DM', 'Dominica', 20, 0, 0, 'CB', '', '', 1),
  ('country', 'DO', 'Dominican Republic', 21, 0, 0, 'CB', '', '', 1),
  ('country', 'GD', 'Grenada', 22, 0, 0, 'CB', '', '', 1),
  ('country', 'GP', 'Guadeloupe', 23, 0, 0, 'CB', '', '', 1),
  ('country', 'GY', 'Guyana', 24, 0, 0, 'CB', '', '', 1),
  ('country', 'HT', 'Haiti', 25, 0, 0, 'CB', '', '', 1),
  ('country', 'JM', 'Jamaica', 26, 0, 0, 'CB', '', '', 1),
  ('country', 'MQ', 'Martinique', 27, 0, 0, 'CB', '', '', 1),
  ('country', 'MS', 'Montserrat', 28, 0, 0, 'CB', '', '', 1),
  ('country', 'PR', 'Puerto Rico', 29, 0, 0, 'CB', '', '', 1),
  ('country', 'KN', 'Saint Kitts and Nevis', 30, 0, 0, 'CB', '', '', 1),
  ('country', 'MF', 'Saint Martin', 31, 0, 0, 'CB', '', '', 1),
  ('country', 'VC', 'Saint Vincent and the Grenadines', 32, 0, 0, 'CB', '', '', 1),
  ('country', 'SX', 'Sint Maarten', 33, 0, 0, 'CB', '', '', 1),
  ('country', 'SR', 'Suriname', 34, 0, 0, 'CB', '', '', 1),
  ('country', 'TT', 'Trinidad and Tobago', 35, 0, 0, 'CB', '', '', 1),
  ('country', 'TC', 'Turks and Caicos Islands', 36, 0, 0, 'CB', '', '', 1),
  ('country', 'VI', 'United States Virgin Islands', 37, 0, 0, 'CB', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `activity` = VALUES(`activity`);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('state', 'LC_CAS', 'Castries', 1, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_GRO', 'Gros Islet', 2, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_VIF', 'Vieux Fort', 3, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_SOU', 'Soufrière', 4, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_DEN', 'Dennery', 5, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_MIC', 'Micoud', 6, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_ALR', 'Anse La Raye', 7, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_CAN', 'Canaries', 8, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_CHO', 'Choiseul', 9, 0, 0, 'LC', '', '', 1),
  ('state', 'LC_LAB', 'Laborie', 10, 0, 0, 'LC', '', '', 1)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `seq` = VALUES(`seq`), `mapping` = VALUES(`mapping`), `activity` = VALUES(`activity`);

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`, `activity`) VALUES
  ('county', 'LC_ALR_ANSE_LA_RAYE', 'Anse La Raye', 100, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_AU_TABOR', 'Au Tabor', 101, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_AU_TABOR_HILL', 'Au Tabor Hill', 102, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_BOIS_D_INDE', 'Bois D\'inde', 103, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_CAICO_MILLET', 'Caico / Millet', 104, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_CHAMPEN_ESTATE', 'Champen Estate', 105, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_DURANDEAU', 'Durandeau', 106, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_JACMEL', 'Jacmel', 107, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_JEAN_BAPTISTE', 'Jean Baptiste', 108, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_MASSACRE', 'Massacre', 109, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_MILLET', 'Millet', 110, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_MORNE_CISEAUX', 'Morne Ciseaux', 111, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_MORNE_D_OR', 'Morne D\'or', 112, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_ROSEAU_VALLEY', 'Roseau Valley', 113, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_ST_LAWRENCE', 'St Lawrence', 114, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_ST_LAWRENCE_ESTATE', 'St Lawrence Estate', 115, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_TETE_CHEMIN_MILLET', 'Tete Chemin / Millet', 116, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_VANARD', 'Vanard', 117, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_VENUS', 'Venus', 118, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_ALR_VILLAGE', 'Village', 119, 0, 0, 'LC_ALR', '', '', 1),
  ('county', 'LC_CAN_ANSE_LA_VERDUE', 'Anse La Verdue', 120, 0, 0, 'LC_CAN', '', '', 1),
  ('county', 'LC_CAN_BELVEDERE', 'Belvedere', 121, 0, 0, 'LC_CAN', '', '', 1),
  ('county', 'LC_CAN_CANARIES', 'Canaries', 122, 0, 0, 'LC_CAN', '', '', 1),
  ('county', 'LC_CAN_RIVERSIDE_ROAD', 'Riverside Road', 123, 0, 0, 'LC_CAN', '', '', 1),
  ('county', 'LC_CAN_VILLAGE', 'Village', 124, 0, 0, 'LC_CAN', '', '', 1),
  ('county', 'LC_CAS_ACTIVE_HILL', 'Active Hill', 125, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_AGARD_LANDS', 'Agard Lands', 126, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_ALMONDALE', 'Almondale', 127, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_AURENDEL_HILL', 'Aurendel Hill', 128, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BABONNEAU_PROPER', 'Babonneau Proper', 129, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BAGATELLE', 'Bagatelle', 130, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BALATA', 'Balata', 131, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BANANNES_BAY', 'Banannes Bay', 132, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BARNARD_HILL', 'Barnard Hill', 133, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BARRE_DENIS', 'Barre Denis', 134, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BARRE_DUCHAUSSEE', 'Barre Duchaussee', 135, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BARRE_ST_JOSEPH', 'Barre St Joseph', 136, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BELAIR', 'Belair', 137, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BELLA_ROSA', 'Bella Rosa', 138, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BEXON', 'Bexon', 139, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BISHOP_S_GAP_GHIRAWOO_ROAD', 'Bishop\'s Gap / Ghirawoo Road', 140, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BISSEE', 'Bissee', 141, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BLACK_MALLET', 'Black Mallet', 142, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BOCAGE', 'Bocage', 143, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BOIS_CATCHET', 'Bois Catchet', 144, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_BOIS_PATAT', 'Bois Patat', 145, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CABICHE_BABONNEAU', 'Cabiche / Babonneau', 146, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CACOA_BABONNEAU', 'Cacoa / Babonneau', 147, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CAPITAL_HILL', 'Capital Hill', 148, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CARELLIE', 'Carellie', 149, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CASTRIES', 'Castries', 150, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CEDARS', 'Cedars', 151, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CHASE_GARDENS', 'Chase Gardens', 152, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CHASSIN_BABONNEAU', 'Chassin / Babonneau', 153, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CHOPPIN', 'Choppin', 154, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CICERON', 'Ciceron', 155, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CITY', 'City', 156, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CITY_GATE', 'City Gate', 157, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CONWAY', 'Conway', 158, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_COOLIE_TOWN', 'Coolie Town', 159, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_COUBARIL', 'Coubaril', 160, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CROWNLANDS_MARC', 'Crownlands / Marc', 161, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_CUL_DE_SAC', 'Cul De Sac', 162, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_DARLING_ROAD', 'Darling Road', 163, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_DEGLOS', 'Deglos', 164, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_DERIERRE_FORT_OLD_VICTORIA_ROAD', 'Derierre Fort / Old Victoria Road', 165, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_DUBRASSAY', 'Dubrassay', 166, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_ENTREPOT', 'Entrepot', 167, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_FAUX_A_CHAUD', 'Faux A Chaud', 168, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_FOND_ASSAU_BABONNEAU', 'Fond Assau / Babonneau', 169, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_FOND_CANIE', 'Fond Canie', 170, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_FOND_MANGER', 'Fond Manger', 171, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_FORESTIERE', 'Forestiere', 172, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GEORGE_CHARLES_BOULEVARD', 'George Charles Boulevard', 173, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GEORGEVILLE', 'Georgeville', 174, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GIRARD_BABONNEAU', 'Girard / Babonneau', 175, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GOODLANDS', 'Goodlands', 176, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GRASS_STREET', 'Grass Street', 177, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GREEN_GOLD_BABONNEAU', 'Green Gold / Babonneau', 178, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_GUESNEAU', 'Guesneau', 179, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_HILL_20_BABONNEAU', 'Hill 20 / Babonneau', 180, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_HILLCREST_GARDENS', 'Hillcrest Gardens', 181, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_HOSPITAL_ROAD', 'Hospital Road', 182, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_INDEPENDENCE_CITY', 'Independence City', 183, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LA_CARIERRE', 'La Carierre', 184, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LA_CLERY', 'La Clery', 185, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LA_CROIX_MAINGOT', 'La Croix Maingot', 186, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LA_PANSEE', 'La Pansee', 187, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LA_TOC', 'La Toc', 188, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LABAYEE', 'Labayee', 189, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LANSE_ROAD', 'Lanse Road', 190, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LASTIC_HILL', 'Lastic Hill', 191, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_LESLIE_LAND', 'Leslie Land', 192, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MARC', 'Marc', 193, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MARCHAND', 'Marchand', 194, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MARIGOT', 'Marigot', 195, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MAYNARD_HILL', 'Maynard Hill', 196, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MONKEY_TOWN_CICERON', 'Monkey Town / Ciceron', 197, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MORNE_ASSAU_BABONNEAU', 'Morne Assau / Babonneau', 198, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MORNE_DUDON', 'Morne Dudon', 199, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_MORNE_ROAD', 'Morne Road', 200, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_NEW_VILLAGE', 'New Village', 201, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_ODSAN', 'Odsan', 202, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_PATTERSON_S_GAP', 'Patterson\'s Gap', 203, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_PAVEE', 'Pavee', 204, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_PEARTS_GAP', 'Pearts Gap', 205, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_PEROU', 'Perou', 206, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_QUATRE_CHEMINS', 'Quatre Chemins', 207, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_RAVINE_CHABOT', 'Ravine Chabot', 208, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_RAVINE_POISSON', 'Ravine Poisson', 209, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_RAVINE_TOUTERELLE', 'Ravine Touterelle', 210, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_RESINARD', 'Resinard', 211, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_ROCK_HALL', 'Rock Hall', 212, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_ROSE_HILL', 'Rose Hill', 213, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SAN_SOUCI', 'San Souci', 214, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SAND_DE_FEU', 'Sand De Feu', 215, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SAROT', 'Sarot', 216, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SUMMERSDALE', 'Summersdale', 217, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SUNBILT', 'Sunbilt', 218, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_SUNNY_ACRES', 'Sunny Acres', 219, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TALVERN_BABONNEAU', 'Talvern / Babonneau', 220, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TAPION', 'Tapion', 221, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_THE_MORNE', 'The Morne', 222, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TI_COLON', 'Ti Colon', 223, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TI_ROCHER', 'Ti Rocher', 224, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TROIS_PITON', 'Trois Piton', 225, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TROU_COCHON_MARC', 'Trou Cochon / Marc', 226, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_TROU_ROUGE', 'Trou Rouge', 227, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_VIDE_BOUTEILLE', 'Vide Bouteille', 228, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_VIGIE', 'Vigie', 229, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_WATERWORKS', 'Waterworks', 230, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_WILTON_S_YARD_GRAVE_YARD', 'Wilton\'s Yard / Grave Yard', 231, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CAS_YORKE_HILL', 'Yorke Hill', 232, 0, 0, 'LC_CAS', '', '', 1),
  ('county', 'LC_CHO_BELLE_VUE', 'Belle Vue', 233, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_BOIS_D_INDE', 'Bois D\'inde', 234, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_CAFFIERE', 'Caffiere', 235, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_CHOISEUL', 'Choiseul', 236, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_CHRISTIAN_HILL', 'Christian Hill', 237, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_DACRETIN', 'Dacretin', 238, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_DEBREIUL', 'Debreiul', 239, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_DELCER', 'Delcer', 240, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_DERRIERE_MORNE', 'Derriere Morne', 241, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_DUGARD', 'Dugard', 242, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_ESPERANCE', 'Esperance', 243, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_FRANCIOU', 'Franciou', 244, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_INDUSTRY', 'Industry', 245, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_LA_FARGUE', 'La Fargue', 246, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_LA_POINTE', 'La Pointe', 247, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_LAMAZE', 'Lamaze', 248, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_LE_RICHE', 'Le Riche', 249, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_MONGOUGE', 'Mongouge', 250, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_MONZIE', 'Monzie', 251, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_MORNE_JACQUES', 'Morne Jacques', 252, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_MORNE_SION', 'Morne Sion', 253, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_NEWFIELD_FIETTE', 'Newfield / Fiette', 254, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_PONYON', 'Ponyon', 255, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_RAVENEAU', 'Raveneau', 256, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_REUNION', 'Reunion', 257, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_RIVER_DOREE', 'River Doree', 258, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_ROBLOT', 'Roblot', 259, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_SAUZAY', 'Sauzay', 260, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_SAVANNES_GEORGE_CONSTITUTION_PARK', 'Savannes George / Constitution Park', 261, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_VICTORIA', 'Victoria', 262, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_CHO_VILLAGE', 'Village', 263, 0, 0, 'LC_CHO', '', '', 1),
  ('county', 'LC_DEN_ANSE_CANOT', 'Anse Canot', 264, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_AU_LEON', 'Au Leon', 265, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_BARA_BARA', 'Bara Bara', 266, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_BELMONT', 'Belmont', 267, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_BOIS_JOLI', 'Bois Joli', 268, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DELAIDE', 'Delaide', 269, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY', 'Dennery', 270, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY_BY_PASS', 'Dennery By Pass', 271, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY_BY_PASS_GREEN_MOUNTAIN', 'Dennery By Pass / Green Mountain', 272, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY_BY_PASS_ROCKY_LANE', 'Dennery By Pass / Rocky Lane', 273, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY_BY_PASS_WHITE_ROCK_GARDENS', 'Dennery By Pass / White Rock Gardens', 274, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DENNERY_VILLAGE', 'Dennery Village', 275, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DERNIERE_RIVIERE', 'Derniere Riviere', 276, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DERNIERE_RIVIERE_MARDI_GRAS_MORNE_CACA_COCHON', 'Derniere Riviere / Mardi Gras / Morne Caca Cochon', 277, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DESPINOZE', 'Despinoze', 278, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_DUBONAIRE', 'Dubonaire', 279, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_GADETTE', 'Gadette', 280, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_GRANDE_RAVINE', 'Grande Ravine', 281, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_GRANDE_RIVIERE', 'Grande Riviere', 282, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_LA_CAYE', 'La Caye', 283, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_LA_PELLE', 'La Pelle', 284, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_LA_POINTE', 'La Pointe', 285, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_LA_RESSOURCE', 'La Ressource', 286, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_LUMIERE', 'Lumiere', 287, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_MORNE_PANACHE', 'Morne Panache', 288, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_RICHE_FOND', 'Riche Fond', 289, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_RICHE_FOND_LA_BELLE_VIE', 'Riche Fond / La Belle Vie', 290, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_RICHE_FOND_NEW_VILLAGE', 'Riche Fond / New Village', 291, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_THAMAZO', 'Thamazo', 292, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_DEN_THOMAZO_TOURNESSE', 'Thomazo / Tournesse', 293, 0, 0, 'LC_DEN', '', '', 1),
  ('county', 'LC_GRO_BEAUSEJOUR', 'Beausejour', 294, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BELLA_ROSA', 'Bella Rosa', 295, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BELLE_VUE', 'Belle Vue', 296, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BOGUIS', 'Boguis', 297, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BOGUIS_DESA_BLOND', 'Boguis / Desa Blond', 298, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BOIS_D_ORANGE', 'Bois D\'orange', 299, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BOIS_D_ORANGE_TROUYA', 'Bois D\'orange / Trouya', 300, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BONNETERRE', 'Bonneterre', 301, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_BONNETERRE_GARDENS', 'Bonneterre Gardens', 302, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CAP_ESTATE', 'Cap Estate', 303, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CAP_ESTATE_BECUNE_PARK', 'Cap Estate / Becune Park', 304, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CAP_ESTATE_GOLF_PARK', 'Cap Estate / Golf Park', 305, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CAS_EN_BAS', 'Cas En Bas', 306, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CAYE_MANJE', 'Caye Manje\'', 307, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CORINTH', 'Corinth', 308, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_CORINTH_ESTATE', 'Corinth Estate', 309, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_DES_BARRAS', 'Des Barras', 310, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_DESRAMEAUX', 'Desrameaux', 311, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GARRAND', 'Garrand', 312, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE', 'Grande Riviere', 313, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_ASSOU_CANAL', 'Grande Riviere / Assou Canal', 314, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_DEGAZON', 'Grande Riviere / Degazon', 315, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_INGLE_WOODS', 'Grande Riviere / Ingle Woods', 316, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_MORNE_SERPENT', 'Grande Riviere / Morne Serpent', 317, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_NORBERT', 'Grande Riviere / Norbert', 318, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_PIAT', 'Grande Riviere / Piat', 319, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GRANDE_RIVIERE_WHITE_ROCK', 'Grande Riviere / White Rock', 320, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GROS_ISLET', 'Gros Islet', 321, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_GROS_ISLET_TOWN', 'Gros Islet Town', 322, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_L_HERMITAGE', 'L\'hermitage', 323, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_LA_CROIX_CHAUBOUGH', 'La Croix Chaubough', 324, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_LA_GUERRE', 'La Guerre', 325, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MARISULE', 'Marisule', 326, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MARISULE_BON_AIR', 'Marisule / Bon Air', 327, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MARISULE_EAST_WINDS', 'Marisule / East Winds', 328, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MARISULE_TOP_OF_THE_WORLD', 'Marisule / Top Of The World', 329, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MASSADE', 'Massade', 330, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY', 'Monchy', 331, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_CAREFFE', 'Monchy / Careffe', 332, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_LA_BORNE', 'Monchy / La Borne', 333, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_LA_RETRAITE', 'Monchy / La Retraite', 334, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_LAFEUILLE', 'Monchy / Lafeuille', 335, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_MALGRETOUTE', 'Monchy / Malgretoute', 336, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_MOULIN_A_VENT', 'Monchy / Moulin A Vent', 337, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_RAVINE_MACOCK', 'Monchy / Ravine Macock', 338, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_RIVIERE_MITAN', 'Monchy / Riviere Mitan', 339, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_TI_DAUPHIN', 'Monchy / Ti Dauphin', 340, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONCHY_VIEUX_SUCREIC', 'Monchy / Vieux Sucreic', 341, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONGIRAUD', 'Mongiraud', 342, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MONIER', 'Monier', 343, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_MORNE_CITON', 'Morne Citon', 344, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_PAIX_BOUCHE', 'Paix Bouche', 345, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_PLATEAU', 'Plateau', 346, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_REDUIT', 'Reduit', 347, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_REDUIT_ORCHARD', 'Reduit Orchard', 348, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_REDUIT_PARK', 'Reduit Park', 349, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_RODNEY_BAY', 'Rodney Bay', 350, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_RODNEY_HEIGHTS', 'Rodney Heights', 351, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_UNION', 'Union', 352, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_UNION_TI_MORNE', 'Union / Ti Morne', 353, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_GRO_UNION_TERRACE', 'Union Terrace', 354, 0, 0, 'LC_GRO', '', '', 1),
  ('county', 'LC_LAB_BALCA', 'Balca', 355, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_BALEMBOUCHE', 'Balembouche', 356, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_BANSE', 'Banse', 357, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_BANSE_LA_GRACE', 'Banse La Grace', 358, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_DABAN', 'Daban', 359, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_FOND_BERANGE', 'Fond Berange', 360, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_GAYABOIS', 'Gayabois', 361, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_GENTIL', 'Gentil', 362, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_GETRINE', 'Getrine', 363, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_GIRAUD', 'Giraud', 364, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_H_ERELLE', 'H\'erelle', 365, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_KENNEDY_HIGHWAY', 'Kennedy Highway', 366, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_LA_HAUT', 'La Haut', 367, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_LA_PERLE', 'La Perle', 368, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_LABORIE', 'Laborie', 369, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_LONDONDERRY', 'Londonderry', 370, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_MACDOMEL', 'Macdomel', 371, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_OLIBO', 'Olibo', 372, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_PARC_ESTATE', 'Parc Estate', 373, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_PIAYE', 'Piaye', 374, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_SALTIBUS', 'Saltibus', 375, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_SAPHIRE', 'Saphire', 376, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_TETE_MORNE', 'Tete Morne', 377, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_LAB_VILLAGE', 'Village', 378, 0, 0, 'LC_LAB', '', '', 1),
  ('county', 'LC_MIC_ANSE_GER', 'Anse Ger', 379, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_BEAUCHAMP', 'Beauchamp', 380, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_BLANCHARD', 'Blanchard', 381, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_CACAO_VIGIE', 'Cacao / Vigie', 382, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_CHIQUE_BLANCHARD', 'Chique / Blanchard', 383, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_DES_BLANCHARD', 'Des Blanchard', 384, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_DESRUISSEAUX', 'Desruisseaux', 385, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_DUGARD', 'Dugard', 386, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_ESCAP', 'Escap', 387, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_FOND_DESRUISSEAUX', 'Fond / Desruisseaux', 388, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_GOMIER', 'Gomier', 389, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_L_EAU_MINEAU', 'L\'eau Mineau', 390, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LA_COURVILLE', 'La Courville', 391, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LA_HAUT', 'La Haut', 392, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LA_POINTE', 'La Pointe', 393, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LEZY', 'Lezy', 394, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LOMBARD', 'Lombard', 395, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_LONDON_ROAD', 'London Road', 396, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_MALGRETOUTE', 'Malgretoute', 397, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_MICOUD', 'Micoud', 398, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_MON_REPOS', 'Mon Repos', 399, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_MORNE_VIENT', 'Morne Vient', 400, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_NEW_VILLAGE', 'New Village', 401, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_PAIX_BOUCHE', 'Paix Bouche', 402, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_PATIENCE', 'Patience', 403, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_PRASLIN', 'Praslin', 404, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_SAVANNES', 'Savannes', 405, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_TI_RIVIERE', 'Ti Riviere', 406, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_TI_ROCHER', 'Ti Rocher', 407, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_VILLAGE', 'Village', 408, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_MIC_VOLET', 'Volet', 409, 0, 0, 'LC_MIC', '', '', 1),
  ('county', 'LC_SOU_BARON_S_DRIVE_COIN_DE_L_ANSE', 'Baron\'s Drive / Coin De L\'anse', 410, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BEAUSEJOUR_MYERS_BRIDGE', 'Beausejour / Myers Bridge', 411, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BELFOND', 'Belfond', 412, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BELLE_PLAIN', 'Belle Plain', 413, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BELVEDERE', 'Belvedere', 414, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BOIS_D_INDE', 'Bois D\'inde', 415, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_BOUTON', 'Bouton', 416, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_CHATEAU_BELAIR', 'Chateau Belair', 417, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_CRESSLANDS', 'Cresslands', 418, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_DIAMOND_DIAMOND_ESTATE', 'Diamond / Diamond Estate', 419, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_ESPERANCE', 'Esperance', 420, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_ETANGS', 'Etangs', 421, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_FOND_BERNIER', 'Fond Bernier', 422, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_FOND_CACOA', 'Fond Cacoa', 423, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_FOND_GENS_LIBRE', 'Fond Gens Libre', 424, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_LENNY_HILL', 'Lenny Hill', 425, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_MALGRETOUTE', 'Malgretoute', 426, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_MOCHA', 'Mocha', 427, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_MORNE_LA_CROIX', 'Morne La Croix', 428, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_NEW_DEVELOPMENT', 'New Development', 429, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_PALMISTE', 'Palmiste', 430, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_RAVINE_CLAIRE', 'Ravine Claire', 431, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_ST_PHILLIP', 'St Phillip', 432, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_SULPHUR_SPRINGS', 'Sulphur Springs', 433, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_TOWN', 'Town', 434, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_SOU_ZENON', 'Zenon', 435, 0, 0, 'LC_SOU', '', '', 1),
  ('county', 'LC_VIF_AUGIER', 'Augier', 436, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_BEANE_FIELD', 'Beane Field', 437, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_BEAUSEJOUR', 'Beausejour', 438, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_BLACK_BAY', 'Black Bay', 439, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_BRUCEVILLE_SHANTY_TOWN', 'Bruceville / Shanty Town', 440, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_CANTONEMENT', 'Cantonement', 441, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_CARIERRE', 'Carierre', 442, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_CATIN', 'Catin', 443, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_COOLIE_TOWN', 'Coolie Town', 444, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_DERIERRE_BOIS', 'Derierre Bois', 445, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_DERIERRE_MORNE', 'Derierre Morne', 446, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_DOCAMEL_LA_RESOURCE', 'Docamel / La Resource', 447, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_EAU_PIQUANT_ST_URBAIN', 'Eau Piquant / St Urbain', 448, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_ESPERANCE', 'Esperance', 449, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_FOND_CAPECHE', 'Fond Capeche', 450, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_GRACE', 'Grace', 451, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_HEWANORRA_ORCHARD', 'Hewanorra Orchard', 452, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_INDUSTRIAL_ESTATE', 'Industrial Estate', 453, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_LA_RESOURCE', 'La Resource', 454, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_LA_RETRAITE', 'La Retraite', 455, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_LA_TOURNEY_CEDAR_HEIGHTS', 'La Tourney / Cedar Heights', 456, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_MORNE_CAYENNE', 'Morne Cayenne', 457, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_MORNE_VERT', 'Morne Vert', 458, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_MOULE_A_CHIQUE', 'Moule A Chique', 459, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_PIERROT', 'Pierrot', 460, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_POMME', 'Pomme', 461, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_ST_JUDE_S_HIGHWAY', 'St Jude\'s Highway', 462, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_TOWN', 'Town', 463, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_VIEUX_FORT_LABORIE_HIGHWAY', 'Vieux Fort / Laborie Highway', 464, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_VIEUX_FORT', 'Vieux-fort', 465, 0, 0, 'LC_VIF', '', '', 1),
  ('county', 'LC_VIF_WESTALL_GROUP_THE_MANGUE', 'Westall Group / The Mangue', 466, 0, 0, 'LC_VIF', '', '', 1)
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
    var countryStates = {"LC":[{"id":"LC_CAS","title":"Castries"},{"id":"LC_GRO","title":"Gros Islet"},{"id":"LC_VIF","title":"Vieux Fort"},{"id":"LC_SOU","title":"Soufrière"},{"id":"LC_DEN","title":"Dennery"},{"id":"LC_MIC","title":"Micoud"},{"id":"LC_ALR","title":"Anse La Raye"},{"id":"LC_CAN","title":"Canaries"},{"id":"LC_CHO","title":"Choiseul"},{"id":"LC_LAB","title":"Laborie"}],"JM":[{"id":"JM_KIN","title":"Kingston"},{"id":"JM_AND","title":"St. Andrew"},{"id":"JM_CAT","title":"St. Catherine"},{"id":"JM_JAM","title":"St. James (Montego Bay)"}],"BB":[{"id":"BB_MIC","title":"St. Michael (Bridgetown)"},{"id":"BB_CHR","title":"Christ Church"},{"id":"BB_JAM","title":"St. James"}],"BS":[{"id":"BS_NP","title":"New Providence (Nassau)"},{"id":"BS_GB","title":"Grand Bahama (Freeport)"}],"TT":[{"id":"TT_POS","title":"Port of Spain"},{"id":"TT_SFO","title":"San Fernando"},{"id":"TT_TOB","title":"Tobago"}],"US":[{"id":"AL","title":"Alabama"},{"id":"AK","title":"Alaska"},{"id":"AZ","title":"Arizona"},{"id":"AR","title":"Arkansas"},{"id":"CA","title":"California"},{"id":"CO","title":"Colorado"},{"id":"CT","title":"Connecticut"},{"id":"DE","title":"Delaware"},{"id":"FL","title":"Florida"},{"id":"GA","title":"Georgia"},{"id":"HI","title":"Hawaii"},{"id":"ID","title":"Idaho"},{"id":"IL","title":"Illinois"},{"id":"IN_US","title":"Indiana (US)"},{"id":"IA","title":"Iowa"},{"id":"KS","title":"Kansas"},{"id":"KY_US","title":"Kentucky"},{"id":"LA","title":"Louisiana"},{"id":"ME","title":"Maine"},{"id":"MD","title":"Maryland"},{"id":"MA","title":"Massachusetts"},{"id":"MI","title":"Michigan"},{"id":"MN","title":"Minnesota"},{"id":"MS","title":"Mississippi"},{"id":"MO","title":"Missouri"},{"id":"MT","title":"Montana"},{"id":"NE","title":"Nebraska"},{"id":"NV","title":"Nevada"},{"id":"NH","title":"New Hampshire"},{"id":"NJ","title":"New Jersey"},{"id":"NM","title":"New Mexico"},{"id":"NY","title":"New York"},{"id":"NC","title":"North Carolina"},{"id":"ND","title":"North Dakota"},{"id":"OH","title":"Ohio"},{"id":"OK","title":"Oklahoma"},{"id":"OR","title":"Oregon"},{"id":"PA","title":"Pennsylvania"},{"id":"RI","title":"Rhode Island"},{"id":"SC","title":"South Carolina"},{"id":"SD","title":"South Dakota"},{"id":"TN","title":"Tennessee"},{"id":"TX","title":"Texas"},{"id":"UT","title":"Utah"},{"id":"VT","title":"Vermont"},{"id":"VA","title":"Virginia"},{"id":"WA","title":"Washington"},{"id":"WV","title":"West Virginia"},{"id":"WI","title":"Wisconsin"},{"id":"WY","title":"Wyoming"},{"id":"DC","title":"District of Columbia"},{"id":"PR","title":"Puerto Rico"}],"IN":[{"id":"IN_MH","title":"Maharashtra"},{"id":"IN_DL","title":"Delhi (NCT)"},{"id":"IN_KA","title":"Karnataka"},{"id":"IN_TN","title":"Tamil Nadu"},{"id":"IN_TG","title":"Telangana"},{"id":"IN_GJ","title":"Gujarat"},{"id":"IN_UP","title":"Uttar Pradesh"},{"id":"IN_WB","title":"West Bengal"},{"id":"IN_KL","title":"Kerala"},{"id":"IN_RJ","title":"Rajasthan"},{"id":"IN_AP","title":"Andhra Pradesh"},{"id":"IN_MP","title":"Madhya Pradesh"},{"id":"IN_PB","title":"Punjab"},{"id":"IN_HR","title":"Haryana"},{"id":"IN_BR","title":"Bihar"},{"id":"IN_OD","title":"Odisha"},{"id":"IN_AS","title":"Assam"},{"id":"IN_JK","title":"Jammu & Kashmir"},{"id":"IN_GA","title":"Goa"},{"id":"IN_UT","title":"Uttarakhand"},{"id":"IN_CH","title":"Chandigarh"}],"AE":[{"id":"AE_DXB","title":"Dubai"},{"id":"AE_AUH","title":"Abu Dhabi"},{"id":"AE_SHJ","title":"Sharjah"},{"id":"AE_AJM","title":"Ajman"},{"id":"AE_RAK","title":"Ras Al Khaimah"},{"id":"AE_FUJ","title":"Fujairah"},{"id":"AE_UAQ","title":"Umm Al Quwain"}],"GB":[{"id":"GB_ENG","title":"England"},{"id":"GB_SCT","title":"Scotland"},{"id":"GB_WLS","title":"Wales"},{"id":"GB_NIR","title":"Northern Ireland"}],"IE":[{"id":"IE_LEI","title":"Leinster (Dublin)"},{"id":"IE_MUN","title":"Munster (Cork)"},{"id":"IE_CON","title":"Connacht (Galway)"},{"id":"IE_ULS","title":"Ulster"}],"DE":[{"id":"DE_BY","title":"Bavaria (Bayern)"},{"id":"DE_BE","title":"Berlin"},{"id":"DE_NW","title":"North Rhine-Westphalia"},{"id":"DE_BW","title":"Baden-Württemberg"},{"id":"DE_HE","title":"Hesse (Frankfurt)"}],"FR":[{"id":"FR_IDF","title":"Île-de-France (Paris)"},{"id":"FR_ARA","title":"Auvergne-Rhône-Alpes"},{"id":"FR_PAC","title":"Provence-Alpes-Côte d\'Azur"}],"ES":[{"id":"ES_MD","title":"Community of Madrid"},{"id":"ES_CT","title":"Catalonia (Barcelona)"},{"id":"ES_AN","title":"Andalusia"}],"ZA":[{"id":"ZA_GP","title":"Gauteng (Johannesburg/Pretoria)"},{"id":"ZA_WC","title":"Western Cape (Cape Town)"},{"id":"ZA_KZN","title":"KwaZulu-Natal (Durban)"},{"id":"ZA_EC","title":"Eastern Cape"}],"NG":[{"id":"NG_LA","title":"Lagos"},{"id":"NG_FC","title":"Federal Capital Territory (Abuja)"},{"id":"NG_KN","title":"Kano"},{"id":"NG_RI","title":"Rivers (Port Harcourt)"}],"KE":[{"id":"KE_NBO","title":"Nairobi County"},{"id":"KE_MBA","title":"Mombasa County"},{"id":"KE_KIS","title":"Kisumu County"},{"id":"KE_KIA","title":"Kiambu County"}],"GH":[{"id":"GH_AA","title":"Greater Accra"},{"id":"GH_AH","title":"Ashanti (Kumasi)"}],"EG":[{"id":"EG_CAI","title":"Cairo Governorate"},{"id":"EG_ALX","title":"Alexandria Governorate"}],"AU":[{"id":"AU_NSW","title":"New South Wales"},{"id":"AU_VIC","title":"Victoria"},{"id":"AU_QLD","title":"Queensland"},{"id":"AU_WA","title":"Western Australia"},{"id":"AU_SA","title":"South Australia"},{"id":"AU_TAS","title":"Tasmania"},{"id":"AU_ACT","title":"Australian Capital Territory"},{"id":"AU_NT","title":"Northern Territory"}]};

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

    var stateToCountry = {"LC_CAS":"LC","LC_GRO":"LC","LC_VIF":"LC","LC_SOU":"LC","LC_DEN":"LC","LC_MIC":"LC","LC_ALR":"LC","LC_CAN":"LC","LC_CHO":"LC","LC_LAB":"LC","JM_KIN":"JM","JM_AND":"JM","JM_CAT":"JM","JM_JAM":"JM","BB_MIC":"BB","BB_CHR":"BB","BB_JAM":"BB","BS_NP":"BS","BS_GB":"BS","TT_POS":"TT","TT_SFO":"TT","TT_TOB":"TT","AL":"US","AK":"US","AZ":"US","AR":"US","CA":"US","CO":"US","CT":"US","DE":"US","FL":"US","GA":"US","HI":"US","ID":"US","IL":"US","IN_US":"US","IA":"US","KS":"US","KY_US":"US","LA":"US","ME":"US","MD":"US","MA":"US","MI":"US","MN":"US","MS":"US","MO":"US","MT":"US","NE":"US","NV":"US","NH":"US","NJ":"US","NM":"US","NY":"US","NC":"US","ND":"US","OH":"US","OK":"US","OR":"US","PA":"US","RI":"US","SC":"US","SD":"US","TN":"US","TX":"US","UT":"US","VT":"US","VA":"US","WA":"US","WV":"US","WI":"US","WY":"US","DC":"US","PR":"US","IN_MH":"IN","IN_DL":"IN","IN_KA":"IN","IN_TN":"IN","IN_TG":"IN","IN_GJ":"IN","IN_UP":"IN","IN_WB":"IN","IN_KL":"IN","IN_RJ":"IN","IN_AP":"IN","IN_MP":"IN","IN_PB":"IN","IN_HR":"IN","IN_BR":"IN","IN_OD":"IN","IN_AS":"IN","IN_JK":"IN","IN_GA":"IN","IN_UT":"IN","IN_CH":"IN","AE_DXB":"AE","AE_AUH":"AE","AE_SHJ":"AE","AE_AJM":"AE","AE_RAK":"AE","AE_FUJ":"AE","AE_UAQ":"AE","GB_ENG":"GB","GB_SCT":"GB","GB_WLS":"GB","GB_NIR":"GB","IE_LEI":"IE","IE_MUN":"IE","IE_CON":"IE","IE_ULS":"IE","DE_BY":"DE","DE_BE":"DE","DE_NW":"DE","DE_BW":"DE","DE_HE":"DE","FR_IDF":"FR","FR_ARA":"FR","FR_PAC":"FR","ES_MD":"ES","ES_CT":"ES","ES_AN":"ES","ZA_GP":"ZA","ZA_WC":"ZA","ZA_KZN":"ZA","ZA_EC":"ZA","NG_LA":"NG","NG_FC":"NG","NG_KN":"NG","NG_RI":"NG","KE_NBO":"KE","KE_MBA":"KE","KE_KIS":"KE","KE_KIA":"KE","GH_AA":"GH","GH_AH":"GH","EG_CAI":"EG","EG_ALX":"EG","AU_NSW":"AU","AU_VIC":"AU","AU_QLD":"AU","AU_WA":"AU","AU_SA":"AU","AU_TAS":"AU","AU_ACT":"AU","AU_NT":"AU"};

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

    var allStates = [{"id":"LC_CAS","title":"Castries"},{"id":"LC_GRO","title":"Gros Islet"},{"id":"LC_VIF","title":"Vieux Fort"},{"id":"LC_SOU","title":"Soufrière"},{"id":"LC_DEN","title":"Dennery"},{"id":"LC_MIC","title":"Micoud"},{"id":"LC_ALR","title":"Anse La Raye"},{"id":"LC_CAN","title":"Canaries"},{"id":"LC_CHO","title":"Choiseul"},{"id":"LC_LAB","title":"Laborie"},{"id":"JM_KIN","title":"Kingston"},{"id":"JM_AND","title":"St. Andrew"},{"id":"JM_CAT","title":"St. Catherine"},{"id":"JM_JAM","title":"St. James (Montego Bay)"},{"id":"BB_MIC","title":"St. Michael (Bridgetown)"},{"id":"BB_CHR","title":"Christ Church"},{"id":"BB_JAM","title":"St. James"},{"id":"BS_NP","title":"New Providence (Nassau)"},{"id":"BS_GB","title":"Grand Bahama (Freeport)"},{"id":"TT_POS","title":"Port of Spain"},{"id":"TT_SFO","title":"San Fernando"},{"id":"TT_TOB","title":"Tobago"},{"id":"AL","title":"Alabama"},{"id":"AK","title":"Alaska"},{"id":"AZ","title":"Arizona"},{"id":"AR","title":"Arkansas"},{"id":"CA","title":"California"},{"id":"CO","title":"Colorado"},{"id":"CT","title":"Connecticut"},{"id":"DE","title":"Delaware"},{"id":"FL","title":"Florida"},{"id":"GA","title":"Georgia"},{"id":"HI","title":"Hawaii"},{"id":"ID","title":"Idaho"},{"id":"IL","title":"Illinois"},{"id":"IN_US","title":"Indiana (US)"},{"id":"IA","title":"Iowa"},{"id":"KS","title":"Kansas"},{"id":"KY_US","title":"Kentucky"},{"id":"LA","title":"Louisiana"},{"id":"ME","title":"Maine"},{"id":"MD","title":"Maryland"},{"id":"MA","title":"Massachusetts"},{"id":"MI","title":"Michigan"},{"id":"MN","title":"Minnesota"},{"id":"MS","title":"Mississippi"},{"id":"MO","title":"Missouri"},{"id":"MT","title":"Montana"},{"id":"NE","title":"Nebraska"},{"id":"NV","title":"Nevada"},{"id":"NH","title":"New Hampshire"},{"id":"NJ","title":"New Jersey"},{"id":"NM","title":"New Mexico"},{"id":"NY","title":"New York"},{"id":"NC","title":"North Carolina"},{"id":"ND","title":"North Dakota"},{"id":"OH","title":"Ohio"},{"id":"OK","title":"Oklahoma"},{"id":"OR","title":"Oregon"},{"id":"PA","title":"Pennsylvania"},{"id":"RI","title":"Rhode Island"},{"id":"SC","title":"South Carolina"},{"id":"SD","title":"South Dakota"},{"id":"TN","title":"Tennessee"},{"id":"TX","title":"Texas"},{"id":"UT","title":"Utah"},{"id":"VT","title":"Vermont"},{"id":"VA","title":"Virginia"},{"id":"WA","title":"Washington"},{"id":"WV","title":"West Virginia"},{"id":"WI","title":"Wisconsin"},{"id":"WY","title":"Wyoming"},{"id":"DC","title":"District of Columbia"},{"id":"PR","title":"Puerto Rico"},{"id":"IN_MH","title":"Maharashtra"},{"id":"IN_DL","title":"Delhi (NCT)"},{"id":"IN_KA","title":"Karnataka"},{"id":"IN_TN","title":"Tamil Nadu"},{"id":"IN_TG","title":"Telangana"},{"id":"IN_GJ","title":"Gujarat"},{"id":"IN_UP","title":"Uttar Pradesh"},{"id":"IN_WB","title":"West Bengal"},{"id":"IN_KL","title":"Kerala"},{"id":"IN_RJ","title":"Rajasthan"},{"id":"IN_AP","title":"Andhra Pradesh"},{"id":"IN_MP","title":"Madhya Pradesh"},{"id":"IN_PB","title":"Punjab"},{"id":"IN_HR","title":"Haryana"},{"id":"IN_BR","title":"Bihar"},{"id":"IN_OD","title":"Odisha"},{"id":"IN_AS","title":"Assam"},{"id":"IN_JK","title":"Jammu & Kashmir"},{"id":"IN_GA","title":"Goa"},{"id":"IN_UT","title":"Uttarakhand"},{"id":"IN_CH","title":"Chandigarh"},{"id":"AE_DXB","title":"Dubai"},{"id":"AE_AUH","title":"Abu Dhabi"},{"id":"AE_SHJ","title":"Sharjah"},{"id":"AE_AJM","title":"Ajman"},{"id":"AE_RAK","title":"Ras Al Khaimah"},{"id":"AE_FUJ","title":"Fujairah"},{"id":"AE_UAQ","title":"Umm Al Quwain"},{"id":"GB_ENG","title":"England"},{"id":"GB_SCT","title":"Scotland"},{"id":"GB_WLS","title":"Wales"},{"id":"GB_NIR","title":"Northern Ireland"},{"id":"IE_LEI","title":"Leinster (Dublin)"},{"id":"IE_MUN","title":"Munster (Cork)"},{"id":"IE_CON","title":"Connacht (Galway)"},{"id":"IE_ULS","title":"Ulster"},{"id":"DE_BY","title":"Bavaria (Bayern)"},{"id":"DE_BE","title":"Berlin"},{"id":"DE_NW","title":"North Rhine-Westphalia"},{"id":"DE_BW","title":"Baden-Württemberg"},{"id":"DE_HE","title":"Hesse (Frankfurt)"},{"id":"FR_IDF","title":"Île-de-France (Paris)"},{"id":"FR_ARA","title":"Auvergne-Rhône-Alpes"},{"id":"FR_PAC","title":"Provence-Alpes-Côte d\'Azur"},{"id":"ES_MD","title":"Community of Madrid"},{"id":"ES_CT","title":"Catalonia (Barcelona)"},{"id":"ES_AN","title":"Andalusia"},{"id":"ZA_GP","title":"Gauteng (Johannesburg/Pretoria)"},{"id":"ZA_WC","title":"Western Cape (Cape Town)"},{"id":"ZA_KZN","title":"KwaZulu-Natal (Durban)"},{"id":"ZA_EC","title":"Eastern Cape"},{"id":"NG_LA","title":"Lagos"},{"id":"NG_FC","title":"Federal Capital Territory (Abuja)"},{"id":"NG_KN","title":"Kano"},{"id":"NG_RI","title":"Rivers (Port Harcourt)"},{"id":"KE_NBO","title":"Nairobi County"},{"id":"KE_MBA","title":"Mombasa County"},{"id":"KE_KIS","title":"Kisumu County"},{"id":"KE_KIA","title":"Kiambu County"},{"id":"GH_AA","title":"Greater Accra"},{"id":"GH_AH","title":"Ashanti (Kumasi)"},{"id":"EG_CAI","title":"Cairo Governorate"},{"id":"EG_ALX","title":"Alexandria Governorate"},{"id":"AU_NSW","title":"New South Wales"},{"id":"AU_VIC","title":"Victoria"},{"id":"AU_QLD","title":"Queensland"},{"id":"AU_WA","title":"Western Australia"},{"id":"AU_SA","title":"South Australia"},{"id":"AU_TAS","title":"Tasmania"},{"id":"AU_ACT","title":"Australian Capital Territory"},{"id":"AU_NT","title":"Northern Territory"}];

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
-- 18. Whitelabel Branding Globals (CarelioEMR Branding, External Links & About Page)
-- ============================================================================
INSERT INTO `globals` (`gl_name`, `gl_index`, `gl_value`) VALUES
  ('openemr_name', 0, 'CarelioEMR'),
  ('main_menu_logo_title', 0, 'CarelioEMR'),
  ('portal_custom_title', 0, 'CarelioEMR Patient Portal'),
  ('online_support_link', 0, ''),
  ('user_manual_link', 0, ''),
  ('main_menu_logo_link', 0, ''),
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

UPDATE `layout_options` SET `title` = 'Parish / District' WHERE `form_id` = 'DEM' AND `field_id` = 'state';

UPDATE `layout_options` SET `title` = 'Community' WHERE `form_id` = 'DEM' AND `field_id` = 'county';
