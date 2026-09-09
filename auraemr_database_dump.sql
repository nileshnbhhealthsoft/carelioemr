-- AuraEMR Cloud Healthcare Platform - Full Database Export Dump
-- Target Database: auraemr (MySQL 8.0 / MariaDB Compatible)
-- Generated: 2026-09-04

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Table structure for `users` (Admin Portal Users)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Credentials Seeder (admin@auraemr.com / admin123)
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `created_at`, `updated_at`) VALUES
(1, 'AuraEMR Super Admin', 'admin@auraemr.com', '2026-09-04 00:00:00', '$2y$12$4mU7113l/Ea04m7N41w9UOiB.c8Qz2Qj9xYd6QyG5hB0aY5c7ZkS2', '2026-09-04 00:00:00', '2026-09-04 00:00:00');

-- --------------------------------------------------------
-- Table structure for `subscriptions` (Subscriber Records & OpenEMR Tenants)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `practice_type` varchar(255) NOT NULL DEFAULT 'General Practice',
  `region` varchar(255) NOT NULL DEFAULT 'LC',
  `tenant_slug` varchar(255) DEFAULT NULL,
  `openemr_database` varchar(255) DEFAULT NULL,
  `openemr_site_url` varchar(255) DEFAULT NULL,
  `provision_status` varchar(255) NOT NULL DEFAULT 'completed',
  `provision_error` text DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `amount` decimal(8,2) NOT NULL DEFAULT '80.00',
  `currency` varchar(255) NOT NULL DEFAULT 'usd',
  `payment_status` varchar(255) NOT NULL DEFAULT 'succeeded',
  `setup_cost_status` varchar(255) NOT NULL DEFAULT 'billed_separately',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seeded Active Subscribers across 5 Global Regions
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `stripe_customer_id`, `stripe_payment_intent_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Sarah Johnson', 'sarah.johnson@medicalpractice.com', 'General Practice', 'LC', 'site_dr_sarah_johnson_1', 'openemr_site_dr_sarah_johnson_1', 'http://localhost:8000/openemr/sites/site_dr_sarah_johnson_1', 'completed', 'cus_Q98aF123bc', 'pi_3P98aF123bc45', 80.00, 'usd', 'succeeded', 'billed_separately', '2026-09-04 10:00:00', '2026-09-04 10:00:00', '2026-09-04 10:00:00'),
(2, 'Dr. Marcus Etienne', 'm.etienne@victoriahospital.lc', 'Hospitals', 'LC', 'site_dr_marcus_etienne_2', 'openemr_site_dr_marcus_etienne_2', 'http://localhost:8000/openemr/sites/site_dr_marcus_etienne_2', 'completed', 'cus_Q77bF456de', 'pi_3P77bF456de89', 80.00, 'usd', 'succeeded', 'billed_separately', '2026-09-03 10:00:00', '2026-09-03 10:00:00', '2026-09-03 10:00:00'),
(3, 'Dr. Rajesh Nair', 'drnair@specialtyclinic.in', 'Clinics', 'IN', 'site_dr_rajesh_nair_3', 'openemr_site_dr_rajesh_nair_3', 'http://localhost:8000/openemr/sites/site_dr_rajesh_nair_3', 'completed', 'cus_Q55cF789fg', 'pi_3P55cF789fg12', 80.00, 'usd', 'succeeded', 'billed_separately', '2026-09-02 10:00:00', '2026-09-02 10:00:00', '2026-09-02 10:00:00'),
(4, 'Dr. Fatima Al-Mansoori', 'f.almansoori@emirateshealth.ae', 'Clinics', 'AE', 'site_dr_fatima_al_mansoori_4', 'openemr_site_dr_fatima_al_mansoori_4', 'http://localhost:8000/openemr/sites/site_dr_fatima_al_mansoori_4', 'completed', 'cus_Q33dF901hi', 'pi_3P33dF901hi34', 80.00, 'usd', 'succeeded', 'billed_separately', '2026-09-01 10:00:00', '2026-09-01 10:00:00', '2026-09-01 10:00:00'),
(5, 'Dr. Elena Rostova', 'elena.rostova@stjudehospital.us', 'Hospitals', 'US', 'site_dr_elena_rostova_5', 'openemr_site_dr_elena_rostova_5', 'http://localhost:8000/openemr/sites/site_dr_elena_rostova_5', 'completed', 'cus_Q11eF234jk', 'pi_3P11eF234jk56', 80.00, 'usd', 'succeeded', 'billed_separately', '2026-08-31 10:00:00', '2026-08-31 10:00:00', '2026-08-31 10:00:00');

-- --------------------------------------------------------
-- Table structure for `sessions` (Database Sessions)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `jobs` (Queue Tasks)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
