-- ========================================================
-- OpenEMR Custom Module: Inpatient & Hospital Management (oe-module-ipd)
-- Database Schema & Initial Catalog Definitions
-- ========================================================

-- 1. Hospital Infrastructure (Buildings, Floors, Wards, Rooms, Beds)
CREATE TABLE IF NOT EXISTS `ipd_buildings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `building_name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_building_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Hospital Buildings';

CREATE TABLE IF NOT EXISTS `ipd_floors` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `building_id` INT(11) UNSIGNED NOT NULL,
    `floor_number` VARCHAR(20) NOT NULL,
    `floor_name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_building` (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Hospital Floors';

CREATE TABLE IF NOT EXISTS `ipd_wards` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `floor_id` INT(11) UNSIGNED DEFAULT NULL,
    `ward_name` VARCHAR(100) NOT NULL,
    `ward_code` VARCHAR(30) NOT NULL,
    `ward_type` VARCHAR(50) NOT NULL DEFAULT 'General',
    `department` VARCHAR(100) DEFAULT 'General Medicine',
    `gender_preference` VARCHAR(20) DEFAULT 'Any',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ward_code` (`ward_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Wards and Units';

CREATE TABLE IF NOT EXISTS `ipd_rooms` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ward_id` INT(11) UNSIGNED NOT NULL,
    `room_number` VARCHAR(50) NOT NULL,
    `room_type` VARCHAR(50) DEFAULT 'Standard',
    `rate_per_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_ward` (`ward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Ward Rooms';

CREATE TABLE IF NOT EXISTS `ipd_beds` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ward_id` INT(11) UNSIGNED NOT NULL,
    `room_id` INT(11) UNSIGNED DEFAULT NULL,
    `bed_number` VARCHAR(50) NOT NULL,
    `bed_category` VARCHAR(50) NOT NULL DEFAULT 'Standard',
    `daily_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` VARCHAR(30) NOT NULL DEFAULT 'available',
    `current_admission_id` INT(11) UNSIGNED DEFAULT NULL,
    `is_isolation` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ward` (`ward_id`),
    KEY `idx_status` (`status`),
    KEY `idx_admission` (`current_admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Hospital Beds';

-- 2. Inpatient Admissions & Bed Allocations
CREATE TABLE IF NOT EXISTS `ipd_admissions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ipd_number` VARCHAR(50) NOT NULL,
    `pid` BIGINT(20) NOT NULL,
    `encounter_id` INT(11) DEFAULT NULL,
    `admission_date` DATETIME NOT NULL,
    `admission_type` VARCHAR(50) NOT NULL DEFAULT 'Elective',
    `attending_doctor_id` INT(11) NOT NULL,
    `admitting_doctor_id` INT(11) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT 'General Medicine',
    `ward_id` INT(11) UNSIGNED NOT NULL,
    `bed_id` INT(11) UNSIGNED NOT NULL,
    `provisional_diagnosis` TEXT DEFAULT NULL,
    `icd10_code` VARCHAR(50) DEFAULT NULL,
    `chief_complaints` TEXT DEFAULT NULL,
    `medical_history` TEXT DEFAULT NULL,
    `known_allergies` TEXT DEFAULT NULL,
    `attendant_name` VARCHAR(150) DEFAULT NULL,
    `attendant_relation` VARCHAR(50) DEFAULT NULL,
    `attendant_phone` VARCHAR(50) DEFAULT NULL,
    `emergency_contact` VARCHAR(100) DEFAULT NULL,
    `admission_status` VARCHAR(30) NOT NULL DEFAULT 'admitted',
    `status_notes` TEXT DEFAULT NULL,
    `discharged_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ipd_num` (`ipd_number`),
    KEY `idx_pid` (`pid`),
    KEY `idx_status` (`admission_status`),
    KEY `idx_doctor` (`attending_doctor_id`),
    KEY `idx_bed` (`bed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Admissions';

CREATE TABLE IF NOT EXISTS `ipd_bed_allocations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `patient_id` BIGINT(20) NOT NULL,
    `bed_id` INT(11) UNSIGNED NOT NULL,
    `ward_id` INT(11) UNSIGNED NOT NULL,
    `allocated_from` DATETIME NOT NULL,
    `allocated_to` DATETIME DEFAULT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `transfer_reason` TEXT DEFAULT NULL,
    `allocated_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm` (`admission_id`),
    KEY `idx_bed` (`bed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bed Allocation History';

-- 3. Doctor & Clinical Management (Daily Rounds, Medication Orders, MAR, Intake/Output)
CREATE TABLE IF NOT EXISTS `ipd_doctor_rounds` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `round_date` DATETIME NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `subjective_notes` TEXT DEFAULT NULL,
    `objective_findings` TEXT DEFAULT NULL,
    `assessment` TEXT DEFAULT NULL,
    `treatment_plan` TEXT DEFAULT NULL,
    `diet_orders` VARCHAR(255) DEFAULT NULL,
    `activity_orders` VARCHAR(255) DEFAULT NULL,
    `next_round_time` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_round` (`admission_id`),
    KEY `idx_doc` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Daily Inpatient Doctor Rounds';

CREATE TABLE IF NOT EXISTS `ipd_medication_orders` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `medicine_name` VARCHAR(255) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL,
    `route` VARCHAR(50) NOT NULL DEFAULT 'Oral',
    `frequency` VARCHAR(50) NOT NULL DEFAULT 'BD',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `instructions` TEXT DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_med` (`admission_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Medication Orders';

CREATE TABLE IF NOT EXISTS `ipd_mar_administrations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) UNSIGNED NOT NULL,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `scheduled_time` DATETIME NOT NULL,
    `administered_time` DATETIME DEFAULT NULL,
    `administered_by` INT(11) DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'given',
    `dosage_given` VARCHAR(100) DEFAULT NULL,
    `site_of_injection` VARCHAR(100) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order` (`order_id`),
    KEY `idx_adm` (`admission_id`),
    KEY `idx_sched` (`scheduled_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Medication Administration Record';

CREATE TABLE IF NOT EXISTS `ipd_intake_output` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `record_datetime` DATETIME NOT NULL,
    `intake_type` VARCHAR(100) DEFAULT 'Oral',
    `intake_amount_ml` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `output_type` VARCHAR(100) DEFAULT 'Urine',
    `output_amount_ml` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `recorded_by` INT(11) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_io` (`admission_id`),
    KEY `idx_time` (`record_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fluid Intake and Output';

-- 4. Nursing Management (Vitals, Care Plans, Shifts, Risk Scales)
CREATE TABLE IF NOT EXISTS `ipd_vitals` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `recorded_at` DATETIME NOT NULL,
    `recorded_by` INT(11) DEFAULT NULL,
    `pulse_rate` INT(5) DEFAULT NULL,
    `bp_systolic` INT(5) DEFAULT NULL,
    `bp_diastolic` INT(5) DEFAULT NULL,
    `temperature` DECIMAL(4,1) DEFAULT NULL,
    `resp_rate` INT(5) DEFAULT NULL,
    `spo2` INT(5) DEFAULT NULL,
    `pain_score` TINYINT(2) DEFAULT NULL,
    `blood_sugar` DECIMAL(6,2) DEFAULT NULL,
    `consciousness` VARCHAR(30) DEFAULT 'Alert',
    `oxygen_support` VARCHAR(100) DEFAULT 'Room Air',
    `clinical_notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_adm_vitals` (`admission_id`),
    KEY `idx_vitals_date` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Vital Signs Monitoring';

CREATE TABLE IF NOT EXISTS `ipd_nursing_notes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `nurse_id` INT(11) NOT NULL,
    `shift` VARCHAR(30) NOT NULL DEFAULT 'Morning',
    `nursing_diagnosis` TEXT DEFAULT NULL,
    `interventions` TEXT DEFAULT NULL,
    `patient_response` TEXT DEFAULT NULL,
    `handover_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_nurse` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nursing Care Plans and Notes';

CREATE TABLE IF NOT EXISTS `ipd_nurse_shifts` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nurse_id` INT(11) NOT NULL,
    `ward_id` INT(11) UNSIGNED NOT NULL,
    `shift_date` DATE NOT NULL,
    `shift_type` VARCHAR(30) NOT NULL DEFAULT 'Morning',
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_shift_date` (`shift_date`, `ward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Nurse Ward Duty Roster';

CREATE TABLE IF NOT EXISTS `ipd_risk_assessments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `assessment_type` VARCHAR(50) NOT NULL,
    `total_score` INT(5) NOT NULL DEFAULT 0,
    `risk_level` VARCHAR(50) NOT NULL,
    `criteria_json` LONGTEXT DEFAULT NULL,
    `assessed_by` INT(11) DEFAULT NULL,
    `assessed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_risk` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fall Risk and Pressure Ulcer Assessments';

-- 5. Operation Theatre (OT) Management
CREATE TABLE IF NOT EXISTS `ipd_operation_theatres` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `theatre_name` VARCHAR(100) NOT NULL,
    `theatre_code` VARCHAR(30) NOT NULL,
    `theatre_type` VARCHAR(50) DEFAULT 'General',
    `status` VARCHAR(30) NOT NULL DEFAULT 'available',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ot_code` (`theatre_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Operation Theatres';

CREATE TABLE IF NOT EXISTS `ipd_surgeries` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `surgery_name` VARCHAR(255) NOT NULL,
    `ot_id` INT(11) UNSIGNED NOT NULL,
    `lead_surgeon_id` INT(11) NOT NULL,
    `assistant_surgeon_id` INT(11) DEFAULT NULL,
    `anesthetist_id` INT(11) DEFAULT NULL,
    `anesthesia_type` VARCHAR(50) DEFAULT 'General',
    `scheduled_start` DATETIME NOT NULL,
    `scheduled_end` DATETIME NOT NULL,
    `actual_start` DATETIME DEFAULT NULL,
    `actual_end` DATETIME DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled',
    `surgical_safety_checklist` LONGTEXT DEFAULT NULL,
    `operation_notes` TEXT DEFAULT NULL,
    `post_op_instructions` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_surgery` (`admission_id`),
    KEY `idx_ot` (`ot_id`),
    KEY `idx_surgeon` (`lead_surgeon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Surgical Operations';

-- 6. Inpatient Billing, Catalog, Packages, Invoices & Payments
CREATE TABLE IF NOT EXISTS `ipd_charge_catalog` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_code` VARCHAR(50) NOT NULL,
    `service_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'General',
    `standard_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_taxable` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_service_code` (`service_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='IPD Tariff Master';

CREATE TABLE IF NOT EXISTS `ipd_packages` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_name` VARCHAR(255) NOT NULL,
    `package_code` VARCHAR(50) NOT NULL,
    `duration_days` INT(5) NOT NULL DEFAULT 1,
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `ward_category` VARCHAR(50) DEFAULT 'General',
    `description` TEXT DEFAULT NULL,
    `inclusions` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_pkg_code` (`package_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Hospital Treatment Packages';

CREATE TABLE IF NOT EXISTS `ipd_bills` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bill_number` VARCHAR(50) NOT NULL,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `bill_date` DATETIME NOT NULL,
    `bill_type` VARCHAR(30) NOT NULL DEFAULT 'interim',
    `total_bed_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_doctor_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_nursing_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_surgery_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_other_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `gross_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `insurance_covered` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `patient_payable` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bill_num` (`bill_number`),
    KEY `idx_adm_bill` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Invoices';

CREATE TABLE IF NOT EXISTS `ipd_bill_items` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bill_id` INT(11) UNSIGNED NOT NULL,
    `item_date` DATE NOT NULL,
    `service_name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `quantity` DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `notes` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_bill` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Invoice Line Items';

CREATE TABLE IF NOT EXISTS `ipd_payments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `receipt_number` VARCHAR(50) NOT NULL,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `bill_id` INT(11) UNSIGNED DEFAULT NULL,
    `payment_date` DATETIME NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_type` VARCHAR(30) NOT NULL DEFAULT 'advance',
    `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cash',
    `transaction_reference` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `received_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_receipt_num` (`receipt_number`),
    KEY `idx_adm_pmt` (`admission_id`),
    KEY `idx_bill_pmt` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Deposits and Payments';

-- 7. Diagnostic Orders (Lab & Radiology Orders during Inpatient Stay)
CREATE TABLE IF NOT EXISTS `ipd_diagnostics_orders` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `test_type` VARCHAR(30) NOT NULL DEFAULT 'lab',
    `test_name` VARCHAR(255) NOT NULL,
    `specimen_or_view` VARCHAR(100) DEFAULT NULL,
    `priority` VARCHAR(20) NOT NULL DEFAULT 'Routine',
    `status` VARCHAR(30) NOT NULL DEFAULT 'ordered',
    `order_date` DATETIME NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `results_summary` TEXT DEFAULT NULL,
    `report_file_url` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_adm_diag` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Lab and Radiology Orders';

-- 8. Discharge Management Workflow
CREATE TABLE IF NOT EXISTS `ipd_discharges` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `discharge_date` DATETIME NOT NULL,
    `discharge_type` VARCHAR(30) NOT NULL DEFAULT 'Normal',
    `doctor_clearance` TINYINT(1) NOT NULL DEFAULT 0,
    `nursing_clearance` TINYINT(1) NOT NULL DEFAULT 0,
    `pharmacy_clearance` TINYINT(1) NOT NULL DEFAULT 0,
    `billing_clearance` TINYINT(1) NOT NULL DEFAULT 0,
    `final_diagnosis` TEXT DEFAULT NULL,
    `icd10_code` VARCHAR(50) DEFAULT NULL,
    `hospital_course` TEXT DEFAULT NULL,
    `condition_at_discharge` TEXT DEFAULT NULL,
    `discharge_medications` TEXT DEFAULT NULL,
    `followup_instructions` TEXT DEFAULT NULL,
    `followup_date` DATE DEFAULT NULL,
    `bed_released` TINYINT(1) NOT NULL DEFAULT 0,
    `discharged_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_adm_discharge` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Patient Discharge Workflow and Clearances';

-- 9. Emergency / Casualty Triage
CREATE TABLE IF NOT EXISTS `ipd_emergency_triage` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pid` BIGINT(20) NOT NULL,
    `triage_number` VARCHAR(50) NOT NULL,
    `arrival_time` DATETIME NOT NULL,
    `triage_level` VARCHAR(20) NOT NULL DEFAULT 'Yellow',
    `chief_complaint` TEXT NOT NULL,
    `bp_systolic` INT(5) DEFAULT NULL,
    `bp_diastolic` INT(5) DEFAULT NULL,
    `pulse` INT(5) DEFAULT NULL,
    `temp` DECIMAL(4,1) DEFAULT NULL,
    `spo2` INT(5) DEFAULT NULL,
    `assigned_doctor_id` INT(11) DEFAULT NULL,
    `bay_number` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'triaged',
    `converted_admission_id` INT(11) UNSIGNED DEFAULT NULL,
    `triage_nurse_id` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_triage_num` (`triage_number`),
    KEY `idx_pid` (`pid`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Emergency and Casualty Triage';

-- 10. Patient & Attendant Services (Passes and Meals)
CREATE TABLE IF NOT EXISTS `ipd_attendant_passes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pass_number` VARCHAR(50) NOT NULL,
    `admission_id` INT(11) UNSIGNED NOT NULL,
    `attendant_name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `valid_from` DATETIME NOT NULL,
    `valid_to` DATETIME NOT NULL,
    `meal_plan` VARCHAR(50) DEFAULT 'Normal',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_pass_num` (`pass_number`),
    KEY `idx_adm_pass` (`admission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Attendant and Visitor Passes';

-- 11. Module Audit Trail
CREATE TABLE IF NOT EXISTS `ipd_audit_logs` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_id` INT(11) UNSIGNED DEFAULT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT(11) DEFAULT NULL,
    `user_id` INT(11) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `details_json` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adm_log` (`admission_id`),
    KEY `idx_action` (`action_type`),
    KEY `idx_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inpatient Action Audit Logs';

-- ========================================================
-- SEED DATA: Buildings, Floors, Wards, Beds & Tariffs
-- ========================================================

INSERT INTO `ipd_buildings` (`id`, `building_name`, `code`, `description`) VALUES
(1, 'Main Hospital Tower', 'BLD-MAIN', 'Primary Inpatient & Surgical Block'),
(2, 'Emergency & Trauma Center', 'BLD-EMRG', 'Casualty, Triage & Critical Care Wing')
ON DUPLICATE KEY UPDATE `building_name`=VALUES(`building_name`);

INSERT INTO `ipd_floors` (`id`, `building_id`, `floor_number`, `floor_name`) VALUES
(1, 1, '1', 'Ground Floor (Admission & Diagnostics)'),
(2, 1, '2', 'Second Floor (General Medical Wards)'),
(3, 1, '3', 'Third Floor (Surgical & Post-Op Wards)'),
(4, 1, '4', 'Fourth Floor (Intensive Care & Deluxe Rooms)'),
(5, 2, '1', 'Emergency & Triage Bay')
ON DUPLICATE KEY UPDATE `floor_name`=VALUES(`floor_name`);

INSERT INTO `ipd_wards` (`id`, `floor_id`, `ward_name`, `ward_code`, `ward_type`, `department`, `gender_preference`, `is_active`) VALUES
(1, 2, 'Male Medical Ward', 'W-MED-M', 'General', 'Internal Medicine', 'Male', 1),
(2, 2, 'Female Medical Ward', 'W-MED-F', 'General', 'Internal Medicine', 'Female', 1),
(3, 3, 'Post-Operative Surgical Ward', 'W-SURG', 'Semi-Private', 'General Surgery', 'Any', 1),
(4, 4, 'Intensive Care Unit (ICU)', 'W-ICU', 'ICU', 'Critical Care', 'Any', 1),
(5, 4, 'Deluxe Private Rooms', 'W-DLX', 'Private', 'Executive Healthcare', 'Any', 1),
(6, 5, 'Emergency Observation Bay', 'W-EMRG', 'Emergency', 'Emergency Medicine', 'Any', 1)
ON DUPLICATE KEY UPDATE `ward_name`=VALUES(`ward_name`);

INSERT INTO `ipd_rooms` (`id`, `ward_id`, `room_number`, `room_type`, `rate_per_day`) VALUES
(1, 1, 'Room 201 (General)', 'Shared 6-Bed', 120.00),
(2, 2, 'Room 202 (General)', 'Shared 6-Bed', 120.00),
(3, 3, 'Room 301 (Semi-Private)', 'Twin Sharing', 250.00),
(4, 4, 'ICU Unit 401', 'Critical Care Isolation', 800.00),
(5, 5, 'Deluxe Suite 405', 'Single Deluxe Suite', 450.00),
(6, 6, 'ER Bay 101', 'Observation Bay', 150.00)
ON DUPLICATE KEY UPDATE `room_number`=VALUES(`room_number`);

INSERT INTO `ipd_beds` (`id`, `ward_id`, `room_id`, `bed_number`, `bed_category`, `daily_charge`, `status`, `is_isolation`, `notes`) VALUES
(1, 1, 1, 'BED-M101', 'Standard', 120.00, 'available', 0, 'General Ward Bed Near Window'),
(2, 1, 1, 'BED-M102', 'Standard', 120.00, 'available', 0, 'General Ward Bed'),
(3, 1, 1, 'BED-M103', 'Standard', 120.00, 'available', 0, 'General Ward Bed with Central Oxygen'),
(4, 2, 2, 'BED-F201', 'Standard', 120.00, 'available', 0, 'Female Ward Bed'),
(5, 2, 2, 'BED-F202', 'Standard', 120.00, 'available', 0, 'Female Ward Bed Near Nursing Station'),
(6, 3, 3, 'BED-S301', 'Semi-Private', 250.00, 'available', 0, 'Semi-Private Motorized Bed'),
(7, 3, 3, 'BED-S302', 'Semi-Private', 250.00, 'available', 0, 'Semi-Private Motorized Bed'),
(8, 4, 4, 'ICU-BED-01', 'ICU', 800.00, 'available', 1, 'ICU Ventilator Bed with Advanced Monitor'),
(9, 4, 4, 'ICU-BED-02', 'ICU', 800.00, 'available', 1, 'ICU Bed with Cardiac Monitor & Infusion Pump'),
(10, 5, 5, 'DLX-405-A', 'Deluxe', 450.00, 'available', 0, 'Deluxe Suite with Attendant Bed & Refrigerator'),
(11, 6, 6, 'ER-BAY-01', 'Standard', 150.00, 'available', 0, 'Emergency Trauma Bed with Crash Cart Access'),
(12, 6, 6, 'ER-BAY-02', 'Standard', 150.00, 'available', 0, 'Emergency Triage Observation Bed')
ON DUPLICATE KEY UPDATE `bed_number`=VALUES(`bed_number`);

INSERT INTO `ipd_operation_theatres` (`id`, `theatre_name`, `theatre_code`, `theatre_type`, `status`) VALUES
(1, 'Major Surgical Suite A (Laminar Flow)', 'OT-01', 'General', 'available'),
(2, 'Orthopedic & Trauma OR', 'OT-02', 'Orthopedic', 'available'),
(3, 'Cardiac & Vascular Theatre', 'OT-03', 'Cardiac', 'available')
ON DUPLICATE KEY UPDATE `theatre_name`=VALUES(`theatre_name`);

INSERT INTO `ipd_charge_catalog` (`id`, `service_code`, `service_name`, `category`, `standard_rate`, `is_taxable`, `is_active`) VALUES
(1, 'SRV-BED-GEN', 'General Ward Bed Charge (Per Day)', 'Bed Charge', 120.00, 0, 1),
(2, 'SRV-BED-SEMI', 'Semi-Private Room Bed Charge (Per Day)', 'Bed Charge', 250.00, 0, 1),
(3, 'SRV-BED-DLX', 'Deluxe Room Bed Charge (Per Day)', 'Bed Charge', 450.00, 0, 1),
(4, 'SRV-BED-ICU', 'ICU Bed & Critical Monitoring (Per Day)', 'Bed Charge', 800.00, 0, 1),
(5, 'SRV-NURS-DAY', 'Daily Inpatient Nursing Care Fee', 'Nursing Care', 45.00, 0, 1),
(6, 'SRV-DOC-ROUND', 'Attending Physician Daily Round Fee', 'Doctor Round', 75.00, 0, 1),
(7, 'SRV-SPEC-CONS', 'Specialist Inpatient Consultation', 'Doctor Round', 120.00, 0, 1),
(8, 'SRV-OT-MAJOR', 'Major Operation Theatre Usage (First 2 Hours)', 'OT Charges', 500.00, 0, 1),
(9, 'SRV-OT-MINOR', 'Minor Operation Theatre Usage (1 Hour)', 'OT Charges', 250.00, 0, 1),
(10, 'SRV-ANESTH-GEN', 'General Anesthesia Service Fee', 'Surgery', 350.00, 0, 1),
(11, 'SRV-SURG-APP', 'Appendectomy Surgical Fee', 'Surgery', 1200.00, 0, 1),
(12, 'SRV-SURG-HERN', 'Hernia Repair Surgical Fee', 'Surgery', 1100.00, 0, 1),
(13, 'SRV-CBC', 'Complete Blood Count (CBC) with Differential', 'Diagnostic', 35.00, 0, 1),
(14, 'SRV-LFT', 'Liver Function Test (LFT Panel)', 'Diagnostic', 55.00, 0, 1),
(15, 'SRV-KFT', 'Kidney Function / Renal Panel (BUN/Creatinine/Electrolytes)', 'Diagnostic', 50.00, 0, 1),
(16, 'SRV-CXR', 'Chest X-Ray (PA View)', 'Diagnostic', 45.00, 0, 1),
(17, 'SRV-ECG', '12-Lead Electrocardiogram (ECG)', 'Diagnostic', 30.00, 0, 1),
(18, 'SRV-USG-ABD', 'Ultrasound Whole Abdomen and Pelvis', 'Diagnostic', 85.00, 0, 1)
ON DUPLICATE KEY UPDATE `service_name`=VALUES(`service_name`);

INSERT INTO `ipd_packages` (`id`, `package_name`, `package_code`, `duration_days`, `total_price`, `ward_category`, `description`, `inclusions`, `is_active`) VALUES
(1, 'Normal Delivery (Maternity 3-Day Package)', 'PKG-MAT-NRM', 3, 1400.00, 'General', 'Comprehensive vaginal delivery package including 3 days bed stay, obstetrician fee, nursing and routine newborn care.', 'General ward 3 days, Obstetrician delivery fee, Pediatrician newborn checkup, Routine IV fluids & post-natal monitoring.', 1),
(2, 'Caesarean Section (LSCS 4-Day Package)', 'PKG-MAT-CS', 4, 2400.00, 'Semi-Private', 'Elective or emergency C-section package covering OT charges, obstetrician, assistant, anesthetist, and 4 days stay.', 'Semi-private room 4 days, OT charges, Surgeon fee, Anesthesia, Pre-op labs & routine medications.', 1),
(3, 'Laparoscopic Cholecystectomy (Gallbladder 2-Day Package)', 'PKG-SURG-LAP-CHOL', 2, 2800.00, 'Semi-Private', 'Minimally invasive laparoscopic removal of gallbladder including OT, consumable kit, and 2 days hospitalization.', 'OT usage, Laparoscopic equipment kit, Surgeon & Anesthetist fees, 2 days semi-private bed, Post-op analgesia.', 1),
(4, 'Dengue / Fever Treatment (3-Day Care Package)', 'PKG-MED-DENGUE', 3, 650.00, 'General', 'Supportive inpatient care for severe febrile illness, daily CBC/platelet counts, IV hydration and nursing.', 'General ward bed 3 days, Daily CBC & hematocrit tests, Physician rounds, IV infusion sets and nursing care.', 1)
ON DUPLICATE KEY UPDATE `package_name`=VALUES(`package_name`);
