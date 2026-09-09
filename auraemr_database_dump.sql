-- AuraEMR Platform MySQL Database Dump
-- Generated: 2026-09-07 18:53:11

CREATE DATABASE IF NOT EXISTS `auraemr`;
USE `auraemr`;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES ('1', 'CarelioEMR Super Admin', 'admin@carelioemr.com', '2026-09-04 11:47:20', '$2y$12$XZqTcheh3UqRgB2/1ontz.iI9LKFTPHkQ0cah9YRoxQQKpVIbAk/6', NULL, '2026-09-04 11:47:20', '2026-09-04 11:47:20');

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `doctor_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `practice_type` varchar(255) NOT NULL DEFAULT 'General Practice',
  `region` varchar(255) NOT NULL DEFAULT 'LC',
  `tenant_slug` varchar(255) DEFAULT NULL,
  `openemr_database` varchar(255) DEFAULT NULL,
  `openemr_site_url` varchar(255) DEFAULT NULL,
  `provision_status` varchar(255) NOT NULL DEFAULT 'pending',
  `provision_error` text DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `amount` decimal(8,2) NOT NULL DEFAULT 80.00,
  `currency` varchar(10) NOT NULL DEFAULT 'usd',
  `payment_status` varchar(255) NOT NULL DEFAULT 'pending',
  `setup_cost_status` varchar(255) NOT NULL DEFAULT 'billed_separately',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('1', 'Dr. Sarah Johnson', 'sarah.johnson@medicalpractice.com', 'General Practice', 'LC', 'site_dr_sarah_johnson_1', 'openemr_site_dr_sarah_johnson_1', 'http://localhost:8000/openemr/sites/site_dr_sarah_johnson_1', 'completed', NULL, 'cus_Q98aF123bc', 'pi_3P98aF123bc45', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-09-04 09:47:20', '2026-09-04 09:47:20', '2026-09-04 11:47:38');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('2', 'Dr. Marcus Etienne', 'm.etienne@victoriahospital.lc', 'Hospitals', 'LC', 'site_dr_marcus_etienne_2', 'openemr_site_dr_marcus_etienne_2', 'http://localhost:8000/openemr/sites/site_dr_marcus_etienne_2', 'completed', NULL, 'cus_Q77bF456de', 'pi_3P77bF456de89', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-09-03 11:47:20', '2026-09-03 11:47:20', '2026-09-04 11:47:39');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('3', 'Dr. Rajesh Nair', 'drnair@specialtyclinic.in', 'Clinics', 'IN', 'site_dr_rajesh_nair_3', 'openemr_site_dr_rajesh_nair_3', 'http://localhost:8000/openemr/sites/site_dr_rajesh_nair_3', 'completed', NULL, 'cus_Q55cF789fg', 'pi_3P55cF789fg12', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-09-02 11:47:20', '2026-09-02 11:47:20', '2026-09-04 11:47:39');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('4', 'Dr. Fatima Al-Mansoori', 'f.almansoori@emirateshealth.ae', 'Clinics', 'AE', 'site_dr_fatima_al_mansoori_4', 'openemr_site_dr_fatima_al_mansoori_4', 'http://localhost:8000/openemr/sites/site_dr_fatima_al_mansoori_4', 'completed', NULL, 'cus_Q33dF901hi', 'pi_3P33dF901hi34', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-09-01 11:47:20', '2026-09-01 11:47:20', '2026-09-04 11:47:39');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('5', 'Dr. Elena Rostova', 'elena.rostova@stjudehospital.us', 'Hospitals', 'US', 'site_dr_elena_rostova_5', 'openemr_site_dr_elena_rostova_5', 'http://localhost:8000/openemr/sites/site_dr_elena_rostova_5', 'completed', NULL, 'cus_Q11eF234jk', 'pi_3P11eF234jk56', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-08-31 11:47:20', '2026-08-31 11:47:20', '2026-09-04 11:47:40');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('6', 'radha', 'radha@gmail.com', 'General Practice', 'LC', NULL, NULL, NULL, 'pending', NULL, 'cus_VDRbqIk6uJMKE7', 'pi_3UD0ibF73bxUU91G2ceHiiud', NULL, '80.00', 'usd', 'succeeded', 'setup_cost_additional_billed_separately', '2026-09-07 11:28:12', '2026-09-07 11:28:09', '2026-09-07 11:28:12');
INSERT INTO `subscriptions` (`id`, `doctor_name`, `email`, `practice_type`, `region`, `tenant_slug`, `openemr_database`, `openemr_site_url`, `provision_status`, `provision_error`, `stripe_customer_id`, `stripe_payment_intent_id`, `stripe_subscription_id`, `amount`, `currency`, `payment_status`, `setup_cost_status`, `paid_at`, `created_at`, `updated_at`) VALUES ('7', 'Dr. Radha', 'shrivastavanandini11@gmail.com', 'Clinics', 'IN', 'site_dr_radha_7', 'openemr_site_dr_radha_7', 'http://localhost:8000/openemr/sites/site_dr_radha_7', 'completed', NULL, 'cus_RadhaStripeCustomer', 'pi_3P_RADHA_CONFIRMED_1788780568', NULL, '80.00', 'usd', 'succeeded', 'billed_separately', '2026-09-07 11:29:28', '2026-09-07 11:29:28', '2026-09-07 11:29:29');

