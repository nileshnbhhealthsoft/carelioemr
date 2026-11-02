CREATE TABLE IF NOT EXISTS `form_dental_encounter` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pid` bigint(20) NOT NULL,
  `encounter` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  `user` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL DEFAULT '',
  `authorized` tinyint(4) NOT NULL DEFAULT 0,
  `activity` tinyint(4) NOT NULL DEFAULT 1,
  `revision` int unsigned NOT NULL DEFAULT 1,
  `payload` longtext NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_encounter` (`pid`, `encounter`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mod_dental_encounter_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) NOT NULL,
  `revision` int unsigned NOT NULL,
  `payload` longtext NOT NULL,
  `saved_at` datetime NOT NULL,
  `saved_by` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_revision` (`form_id`, `revision`)
) ENGINE=InnoDB;

INSERT INTO `registry` (`name`, `state`, `directory`, `sql_run`, `unpackaged`, `date`, `priority`, `category`, `nickname`, `patient_encounter`, `therapy_group_encounter`, `aco_spec`)
SELECT 'Dental Encounter', 1, 'dental_encounter', 1, 1, NOW(), 50, 'Clinical', 'Dental', 1, 0, 'encounters|notes'
WHERE NOT EXISTS (SELECT 1 FROM `registry` WHERE `directory` = 'dental_encounter');
