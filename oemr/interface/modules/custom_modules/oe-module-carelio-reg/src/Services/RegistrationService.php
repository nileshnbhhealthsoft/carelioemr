<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class RegistrationService
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate sequential temporary emergency MRN (e.g. TEMP-20261124-001)
     */
    public static function generateTempMrn(): string
    {
        $prefix = 'TEMP-' . date('Ymd') . '-';
        $row = sqlQuery("SELECT `temp_mrn` FROM `hms_temp_identity` WHERE `temp_mrn` LIKE ? ORDER BY `id` DESC LIMIT 1", [$prefix . '%']);
        if (!empty($row['temp_mrn'])) {
            $lastNum = (int) substr($row['temp_mrn'], -3);
            $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '001';
        }
        return $prefix . $nextNum;
    }

    /**
     * Generate sequential permanent MRN (e.g. MRN-20261125-001)
     */
    public static function generatePermanentMrn(): string
    {
        $prefix = 'MRN-' . date('Ymd') . '-';
        $row = sqlQuery("SELECT `pubpid` FROM `patient_data` WHERE `pubpid` LIKE ? ORDER BY `id` DESC LIMIT 1", [$prefix . '%']);
        if (!empty($row['pubpid'])) {
            $lastNum = (int) substr($row['pubpid'], -3);
            $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '001';
        }
        return $prefix . $nextNum;
    }

    /**
     * Unified Front-Desk Patient Registration with automatic duplicate flagging
     * Supports demographics, contacts, guardian, language/interpreter, optional insurance & consent.
     */
    public static function registerPatient(array $data, int $staffUserId): array
    {
        $fname = trim($data['fname'] ?? '');
        $lname = trim($data['lname'] ?? '');
        $mname = trim($data['mname'] ?? '');
        $dob = trim($data['DOB'] ?? '');
        $sex = trim($data['sex'] ?? '');
        $ss = trim($data['ss'] ?? '');
        $phoneCell = trim($data['phone_cell'] ?? '');
        $phoneHome = trim($data['phone_home'] ?? '');
        $email = trim($data['email'] ?? '');
        $street = trim($data['street'] ?? '');
        $city = trim($data['city'] ?? '');
        $state = trim($data['state'] ?? '');
        $postalCode = trim($data['postal_code'] ?? '');
        $countryCode = trim($data['country_code'] ?? 'LC');
        $guardianName = trim($data['guardian_name'] ?? '');
        $guardianRel = trim($data['guardian_relationship'] ?? '');
        $guardianPhone = trim($data['phone_contact'] ?? '');
        $language = trim($data['language'] ?? 'English');
        $interpreterNeeded = trim($data['interpreter_needed'] ?? 'no');
        $interpreterNotes = trim($data['interpreter'] ?? '');
        $siteId = trim($data['site_id'] ?? ($_SESSION['site_id'] ?? 'default'));
        $forceRegister = !empty($data['force_register']) && $data['force_register'] === '1';

        if ($fname === '' || $lname === '' || $dob === '' || $sex === '') {
            return ['success' => false, 'error' => 'First Name, Last Name, Date of Birth, and Sex are required.'];
        }

        // Flag duplicates at registration unless explicitly confirmed
        if (!$forceRegister) {
            $matches = self::detectDuplicates([
                'fname' => $fname,
                'lname' => $lname,
                'DOB' => $dob,
                'phone_cell' => $phoneCell,
                'ss' => $ss,
            ]);
            if (!empty($matches)) {
                return [
                    'success' => false,
                    'duplicate_warning' => true,
                    'matches' => $matches,
                    'error' => count($matches) . ' potential duplicate patient(s) detected. Review matches before registering.'
                ];
            }
        }

        $mrn = !empty($data['pubpid']) ? trim($data['pubpid']) : self::generatePermanentMrn();
        $contactRel = $guardianName !== ''
            ? ($guardianRel !== '' ? "$guardianName ($guardianRel)" : $guardianName)
            : $guardianRel;

        $sqlPt = "INSERT INTO `patient_data`
                  (`fname`, `mname`, `lname`, `DOB`, `sex`, `ss`, `pubpid`, `phone_cell`, `phone_home`, `email`,
                   `street`, `city`, `state`, `postal_code`, `country_code`, `contact_relationship`, `phone_contact`,
                   `language`, `interpreter_needed`, `interpreter`, `status`, `date`)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $insertId = (int) sqlInsert($sqlPt, [
            $fname, $mname, $lname, $dob, $sex, $ss, $mrn, $phoneCell, $phoneHome, $email,
            $street, $city, $state, $postalCode, $countryCode, $contactRel, $guardianPhone,
            $language, $interpreterNeeded, $interpreterNotes
        ]);
        sqlStatement("UPDATE `patient_data` SET `pid` = ? WHERE `id` = ?", [$insertId, $insertId]);
        $pid = $insertId;

        // Optional Insurance Policy at Registration
        $planName = trim($data['ins_plan_name'] ?? '');
        $policyNumber = trim($data['ins_policy_number'] ?? '');
        if ($planName !== '' || $policyNumber !== '') {
            sqlStatement(
                "INSERT INTO `insurance_data` (`type`, `provider`, `plan_name`, `policy_number`, `date`, `pid`, `copay`)
                 VALUES ('primary', '0', ?, ?, CURDATE(), ?, ?)",
                [$planName ?: 'Private Insurance', $policyNumber, $pid, trim($data['ins_copay'] ?? '0.00')]
            );
        }

        // Optional Consent at Registration
        $consentSigner = trim($data['consent_signer_name'] ?? '');
        if ($consentSigner !== '') {
            self::captureConsent([
                'pid' => $pid,
                'consent_type' => $data['consent_type'] ?? 'general_treatment',
                'signer_name' => $consentSigner,
                'signer_relationship' => $data['consent_signer_relationship'] ?? 'self',
                'notes' => 'Captured during front-desk registration',
            ], $staffUserId);
        }

        // Publish registration event with site_id for Day & Site reporting
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `facility_id`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('patient_registered', ?, 0, 1, ?, NOW(), NOW(), ?, 'processed')",
            [$pid, $staffUserId, json_encode(['mrn' => $mrn, 'site_id' => $siteId, 'language' => $language, 'interpreter_needed' => $interpreterNeeded])]
        );

        return [
            'success' => true,
            'pid' => $pid,
            'mrn' => $mrn,
            'message' => "Patient $fname $lname registered successfully (PID #$pid, MRN: $mrn)."
        ];
    }

    /**
     * Rapid Emergency Registration of an unknown/unconscious patient in < 5 seconds
     * Conforms to Specification Section 18.2 (Pages 37-38)
     */
    public static function rapidRegisterUnknownPatient(array $data, int $staffUserId): array
    {
        $tempMrn = self::generateTempMrn();
        $gender = !empty($data['estimated_gender']) ? $data['estimated_gender'] : 'Unknown';
        $estAge = !empty($data['estimated_age']) ? (int)$data['estimated_age'] : 30;
        $triageColor = !empty($data['triage_color']) ? $data['triage_color'] : 'red';
        $notes = trim($data['arrival_notes'] ?? 'Emergency arrival; identity unknown at intake.');

        // Calculate estimated DOB
        $estBirthYear = (int)date('Y') - $estAge;
        $estDob = $estBirthYear . '-01-01';

        // 1. Create Patient Record in OpenEMR's standard patient_data
        $sqlPt = "INSERT INTO `patient_data` 
                  (`fname`, `lname`, `sex`, `DOB`, `pubpid`, `status`, `date`) 
                  VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        $insertId = (int) sqlInsert($sqlPt, ['Unknown', $tempMrn, $gender, $estDob, $tempMrn]);
        sqlStatement("UPDATE `patient_data` SET `pid` = ? WHERE `id` = ?", [$insertId, $insertId]);
        $pid = $insertId;

        // 2. Open an immediate Emergency Inpatient Encounter
        $sqlEnc = "INSERT INTO `form_encounter`
                   (`date`, `pid`, `encounter`, `facility_id`, `provider_id`, `class_code`, `reason`)
                   VALUES (NOW(), ?, 0, 1, ?, 'EMER', ?)";
        $encId = (int) sqlInsert($sqlEnc, [$pid, $staffUserId, 'Emergency Trauma Intake (' . strtoupper($triageColor) . ' Triage)']);
        sqlStatement("UPDATE `form_encounter` SET `encounter` = ? WHERE `id` = ?", [$encId, $encId]);

        // 3. Register in hms_temp_identity tracker
        $sqlTemp = "INSERT INTO `hms_temp_identity`
                    (`temp_mrn`, `temp_pid`, `estimated_gender`, `estimated_age`, `triage_color`, `arrival_notes`, `status`, `created_by`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())";
        $tempId = (int) sqlInsert($sqlTemp, [$tempMrn, $pid, $gender, $estAge, $triageColor, $notes, $staffUserId]);

        // 4. Publish to internal hospital event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('emergency_patient_registered', ?, ?, ?, NOW(), NOW(), ?, 'published')",
            [$pid, $encId, $staffUserId, json_encode(['temp_mrn' => $tempMrn, 'triage_color' => $triageColor, 'estimated_age' => $estAge])]
        );

        return [
            'success' => true,
            'temp_id' => $tempId,
            'temp_mrn' => $tempMrn,
            'pid' => $pid,
            'encounter' => $encId,
            'message' => "Emergency patient registered under $tempMrn. Clinical orders unlocked!"
        ];
    }

    /**
     * Get all active (unreconciled) emergency temporary identities
     */
    public static function getActiveTempIdentities(): array
    {
        $sql = "SELECT ti.*, p.pubpid, p.DOB, p.date as reg_date,
                       u.fname as creator_fname, u.lname as creator_lname
                FROM `hms_temp_identity` ti
                LEFT JOIN `patient_data` p ON ti.`temp_pid` = p.`pid`
                LEFT JOIN `users` u ON ti.`created_by` = u.`id`
                WHERE ti.`status` = 'active'
                ORDER BY ti.`id` DESC";
        $rows = sqlStatement($sql);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get all reconciled emergency identities for audit
     */
    public static function getReconciledIdentities(): array
    {
        $sql = "SELECT ti.*, 
                       p1.pubpid as temp_pubpid,
                       p2.fname as real_fname, p2.lname as real_lname, p2.pubpid as real_pubpid,
                       u.fname as merger_fname, u.lname as merger_lname
                FROM `hms_temp_identity` ti
                LEFT JOIN `patient_data` p1 ON ti.`temp_pid` = p1.`pid`
                LEFT JOIN `patient_data` p2 ON ti.`reconciled_pid` = p2.`pid`
                LEFT JOIN `users` u ON ti.`reconciled_by` = u.`id`
                WHERE ti.`status` = 'reconciled'
                ORDER BY ti.`reconciled_at` DESC";
        $rows = sqlStatement($sql);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Safe Identity Reconciliation & Chart Merging
     * Moves all clinical notes, lab orders, and radiology from temp_pid to targetPid.
     * If targetPid <= 0 and new patient details are provided, creates the permanent patient first.
     */
    public static function reconcileIdentity(int $tempIdentityId, int $targetPid, int $staffUserId, array $newPatientData = []): array
    {
        $temp = sqlQuery("SELECT * FROM `hms_temp_identity` WHERE `id` = ? AND `status` = 'active' LIMIT 1", [$tempIdentityId]);
        if (!$temp) {
            return ['success' => false, 'error' => 'Temporary record not found or already reconciled.'];
        }

        if ($targetPid <= 0) {
            $fname = trim($newPatientData['new_fname'] ?? '');
            $lname = trim($newPatientData['new_lname'] ?? '');
            $dob = trim($newPatientData['new_DOB'] ?? '');
            $sex = trim($newPatientData['new_sex'] ?? '');

            if ($fname === '' || $lname === '' || $dob === '' || $sex === '') {
                return ['success' => false, 'error' => 'Please either select an existing patient PID, or enter First Name, Last Name, DOB, and Sex to create a new permanent patient.'];
            }

            $regRes = self::registerPatient([
                'fname' => $fname,
                'lname' => $lname,
                'DOB' => $dob,
                'sex' => $sex,
                'phone_cell' => trim($newPatientData['new_phone_cell'] ?? ''),
                'ss' => trim($newPatientData['new_ss'] ?? ''),
                'force_register' => '1',
            ], $staffUserId);

            if (empty($regRes['success']) || empty($regRes['pid'])) {
                return ['success' => false, 'error' => $regRes['error'] ?? 'Failed to create new permanent patient.'];
            }
            $targetPid = (int)$regRes['pid'];
        }

        $target = sqlQuery("SELECT `pid`, `fname`, `lname`, `pubpid` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$targetPid]);
        if (!$target) {
            return ['success' => false, 'error' => "Target permanent patient PID #$targetPid does not exist."];
        }

        $sourcePid = (int)$temp['temp_pid'];

        // 1. Transfer encounters from temporary chart to verified chart
        sqlStatement("UPDATE `form_encounter` SET `pid` = ? WHERE `pid` = ?", [$targetPid, $sourcePid]);

        // 2. Transfer lab and radiology procedure orders
        sqlStatement("UPDATE `procedure_order` SET `patient_id` = ? WHERE `patient_id` = ?", [$targetPid, $sourcePid]);

        // 3. Transfer prescriptions & medication orders
        sqlStatement("UPDATE `prescriptions` SET `patient_id` = ? WHERE `patient_id` = ?", [$targetPid, $sourcePid]);

        // 4. Transfer clinical notes & vital signs
        sqlStatement("UPDATE `form_vitals` SET `pid` = ? WHERE `pid` = ?", [$targetPid, $sourcePid]);

        // 5. Update temporary record status to 'reconciled' with audit stamp
        sqlStatement(
            "UPDATE `hms_temp_identity` SET 
             `status` = 'reconciled',
             `reconciled_pid` = ?,
             `reconciled_by` = ?,
             `reconciled_at` = NOW()
             WHERE `id` = ?",
            [$targetPid, $staffUserId, $tempIdentityId]
        );

        // 6. Update the temporary patient_data status to inactive/merged
        sqlStatement("UPDATE `patient_data` SET `status` = 'merged_inactive' WHERE `pid` = ?", [$sourcePid]);

        // 7. Publish to internal event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('identity_reconciled', ?, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$targetPid, $staffUserId, json_encode([
                'temp_mrn' => $temp['temp_mrn'],
                'source_pid' => $sourcePid,
                'target_pid' => $targetPid,
                'target_name' => $target['fname'] . ' ' . $target['lname']
            ])]
        );

        return [
            'success' => true,
            'message' => "Successfully merged {$temp['temp_mrn']} into {$target['fname']} {$target['lname']} (PID #$targetPid). All notes and orders transferred safely!"
        ];
    }

    /**
     * Pre-Admission Cash / Insurance Deposit Collection
     */
    public static function collectDeposit(array $data, int $staffUserId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $amount = (float)($data['deposit_amount'] ?? 0.00);
        $method = $data['payment_method'] ?? 'cash';
        $ref = trim($data['payment_reference'] ?? '');
        $notes = trim($data['notes'] ?? 'Pre-admission hospital deposit');
        $currency = $data['currency'] ?? 'XCD';

        if ($pid <= 0 || $amount <= 0) {
            return ['success' => false, 'error' => 'Valid Patient PID and Deposit Amount (> 0) required.'];
        }

        // Generate receipt number (e.g. RCPT-20261124-001)
        $prefix = 'RCPT-' . date('Ymd') . '-';
        $row = sqlQuery("SELECT `receipt_number` FROM `hms_deposit` WHERE `receipt_number` LIKE ? ORDER BY `id` DESC LIMIT 1", [$prefix . '%']);
        if (!empty($row['receipt_number'])) {
            $lastNum = (int) substr($row['receipt_number'], -3);
            $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '001';
        }
        $receiptNo = $prefix . $nextNum;

        // Create AR session entry in OpenEMR ar_session
        $arSessionId = (int) sqlInsert(
            "INSERT INTO `ar_session` 
             (`patient_id`, `payer_id`, `user_id`, `pay_total`, `payment_method`, `payment_type`, `deposit_date`, `post_to_date`, `adjustment_code`, `reference`, `description`)
             VALUES (?, 0, ?, ?, ?, 'patient', CURDATE(), CURDATE(), 'none', ?, ?)",
            [$pid, $staffUserId, $amount, $method, $receiptNo, $notes]
        );

        // Record in hms_deposit
        $depositId = (int) sqlInsert(
            "INSERT INTO `hms_deposit` 
             (`receipt_number`, `pid`, `deposit_amount`, `currency`, `payment_method`, `payment_reference`, `notes`, `ar_session_id`, `collected_by`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$receiptNo, $pid, $amount, $currency, $method, $ref, $notes, $arSessionId, $staffUserId]
        );

        // Publish to internal event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('deposit_collected', ?, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$pid, $staffUserId, json_encode(['receipt_number' => $receiptNo, 'amount' => $amount, 'currency' => $currency, 'method' => $method])]
        );

        $ptRow = sqlQuery("SELECT `fname`, `lname`, `pubpid` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$pid]);
        $patientName = $ptRow ? trim(($ptRow['fname'] ?? '') . ' ' . ($ptRow['lname'] ?? '')) : ('PID #' . $pid);

        return [
            'success' => true,
            'deposit_id' => $depositId,
            'receipt_number' => $receiptNo,
            'pid' => $pid,
            'patient_name' => $patientName,
            'mrn' => $ptRow['pubpid'] ?? '',
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $method,
            'payment_reference' => $ref,
            'notes' => $notes,
            'collected_at' => date('d-M-Y H:i'),
            'ar_session_id' => $arSessionId,
            'message' => "Deposit of $currency $amount collected successfully. Receipt #$receiptNo issued."
        ];
    }

    /**
     * Get recent hospital deposits
     */
    public static function getRecentDeposits(int $limit = 20): array
    {
        $sql = "SELECT d.*, 
                       p.fname, p.lname, p.pubpid,
                       u.fname as cashier_fname, u.lname as cashier_lname
                FROM `hms_deposit` d
                LEFT JOIN `patient_data` p ON d.`pid` = p.`pid`
                LEFT JOIN `users` u ON d.`collected_by` = u.`id`
                ORDER BY d.`id` DESC LIMIT ?";
        $rows = sqlStatement($sql, [$limit]);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Detect duplicate patients at registration or on demand
     * Conforms to Specification Acceptance Test: "A duplicate is flagged at registration"
     */
    public static function detectDuplicates(array $criteria): array
    {
        $fname = trim($criteria['fname'] ?? '');
        $lname = trim($criteria['lname'] ?? '');
        $dob = trim($criteria['DOB'] ?? '');
        $phone = trim($criteria['phone_cell'] ?? '');
        $ss = trim($criteria['ss'] ?? '');
        $excludePid = (int)($criteria['exclude_pid'] ?? 0);

        if (empty($fname) && empty($lname) && empty($dob) && empty($phone) && empty($ss)) {
            return [];
        }

        $sql = "SELECT pid, pubpid, fname, lname, DOB, sex, phone_cell, street, city, status, date as reg_date 
                FROM `patient_data` 
                WHERE `status` != 'merged_inactive' AND `pid` != ?";
        $res = sqlStatement($sql, [$excludePid]);
        $matches = [];

        while ($p = sqlFetchArray($res)) {
            $score = 0;
            $reasons = [];

            // 1. National ID (SS / NIC) match
            if (!empty($ss) && !empty($p['ss']) && $ss === $p['ss']) {
                $score += 99;
                $reasons[] = 'National ID Exact Match';
            }

            // 2. Exact First and Last Name
            $nameExact = (strcasecmp($p['fname'], $fname) === 0 && strcasecmp($p['lname'], $lname) === 0);
            if ($nameExact) {
                $score += 50;
                $reasons[] = 'Full Name Match';
            } elseif (!empty($fname) && !empty($lname) && soundex($p['fname']) === soundex($fname) && soundex($p['lname']) === soundex($lname)) {
                $score += 35;
                $reasons[] = 'Phonetic Soundex Name Match';
            }

            // 3. Date of Birth match
            if (!empty($dob) && !empty($p['DOB']) && $p['DOB'] === $dob) {
                $score += 40;
                $reasons[] = 'Date of Birth Exact Match';
            }

            // 4. Phone match
            if (!empty($phone) && !empty($p['phone_cell']) && preg_replace('/\D/', '', $p['phone_cell']) === preg_replace('/\D/', '', $phone)) {
                $score += 30;
                $reasons[] = 'Mobile Phone Match';
            }

            if ($score >= 40) {
                $p['match_score'] = min($score, 99);
                $p['reasons'] = implode(', ', $reasons);
                $matches[] = $p;
            }
        }

        usort($matches, function ($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });

        return $matches;
    }

    /**
     * Get detailed patient profile for side-by-side comparison
     */
    public static function getPatientProfile(int $pid): ?array
    {
        $pt = sqlQuery("SELECT * FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$pid]);
        if (!$pt) {
            return null;
        }

        // Count encounters
        $encRow = sqlQuery("SELECT COUNT(*) as total FROM `form_encounter` WHERE `pid` = ?", [$pid]);
        $pt['total_encounters'] = (int)($encRow['total'] ?? 0);

        // Count vitals
        $vitRow = sqlQuery("SELECT COUNT(*) as total FROM `form_vitals` WHERE `pid` = ?", [$pid]);
        $pt['total_vitals'] = (int)($vitRow['total'] ?? 0);

        // Count orders
        $ordRow = sqlQuery("SELECT COUNT(*) as total FROM `procedure_order` WHERE `patient_id` = ?", [$pid]);
        $pt['total_orders'] = (int)($ordRow['total'] ?? 0);

        // Count prescriptions
        $rxRow = sqlQuery("SELECT COUNT(*) as total FROM `prescriptions` WHERE `patient_id` = ?", [$pid]);
        $pt['total_prescriptions'] = (int)($rxRow['total'] ?? 0);

        return $pt;
    }

    /**
     * Full Patient-to-Patient Merge with complete snapshot for reversible unmerge
     * Conforms to Specification: "Merge and unmerge records with an audit trail"
     */
    public static function mergePatients(int $sourcePid, int $targetPid, int $staffUserId, string $reason = ''): array
    {
        if ($sourcePid <= 0 || $targetPid <= 0 || $sourcePid === $targetPid) {
            return ['success' => false, 'error' => 'Source and Target PID must be distinct valid patients.'];
        }

        $source = sqlQuery("SELECT `pid`, `fname`, `lname`, `pubpid`, `status` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$sourcePid]);
        $target = sqlQuery("SELECT `pid`, `fname`, `lname`, `pubpid`, `status` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$targetPid]);

        if (!$source || !$target) {
            return ['success' => false, 'error' => 'One or both patient records do not exist.'];
        }

        if ($source['status'] === 'merged_inactive') {
            return ['success' => false, 'error' => 'Source patient is already inactive/merged.'];
        }

        // 1. Gather all entity IDs from source patient for the unmerge snapshot
        $encounters = [];
        $rEnc = sqlStatement("SELECT `id` FROM `form_encounter` WHERE `pid` = ?", [$sourcePid]);
        while ($row = sqlFetchArray($rEnc)) { $encounters[] = (int)$row['id']; }

        $vitals = [];
        $rVit = sqlStatement("SELECT `id` FROM `form_vitals` WHERE `pid` = ?", [$sourcePid]);
        while ($row = sqlFetchArray($rVit)) { $vitals[] = (int)$row['id']; }

        $orders = [];
        $rOrd = sqlStatement("SELECT `procedure_order_id` FROM `procedure_order` WHERE `patient_id` = ?", [$sourcePid]);
        while ($row = sqlFetchArray($rOrd)) { $orders[] = (int)$row['procedure_order_id']; }

        $prescriptions = [];
        $rRx = sqlStatement("SELECT `id` FROM `prescriptions` WHERE `patient_id` = ?", [$sourcePid]);
        while ($row = sqlFetchArray($rRx)) { $prescriptions[] = (int)$row['id']; }

        $documents = [];
        $rDoc = sqlStatement("SELECT `id` FROM `documents` WHERE `foreign_id` = ?", [$sourcePid]);
        while ($row = sqlFetchArray($rDoc)) { $documents[] = (int)$row['id']; }

        $snapshot = [
            'source_pid' => $sourcePid,
            'target_pid' => $targetPid,
            'encounters' => $encounters,
            'vitals' => $vitals,
            'orders' => $orders,
            'prescriptions' => $prescriptions,
            'documents' => $documents,
            'original_source_status' => $source['status']
        ];

        // 2. Transfer entities to targetPid
        sqlStatement("UPDATE `form_encounter` SET `pid` = ? WHERE `pid` = ?", [$targetPid, $sourcePid]);
        sqlStatement("UPDATE `form_vitals` SET `pid` = ? WHERE `pid` = ?", [$targetPid, $sourcePid]);
        sqlStatement("UPDATE `procedure_order` SET `patient_id` = ? WHERE `patient_id` = ?", [$targetPid, $sourcePid]);
        sqlStatement("UPDATE `prescriptions` SET `patient_id` = ? WHERE `patient_id` = ?", [$targetPid, $sourcePid]);
        sqlStatement("UPDATE `documents` SET `foreign_id` = ? WHERE `foreign_id` = ?", [$targetPid, $sourcePid]);

        // 3. Inactivate source patient
        sqlStatement("UPDATE `patient_data` SET `status` = 'merged_inactive' WHERE `pid` = ?", [$sourcePid]);

        // 4. Record in hms_merge_log
        $mergeLogId = (int) sqlInsert(
            "INSERT INTO `hms_merge_log`
             (`merge_type`, `source_pid`, `target_pid`, `snapshot_data`, `reason`, `status`, `merged_by`, `merged_at`)
             VALUES ('patient_patient', ?, ?, ?, ?, 'merged', ?, NOW())",
            [$sourcePid, $targetPid, json_encode($snapshot), $reason ?: 'Duplicate patient chart consolidation', $staffUserId]
        );

        // 5. Publish to internal event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('patient_merged', ?, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$targetPid, $staffUserId, json_encode([
                'merge_log_id' => $mergeLogId,
                'source_pid' => $sourcePid,
                'target_pid' => $targetPid,
                'source_name' => $source['fname'] . ' ' . $source['lname'],
                'target_name' => $target['fname'] . ' ' . $target['lname']
            ])]
        );

        return [
            'success' => true,
            'merge_log_id' => $mergeLogId,
            'message' => "Successfully merged {$source['fname']} {$source['lname']} (PID #$sourcePid) into {$target['fname']} {$target['lname']} (PID #$targetPid). Audit snapshot logged."
        ];
    }

    /**
     * Safe UNMERGE reversing the merge while keeping the audit trail intact
     * Conforms to Acceptance Test: "A merge followed by an unmerge leaves the audit trail intact"
     */
    public static function unmergePatients(int $mergeLogId, int $staffUserId, string $reason = ''): array
    {
        $log = sqlQuery("SELECT * FROM `hms_merge_log` WHERE `id` = ? AND `status` = 'merged' LIMIT 1", [$mergeLogId]);
        if (!$log) {
            return ['success' => false, 'error' => 'Merge log record not found or already unmerged.'];
        }

        $snapshot = json_decode($log['snapshot_data'], true);
        if (!$snapshot) {
            return ['success' => false, 'error' => 'Malformed snapshot data in merge log.'];
        }

        $sourcePid = (int)$snapshot['source_pid'];
        $targetPid = (int)$snapshot['target_pid'];

        // 1. Revert Encounters
        if (!empty($snapshot['encounters'])) {
            $inClause = implode(',', array_map('intval', $snapshot['encounters']));
            sqlStatement("UPDATE `form_encounter` SET `pid` = ? WHERE `id` IN ($inClause)", [$sourcePid]);
        }

        // 2. Revert Vitals
        if (!empty($snapshot['vitals'])) {
            $inClause = implode(',', array_map('intval', $snapshot['vitals']));
            sqlStatement("UPDATE `form_vitals` SET `pid` = ? WHERE `id` IN ($inClause)", [$sourcePid]);
        }

        // 3. Revert Orders
        if (!empty($snapshot['orders'])) {
            $inClause = implode(',', array_map('intval', $snapshot['orders']));
            sqlStatement("UPDATE `procedure_order` SET `patient_id` = ? WHERE `procedure_order_id` IN ($inClause)", [$sourcePid]);
        }

        // 4. Revert Prescriptions
        if (!empty($snapshot['prescriptions'])) {
            $inClause = implode(',', array_map('intval', $snapshot['prescriptions']));
            sqlStatement("UPDATE `prescriptions` SET `patient_id` = ? WHERE `id` IN ($inClause)", [$sourcePid]);
        }

        // 5. Revert Documents
        if (!empty($snapshot['documents'])) {
            $inClause = implode(',', array_map('intval', $snapshot['documents']));
            sqlStatement("UPDATE `documents` SET `foreign_id` = ? WHERE `id` IN ($inClause)", [$sourcePid]);
        }

        // 6. Restore original source patient status to active
        $origStatus = !empty($snapshot['original_source_status']) ? $snapshot['original_source_status'] : 'active';
        sqlStatement("UPDATE `patient_data` SET `status` = ? WHERE `pid` = ?", [$origStatus, $sourcePid]);

        // 7. Update merge log: status='unmerged' while preserving merge records
        sqlStatement(
            "UPDATE `hms_merge_log` SET 
             `status` = 'unmerged',
             `unmerged_by` = ?,
             `unmerged_at` = NOW(),
             `unmerge_reason` = ?
             WHERE `id` = ?",
            [$staffUserId, $reason ?: 'Reversed mistaken patient merge', $mergeLogId]
        );

        // 8. Publish to internal event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('patient_unmerged', ?, 0, ?, NOW(), NOW(), ?, 'processed')",
            [$sourcePid, $staffUserId, json_encode([
                'merge_log_id' => $mergeLogId,
                'source_pid' => $sourcePid,
                'target_pid' => $targetPid,
                'unmerge_reason' => $reason
            ])]
        );

        return [
            'success' => true,
            'message' => "Successfully unmerged PID #$sourcePid from PID #$targetPid. All clinical records restored to original chart; audit trail preserved."
        ];
    }

    /**
     * Get all merge logs (active and unmerged) for audit inspection
     */
    public static function getMergeHistory(): array
    {
        $sql = "SELECT ml.*,
                       p1.fname as src_fname, p1.lname as src_lname, p1.pubpid as src_pubpid,
                       p2.fname as tgt_fname, p2.lname as tgt_lname, p2.pubpid as tgt_pubpid,
                       u1.fname as merger_fname, u1.lname as merger_lname,
                       u2.fname as unmerger_fname, u2.lname as unmerger_lname
                FROM `hms_merge_log` ml
                LEFT JOIN `patient_data` p1 ON ml.`source_pid` = p1.`pid`
                LEFT JOIN `patient_data` p2 ON ml.`target_pid` = p2.`pid`
                LEFT JOIN `users` u1 ON ml.`merged_by` = u1.`id`
                LEFT JOIN `users` u2 ON ml.`unmerged_by` = u2.`id`
                ORDER BY ml.`id` DESC";
        $rows = sqlStatement($sql);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Capture Patient Consent (General Inpatient, Financial Guarantee, Release) with optional scan upload
     */
    public static function captureConsent(array $data, int $staffUserId, ?array $file = null): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $type = $data['consent_type'] ?? 'general_treatment';
        $signer = trim($data['signer_name'] ?? '');
        $relation = trim($data['signer_relationship'] ?? 'self');
        $notes = trim($data['notes'] ?? '');

        if ($pid <= 0 || empty($signer)) {
            return ['success' => false, 'error' => 'Patient PID and Signer Name required.'];
        }

        // Ensure scan_path column exists on pre-existing installations
        $colCheck = sqlQuery("SHOW COLUMNS FROM `hms_consent` LIKE 'scan_path'");
        if (empty($colCheck)) {
            sqlStatement("ALTER TABLE `hms_consent` ADD COLUMN `scan_path` VARCHAR(255) DEFAULT NULL AFTER `signed_at`");
        }

        $scanPath = null;
        if ($file && isset($file['error']) && (int)$file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name'])) {
            $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
            if (!in_array($ext, $allowed, true)) {
                return ['success' => false, 'error' => 'Invalid consent scan format. Allowed: PDF, PNG, JPG.'];
            }
            $siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_SESSION['site_id'] ?? 'default')) ?: 'default';
            $uploadDir = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . $siteId . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'carelio_consents';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $filename = 'consent_pid' . $pid . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $scanPath = 'sites/' . $siteId . '/documents/carelio_consents/' . $filename;
            }
        }

        $id = (int) sqlInsert(
            "INSERT INTO `hms_consent` 
             (`pid`, `consent_type`, `status`, `signer_name`, `signer_relationship`, `witness_user_id`, `signed_at`, `scan_path`, `notes`)
             VALUES (?, ?, 'signed', ?, ?, ?, NOW(), ?, ?)",
            [$pid, $type, $signer, $relation, $staffUserId, $scanPath, $notes]
        );

        return [
            'success' => true,
            'consent_id' => $id,
            'scan_path' => $scanPath,
            'message' => $scanPath
                ? 'Patient consent form and scanned attachment saved.'
                : 'Patient consent form captured and timestamped.'
        ];
    }

    /**
     * Get Consents for a Patient
     */
    public static function getPatientConsents(int $pid): array
    {
        $sql = "SELECT c.*, u.fname as witness_fname, u.lname as witness_lname
                FROM `hms_consent` c
                LEFT JOIN `users` u ON c.`witness_user_id` = u.`id`
                WHERE c.`pid` = ?
                ORDER BY c.`id` DESC";
        $rows = sqlStatement($sql, [$pid]);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Get Insurance Verification Status for a Patient
     */
    public static function getInsuranceVerification(int $pid): array
    {
        $sql = "SELECT id.*, ic.name as insurer_name
                FROM `insurance_data` id
                LEFT JOIN `insurance_companies` ic ON id.`provider` = ic.`id`
                WHERE id.`pid` = ?
                ORDER BY FIELD(id.`type`, 'primary', 'secondary', 'tertiary'), id.`date` DESC";
        $rows = sqlStatement($sql, [$pid]);
        $items = [];
        $hasActive = false;
        $today = date('Y-m-d');
        while ($row = sqlFetchArray($rows)) {
            $dateEnd = $row['date_end'] ?? '';
            $isExpired = (!empty($dateEnd) && $dateEnd !== '0000-00-00' && $dateEnd < $today);
            $row['eligibility_status'] = $isExpired ? 'Expired' : 'Active / Eligible';
            if (!$isExpired) {
                $hasActive = true;
            }
            $items[] = $row;
        }

        if (empty($items)) {
            return [
                'has_insurance' => false,
                'plans' => [],
                'status' => 'Self-Pay / Cash Patient (No Active Policy)'
            ];
        }

        return [
            'has_insurance' => true,
            'plans' => $items,
            'status' => $hasActive ? 'Verified Active Coverage' : 'Coverage Expired (Self-Pay / Renewal Required)'
        ];
    }

    /**
     * Generate Registration Reports (Daily Volume, Duplicates, Unreconciled Temp)
     */
    public static function getRegistrationReports(): array
    {
        // 1. Unreconciled Temp IDs count
        $tempCountRow = sqlQuery("SELECT COUNT(*) as cnt FROM `hms_temp_identity` WHERE `status` = 'active'");
        $unreconciledCount = (int)($tempCountRow['cnt'] ?? 0);

        // 2. Registrations by day (last 7 days)
        $dailySql = "SELECT DATE(`date`) as reg_date, COUNT(*) as count 
                     FROM `patient_data` 
                     WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                     GROUP BY DATE(`date`) 
                     ORDER BY reg_date DESC";
        $dailyRows = sqlStatement($dailySql);
        $dailyVolume = [];
        while ($row = sqlFetchArray($dailyRows)) {
            $dailyVolume[] = $row;
        }

        // 2b. Registrations by day and site (from hms_event + fallback to current site)
        $currentSite = $_SESSION['site_id'] ?? 'default';
        $daySiteSql = "SELECT DATE(p.`date`) as reg_date,
                              COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.`payload`, '$.site_id')), ''), ?) as site_name,
                              COUNT(DISTINCT p.`pid`) as count
                       FROM `patient_data` p
                       LEFT JOIN `hms_event` e ON e.`pid` = p.`pid` AND e.`event_type` IN ('patient_registered', 'emergency_patient_registered')
                       WHERE p.`date` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       GROUP BY DATE(p.`date`), site_name
                       ORDER BY reg_date DESC, site_name ASC";
        $daySiteRows = sqlStatement($daySiteSql, [$currentSite]);
        $volumeByDayAndSite = [];
        while ($row = sqlFetchArray($daySiteRows)) {
            $volumeByDayAndSite[] = $row;
        }

        // 3. Suspected duplicate pairs in database
        $dupSql = "SELECT p1.pid as pid1, p1.fname as fname1, p1.lname as lname1, p1.DOB as dob1, p1.pubpid as pubpid1,
                          p2.pid as pid2, p2.fname as fname2, p2.lname as lname2, p2.DOB as dob2, p2.pubpid as pubpid2
                   FROM `patient_data` p1
                   INNER JOIN `patient_data` p2 ON (
                       p1.pid < p2.pid 
                       AND p1.status != 'merged_inactive' 
                       AND p2.status != 'merged_inactive'
                       AND p1.fname = p2.fname 
                       AND p1.lname = p2.lname
                   )
                   LIMIT 20";
        $dupRows = sqlStatement($dupSql);
        $suspectedDuplicates = [];
        while ($row = sqlFetchArray($dupRows)) {
            $suspectedDuplicates[] = $row;
        }

        return [
            'unreconciled_temp_count' => $unreconciledCount,
            'daily_volume' => $dailyVolume,
            'volume_by_day_and_site' => $volumeByDayAndSite,
            'suspected_duplicates' => $suspectedDuplicates
        ];
    }
}
