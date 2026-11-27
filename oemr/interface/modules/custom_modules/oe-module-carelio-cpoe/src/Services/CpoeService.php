<?php

namespace OpenEMR\Modules\CarelioCpoe\Services;

use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;

class CpoeService
{
    private static function publishEvent(string $eventType, int $pid, ?int $encounterId, int $actorId, array $payload): void
    {
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `facility_id`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES (?, ?, ?, 1, ?, NOW(), NOW(), ?, 'published')",
            [$eventType, $pid, $encounterId, $actorId, json_encode($payload)]
        );
    }

    private static function getUsername(int $userId): string
    {
        $row = sqlQuery("SELECT `username` FROM `users` WHERE `id` = ? LIMIT 1", [$userId]);
        return !empty($row['username']) ? (string)$row['username'] : 'admin';
    }

    /**
     * Ensure CPOE tables and baseline Order Sets / Panic Rules exist
     */
    public static function ensureSchemaAndDefaults(int $userId = 1): void
    {
        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `hms_order_set` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `set_code` VARCHAR(100) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `specialty` VARCHAR(100) NULL,
                `description` TEXT NULL,
                `created_by` BIGINT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_os_code` (`set_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `hms_order_set_item` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_set_id` BIGINT UNSIGNED NOT NULL,
                `item_type` ENUM('lab', 'imaging', 'medication', 'nursing') NOT NULL DEFAULT 'lab',
                `item_code` VARCHAR(64) NOT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `dosage_or_instructions` VARCHAR(255) NULL,
                `priority` ENUM('routine', 'urgent', 'stat') NOT NULL DEFAULT 'routine',
                `is_high_risk` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT NOT NULL DEFAULT 0,
                INDEX `idx_osi_set` (`order_set_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `hms_alert_rule` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `rule_code` VARCHAR(100) NOT NULL UNIQUE,
                `test_code` VARCHAR(64) NOT NULL,
                `test_name` VARCHAR(150) NOT NULL,
                `panic_low` DECIMAL(10,3) NULL,
                `panic_high` DECIMAL(10,3) NULL,
                `units` VARCHAR(31) NULL,
                `escalation_minutes` INT NOT NULL DEFAULT 30,
                `escalate_to_user_id` BIGINT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_ar_test` (`test_code`),
                INDEX `idx_ar_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `hms_alert_ack` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `rule_id` BIGINT UNSIGNED NULL,
                `procedure_result_id` BIGINT NULL,
                `pid` BIGINT NOT NULL,
                `encounter` BIGINT NULL,
                `test_name` VARCHAR(150) NOT NULL,
                `result_value` VARCHAR(100) NOT NULL,
                `severity` ENUM('critical', 'abnormal', 'post_discharge') NOT NULL DEFAULT 'critical',
                `target_user_id` BIGINT NOT NULL,
                `escalated_to_user_id` BIGINT NULL,
                `status` ENUM('unacknowledged', 'acknowledged', 'escalated') NOT NULL DEFAULT 'unacknowledged',
                `triggered_at` DATETIME NOT NULL,
                `due_by` DATETIME NOT NULL,
                `acknowledged_by` BIGINT NULL,
                `acknowledged_at` DATETIME NULL,
                `escalated_at` DATETIME NULL,
                `ack_notes` TEXT NULL,
                INDEX `idx_aa_status` (`status`),
                INDEX `idx_aa_target` (`target_user_id`),
                INDEX `idx_aa_due` (`due_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `hms_cpoe_cosign_queue` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `pid` BIGINT NOT NULL,
                `encounter` BIGINT NOT NULL,
                `order_type` ENUM('procedure', 'medication') NOT NULL DEFAULT 'procedure',
                `order_ref_id` BIGINT NULL,
                `order_summary` VARCHAR(255) NOT NULL,
                `ordered_by` BIGINT NOT NULL,
                `attending_id` BIGINT NULL,
                `status` ENUM('pending_cosign', 'cosigned', 'rejected') NOT NULL DEFAULT 'pending_cosign',
                `cosigned_by` BIGINT NULL,
                `cosigned_at` DATETIME NULL,
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_cs_status` (`status`),
                INDEX `idx_cs_pid` (`pid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Ensure ContentApprovalService baseline rows exist
        if (class_exists(ContentApprovalService::class)) {
            ContentApprovalService::getAll();
        }

        // Seed baseline Order Sets matching hms_content_approval
        $sets = [
            [
                'set_code' => 'ORD-SEPSIS-HOUR1',
                'title' => 'Adult Sepsis Resuscitation Bundle (Hour-1 Protocol)',
                'specialty' => 'Emergency / Critical Care',
                'description' => '1-click Hour-1 Sepsis bundle: lactate, blood cultures, broad-spectrum IV antibiotics, and crystalloid bolus.',
                'items' => [
                    ['lab', '2524-7', 'Serum Lactate STAT', 'Draw STAT venous/arterial sample', 'stat', 0],
                    ['lab', '600-7', 'Blood Cultures x 2 Sites', 'Collect before first antibiotic dose', 'stat', 0],
                    ['medication', 'RX-PIPTAZO', 'Piperacillin/Tazobactam 4.5g IV', '4.5g IV Q6H STAT', 'stat', 1],
                    ['medication', 'RX-NS-BOLUS', '0.9% Sodium Chloride 30 mL/kg IV', 'Infuse over 1 hour for hypotension', 'stat', 0],
                ]
            ],
            [
                'set_code' => 'ORD-DKA-ADULT',
                'title' => 'Adult Diabetic Ketoacidosis (DKA) Management Protocol',
                'specialty' => 'Internal Medicine',
                'description' => 'Standardized DKA resuscitation with IV fluids, regular insulin infusion, and Q2H electrolytes.',
                'items' => [
                    ['medication', 'RX-NS-1L', '0.9% Normal Saline 1000 mL/hr', 'Initial 1L bolus over 1 hr', 'stat', 0],
                    ['medication', 'RX-INSULIN-IV', 'Regular Insulin IV Infusion 0.1 units/kg/hr', 'High-risk continuous infusion; check K+ first', 'stat', 1],
                    ['lab', '2823-3', 'Serum Potassium & Electrolytes Q2H', 'Monitor K+ before & during insulin', 'urgent', 0],
                    ['nursing', 'NURS-CBG-Q1H', 'Hourly Capillary Blood Glucose (CBG)', 'Target drop 3-4 mmol/L per hr', 'urgent', 0],
                ]
            ],
        ];

        foreach ($sets as $s) {
            $row = sqlQuery("SELECT `id` FROM `hms_order_set` WHERE `set_code` = ? LIMIT 1", [$s['set_code']]);
            if (!$row) {
                $setId = (int)sqlInsert(
                    "INSERT INTO `hms_order_set` (`set_code`, `title`, `specialty`, `description`, `created_by`) VALUES (?, ?, ?, ?, ?)",
                    [$s['set_code'], $s['title'], $s['specialty'], $s['description'], $userId]
                );
                foreach ($s['items'] as $idx => $it) {
                    sqlInsert(
                        "INSERT INTO `hms_order_set_item` (`order_set_id`, `item_type`, `item_code`, `item_name`, `dosage_or_instructions`, `priority`, `is_high_risk`, `sort_order`)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$setId, $it[0], $it[1], $it[2], $it[3], $it[4], $it[5], ($idx + 1) * 10]
                    );
                }
            }
        }

        // Seed baseline Panic Alert Rules
        $rules = [
            ['ALERT-PANIC-POTASSIUM', '2823-3', 'Serum Potassium (K+)', 2.800, 6.500, 'mmol/L', 30],
            ['ALERT-PANIC-GLUCOSE', '2345-7', 'Serum Glucose', 2.500, 25.000, 'mmol/L', 30],
            ['ALERT-PANIC-HEMOGLOBIN', '718-7', 'Hemoglobin (Hb)', 6.500, 20.000, 'g/dL', 30],
        ];
        foreach ($rules as $rl) {
            $ex = sqlQuery("SELECT `id` FROM `hms_alert_rule` WHERE `rule_code` = ? LIMIT 1", [$rl[0]]);
            if (!$ex) {
                sqlInsert(
                    "INSERT INTO `hms_alert_rule` (`rule_code`, `test_code`, `test_name`, `panic_low`, `panic_high`, `units`, `escalation_minutes`, `escalate_to_user_id`, `is_active`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)",
                    $rl
                );
            }
        }
    }

    /**
     * List all Order Sets with governance status from hms_content_approval and items
     */
    public static function getOrderSets(): array
    {
        $rows = sqlStatement("SELECT * FROM `hms_order_set` ORDER BY `id` ASC");
        $list = [];
        while ($s = sqlFetchArray($rows)) {
            $appr = sqlQuery(
                "SELECT `status`, `version` FROM `hms_content_approval`
                 WHERE `content_code` = ? AND `status` != 'superseded'
                 ORDER BY `id` DESC LIMIT 1",
                [$s['set_code']]
            );
            $s['approval_status'] = $appr['status'] ?? 'draft';
            $s['version'] = $appr['version'] ?? '1.0';

            $itemRows = sqlStatement("SELECT * FROM `hms_order_set_item` WHERE `order_set_id` = ? ORDER BY `sort_order` ASC", [$s['id']]);
            $items = [];
            while ($it = sqlFetchArray($itemRows)) {
                $items[] = $it;
            }
            $s['items'] = $items;
            $list[] = $s;
        }
        return $list;
    }

    /**
     * Apply a 1-click Clinical Order Set to an encounter.
     * Enforces hms_content_approval gate (draft order sets cannot be used).
     * High-risk items ordered by residents are routed to the Co-Signature Queue.
     */
    public static function applyOrderSet(int $orderSetId, int $pid, int $encounter, int $userId, bool $isResident = false): array
    {
        if ($orderSetId <= 0 || $pid <= 0 || $encounter <= 0) {
            return ['success' => false, 'error' => 'Order Set, Patient, and Encounter are required.'];
        }

        $set = sqlQuery("SELECT * FROM `hms_order_set` WHERE `id` = ? LIMIT 1", [$orderSetId]);
        if (!$set) {
            return ['success' => false, 'error' => 'Order Set not found.'];
        }

        // Governance Gate: Draft/Pending/Rejected order sets cannot be used
        if (!ContentApprovalService::isApproved($set['set_code'])) {
            $appr = sqlQuery(
                "SELECT `status` FROM `hms_content_approval` WHERE `content_code` = ? ORDER BY `id` DESC LIMIT 1",
                [$set['set_code']]
            );
            $st = strtoupper($appr['status'] ?? 'DRAFT');
            return [
                'success' => false,
                'governance_blocked' => true,
                'error' => "Governance Block: Order Set '{$set['title']}' ({$set['set_code']}) is currently $st and cannot be applied until approved by the Medical Director."
            ];
        }

        $itemRows = sqlStatement("SELECT * FROM `hms_order_set_item` WHERE `order_set_id` = ? ORDER BY `sort_order` ASC", [$orderSetId]);
        $placedCount = 0;
        $cosignCount = 0;

        while ($it = sqlFetchArray($itemRows)) {
            $needsCosign = $isResident && ((int)$it['is_high_risk'] === 1);

            if ($it['item_type'] === 'medication') {
                if ($needsCosign) {
                    sqlInsert(
                        "INSERT INTO `hms_cpoe_cosign_queue` (`pid`, `encounter`, `order_type`, `order_summary`, `ordered_by`, `status`, `notes`)
                         VALUES (?, ?, 'medication', ?, ?, 'pending_cosign', ?)",
                        [$pid, $encounter, $it['item_name'] . ' — ' . ($it['dosage_or_instructions'] ?? ''), $userId, 'High-risk medication from ' . $set['set_code']]
                    );
                    $cosignCount++;
                } else {
                    sqlInsert(
                        "INSERT INTO `prescriptions` (`patient_id`, `provider_id`, `encounter`, `date_added`, `date_modified`, `start_date`, `drug`, `dosage`, `note`, `active`)
                         VALUES (?, ?, ?, NOW(), NOW(), CURDATE(), ?, ?, ?, 1)",
                        [$pid, $userId, $encounter, $it['item_name'], $it['dosage_or_instructions'] ?? 'As directed', "Order Set: {$set['set_code']}"]
                    );
                    $placedCount++;
                }
            } else {
                $status = $needsCosign ? 'pending' : 'routed';
                $orderId = (int)sqlInsert(
                    "INSERT INTO `procedure_order` (`provider_id`, `patient_id`, `encounter_id`, `date_ordered`, `order_priority`, `order_status`, `procedure_order_type`, `clinical_hx`)
                     VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)",
                    [$userId, $pid, $encounter, $it['priority'], $status, $it['item_type'], "Order Set: {$set['set_code']}"]
                );
                sqlInsert(
                    "INSERT INTO `procedure_order_code` (`procedure_order_id`, `procedure_order_seq`, `procedure_code`, `procedure_name`, `procedure_order_title`)
                     VALUES (?, 1, ?, ?, ?)",
                    [$orderId, $it['item_code'], $it['item_name'], $it['item_name']]
                );

                if ($needsCosign) {
                    sqlInsert(
                        "INSERT INTO `hms_cpoe_cosign_queue` (`pid`, `encounter`, `order_type`, `order_ref_id`, `order_summary`, `ordered_by`, `status`, `notes`)
                         VALUES (?, ?, 'procedure', ?, ?, ?, 'pending_cosign', ?)",
                        [$pid, $encounter, $orderId, $it['item_name'], $userId, 'High-risk order from ' . $set['set_code']]
                    );
                    $cosignCount++;
                } else {
                    $placedCount++;
                }
            }
        }

        self::publishEvent('order_set_applied', $pid, $encounter, $userId, [
            'set_code' => $set['set_code'],
            'orders_placed' => $placedCount,
            'pending_cosign' => $cosignCount
        ]);

        $msg = "Applied '{$set['title']}': $placedCount orders released";
        if ($cosignCount > 0) {
            $msg .= ", $cosignCount high-risk order(s) queued for Attending Co-Signature.";
        } else {
            $msg .= ".";
        }
        return ['success' => true, 'message' => $msg];
    }

    /**
     * Place a high-risk order as Resident (waits for Attending Co-Signature)
     */
    public static function placeResidentHighRiskOrder(array $data, int $residentId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $encounter = (int)($data['encounter'] ?? 0);
        $orderType = ($data['order_type'] ?? 'medication') === 'procedure' ? 'procedure' : 'medication';
        $summary = trim($data['order_summary'] ?? '');
        $attendingId = !empty($data['attending_id']) ? (int)$data['attending_id'] : 1;

        if ($pid <= 0 || $encounter <= 0 || $summary === '') {
            return ['success' => false, 'error' => 'Patient, Encounter, and Order Details are required.'];
        }

        $orderRefId = null;
        if ($orderType === 'procedure') {
            $orderRefId = (int)sqlInsert(
                "INSERT INTO `procedure_order` (`provider_id`, `patient_id`, `encounter_id`, `date_ordered`, `order_priority`, `order_status`, `procedure_order_type`, `clinical_hx`)
                 VALUES (?, ?, ?, NOW(), 'stat', 'pending', 'lab', 'Awaiting Attending Co-Signature')",
                [$residentId, $pid, $encounter]
            );
            sqlInsert(
                "INSERT INTO `procedure_order_code` (`procedure_order_id`, `procedure_order_seq`, `procedure_code`, `procedure_name`, `procedure_order_title`)
                 VALUES (?, 1, 'HIGH-RISK', ?, ?)",
                [$orderRefId, $summary, $summary]
            );
        }

        $csId = (int)sqlInsert(
            "INSERT INTO `hms_cpoe_cosign_queue` (`pid`, `encounter`, `order_type`, `order_ref_id`, `order_summary`, `ordered_by`, `attending_id`, `status`)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_cosign')",
            [$pid, $encounter, $orderType, $orderRefId, $summary, $residentId, $attendingId]
        );

        self::publishEvent('order_pending_cosign', $pid, $encounter, $residentId, [
            'cosign_id' => $csId,
            'order_summary' => $summary
        ]);

        return ['success' => true, 'id' => $csId, 'message' => "High-risk order queued (#$csId) awaiting Attending Physician co-signature."];
    }

    /**
     * Attending Physician co-signs or rejects a Resident's high-risk order
     */
    public static function cosignOrder(int $cosignId, int $attendingId, string $decision = 'cosigned', string $notes = ''): array
    {
        $row = sqlQuery("SELECT * FROM `hms_cpoe_cosign_queue` WHERE `id` = ? LIMIT 1", [$cosignId]);
        if (!$row) {
            return ['success' => false, 'error' => 'Co-signature request not found.'];
        }
        if ($row['status'] !== 'pending_cosign') {
            return ['success' => false, 'error' => 'Order has already been processed.'];
        }

        $newStatus = $decision === 'rejected' ? 'rejected' : 'cosigned';
        sqlStatement(
            "UPDATE `hms_cpoe_cosign_queue` SET `status` = ?, `cosigned_by` = ?, `cosigned_at` = NOW(), `notes` = ? WHERE `id` = ?",
            [$newStatus, $attendingId, $notes ?: null, $cosignId]
        );

        if ($newStatus === 'cosigned') {
            if ($row['order_type'] === 'medication') {
                sqlInsert(
                    "INSERT INTO `prescriptions` (`patient_id`, `provider_id`, `encounter`, `date_added`, `date_modified`, `start_date`, `drug`, `note`, `active`)
                     VALUES (?, ?, ?, NOW(), NOW(), CURDATE(), ?, ?, 1)",
                    [(int)$row['pid'], $attendingId, (int)$row['encounter'], $row['order_summary'], "Co-signed by Attending #$attendingId"]
                );
            } elseif (!empty($row['order_ref_id'])) {
                sqlStatement("UPDATE `procedure_order` SET `order_status` = 'routed' WHERE `procedure_order_id` = ?", [(int)$row['order_ref_id']]);
            }
        } elseif (!empty($row['order_ref_id'])) {
            sqlStatement("UPDATE `procedure_order` SET `order_status` = 'canceled' WHERE `procedure_order_id` = ?", [(int)$row['order_ref_id']]);
        }

        self::publishEvent('order_' . $newStatus, (int)$row['pid'], (int)$row['encounter'], $attendingId, [
            'cosign_id' => $cosignId,
            'order_summary' => $row['order_summary']
        ]);

        return ['success' => true, 'message' => "Order #$cosignId marked $newStatus."];
    }

    /**
     * List Co-Signature Queue
     */
    public static function getCosignQueue(): array
    {
        $rows = sqlStatement(
            "SELECT c.*, p.fname, p.lname, p.pubpid,
                    CONCAT(u1.fname, ' ', u1.lname) AS resident_name,
                    CONCAT(u2.fname, ' ', u2.lname) AS cosigner_name
             FROM `hms_cpoe_cosign_queue` c
             LEFT JOIN `patient_data` p ON p.pid = c.pid
             LEFT JOIN `users` u1 ON u1.id = c.ordered_by
             LEFT JOIN `users` u2 ON u2.id = c.cosigned_by
             ORDER BY FIELD(c.status, 'pending_cosign', 'cosigned', 'rejected'), c.id DESC
             LIMIT 50"
        );
        $list = [];
        while ($r = sqlFetchArray($rows)) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * Record a lab/test result into OpenEMR's procedure_report + procedure_result,
     * evaluate panic rules (hms_alert_rule), and route post-discharge results.
     */
    public static function recordAndEvaluateResult(array $data, int $userId): array
    {
        $pid = (int)($data['pid'] ?? 0);
        $encounter = (int)($data['encounter'] ?? 0);
        $orderId = !empty($data['procedure_order_id']) ? (int)$data['procedure_order_id'] : null;
        $testCode = trim($data['test_code'] ?? '');
        $testName = trim($data['test_name'] ?? '');
        $resultVal = trim($data['result_value'] ?? '');
        $units = trim($data['units'] ?? '');
        $isPostDischarge = !empty($data['is_post_discharge']);

        if ($pid <= 0 || $testCode === '' || $resultVal === '') {
            return ['success' => false, 'error' => 'Patient, Test Code, and Result Value are required.'];
        }

        // Resolve accountable clinician from encounter (or fallback to current user)
        $targetUserId = $userId;
        if ($encounter > 0) {
            $encRow = sqlQuery("SELECT `provider_id`, `date_end` FROM `form_encounter` WHERE `encounter` = ? LIMIT 1", [$encounter]);
            if (!empty($encRow['provider_id'])) {
                $targetUserId = (int)$encRow['provider_id'];
            }
            if (!empty($encRow['date_end'])) {
                $isPostDischarge = true;
            }
        }
        if (!$isPostDischarge && $pid > 0) {
            $dischEvt = sqlQuery("SELECT `id` FROM `hms_event` WHERE `pid` = ? AND `event_type` = 'discharge' LIMIT 1", [$pid]);
            if ($dischEvt) {
                $isPostDischarge = true;
            }
        }

        if ($orderId) {
            sqlStatement("UPDATE `procedure_order` SET `order_status` = 'complete' WHERE `procedure_order_id` = ?", [$orderId]);
        }

        $reportId = (int)sqlInsert(
            "INSERT INTO `procedure_report` (`procedure_order_id`, `procedure_order_seq`, `date_collected`, `date_report`, `source`, `report_status`, `review_status`)
             VALUES (?, 1, NOW(), NOW(), ?, 'complete', 'received')",
            [$orderId, $userId]
        );

        // Check panic rule
        $rule = sqlQuery("SELECT * FROM `hms_alert_rule` WHERE `test_code` = ? AND `is_active` = 1 LIMIT 1", [$testCode]);
        $isPanic = false;
        $abnormalFlag = 'no';
        if ($rule && is_numeric($resultVal)) {
            $num = (float)$resultVal;
            if ($rule['panic_low'] !== null && $num < (float)$rule['panic_low']) {
                $isPanic = true;
                $abnormalFlag = 'low';
            } elseif ($rule['panic_high'] !== null && $num > (float)$rule['panic_high']) {
                $isPanic = true;
                $abnormalFlag = 'high';
            }
            if ($testName === '') {
                $testName = $rule['test_name'];
            }
            if ($units === '') {
                $units = (string)($rule['units'] ?? '');
            }
        }
        if ($testName === '') {
            $testName = $testCode;
        }

        $resultId = (int)sqlInsert(
            "INSERT INTO `procedure_result` (`procedure_report_id`, `result_data_type`, `result_code`, `result_text`, `date`, `units`, `result`, `abnormal`, `result_status`)
             VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'final')",
            [$reportId, is_numeric($resultVal) ? 'N' : 'S', $testCode, $testName, $units, $resultVal, $abnormalFlag]
        );

        $alertMessages = [];
        if ($isPanic || $isPostDischarge) {
            $severity = $isPanic ? 'critical' : 'post_discharge';
            $escMins = $rule ? (int)$rule['escalation_minutes'] : 30;
            $escUserId = $rule && !empty($rule['escalate_to_user_id']) ? (int)$rule['escalate_to_user_id'] : 1;

            $alertId = (int)sqlInsert(
                "INSERT INTO `hms_alert_ack` (`rule_id`, `procedure_result_id`, `pid`, `encounter`, `test_name`, `result_value`, `severity`, `target_user_id`, `escalated_to_user_id`, `status`, `triggered_at`, `due_by`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'unacknowledged', NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE))",
                [$rule['id'] ?? null, $resultId, $pid, $encounter ?: null, $testName, "$resultVal $units", $severity, $targetUserId, $escUserId, $escMins]
            );

            $targetUsername = self::getUsername($targetUserId);
            $noteTitle = $isPanic ? "CRITICAL PANIC RESULT: $testName" : "Post-Discharge Result Routed: $testName";
            sqlInsert(
                "INSERT INTO `pnotes` (`date`, `body`, `pid`, `user`, `groupname`, `activity`, `authorized`, `title`, `assigned_to`, `message_status`)
                 VALUES (NOW(), ?, ?, ?, 'Default', 1, 1, ?, ?, 'New')",
                ["Result: $testName = $resultVal $units (Alert #$alertId)", $pid, self::getUsername($userId), $noteTitle, $targetUsername]
            );

            self::publishEvent($isPanic ? 'critical_result_alert' : 'post_discharge_result_routed', $pid, $encounter ?: null, $userId, [
                'alert_id' => $alertId,
                'result_id' => $resultId,
                'test_name' => $testName,
                'result_value' => "$resultVal $units",
                'target_user_id' => $targetUserId
            ]);

            if ($isPanic) {
                $alertMessages[] = "CRITICAL PANIC ALERT (#$alertId) triggered for $testName ($resultVal $units)!";
            }
            if ($isPostDischarge) {
                $alertMessages[] = "Post-discharge result routed to accountable clinician ($targetUsername).";
            }
        }

        return [
            'success' => true,
            'result_id' => $resultId,
            'message' => "Result saved (#$resultId)." . (!empty($alertMessages) ? ' ' . implode(' ', $alertMessages) : '')
        ];
    }

    /**
     * Acknowledge a critical / post-discharge alert
     */
    public static function acknowledgeAlert(int $alertId, int $userId, string $notes = ''): array
    {
        $row = sqlQuery("SELECT * FROM `hms_alert_ack` WHERE `id` = ? LIMIT 1", [$alertId]);
        if (!$row) {
            return ['success' => false, 'error' => 'Alert not found.'];
        }
        sqlStatement(
            "UPDATE `hms_alert_ack` SET `status` = 'acknowledged', `acknowledged_by` = ?, `acknowledged_at` = NOW(), `ack_notes` = ? WHERE `id` = ?",
            [$userId, $notes ?: 'Acknowledged by clinician', $alertId]
        );
        if (!empty($row['procedure_result_id'])) {
            sqlStatement(
                "UPDATE `procedure_report` pr
                 INNER JOIN `procedure_result` res ON res.procedure_report_id = pr.procedure_report_id
                 SET pr.review_status = 'reviewed'
                 WHERE res.procedure_result_id = ?",
                [(int)$row['procedure_result_id']]
            );
        }
        self::publishEvent('critical_alert_acknowledged', (int)$row['pid'], $row['encounter'] ? (int)$row['encounter'] : null, $userId, ['alert_id' => $alertId]);
        return ['success' => true, 'message' => "Alert #$alertId acknowledged."];
    }

    /**
     * Escalate unacknowledged alerts (either all overdue alerts or a specific alert)
     */
    public static function escalateAlert(?int $alertId, int $actorId): array
    {
        if ($alertId !== null && $alertId > 0) {
            $rows = sqlStatement("SELECT * FROM `hms_alert_ack` WHERE `id` = ? AND `status` = 'unacknowledged'", [$alertId]);
        } else {
            $rows = sqlStatement("SELECT * FROM `hms_alert_ack` WHERE `status` = 'unacknowledged' AND `due_by` <= NOW()");
        }

        $count = 0;
        while ($a = sqlFetchArray($rows)) {
            $escTo = !empty($a['escalated_to_user_id']) ? (int)$a['escalated_to_user_id'] : 1;
            sqlStatement(
                "UPDATE `hms_alert_ack` SET `status` = 'escalated', `escalated_at` = NOW() WHERE `id` = ?",
                [(int)$a['id']]
            );
            sqlInsert(
                "INSERT INTO `pnotes` (`date`, `body`, `pid`, `user`, `groupname`, `activity`, `authorized`, `title`, `assigned_to`, `message_status`)
                 VALUES (NOW(), ?, ?, ?, 'Default', 1, 1, ?, ?, 'New')",
                [
                    "ESCALATED: Unacknowledged critical result {$a['test_name']} = {$a['result_value']} (Alert #{$a['id']})",
                    (int)$a['pid'],
                    self::getUsername($actorId),
                    "ESCALATION: {$a['test_name']}",
                    self::getUsername($escTo)
                ]
            );
            self::publishEvent('critical_alert_escalated', (int)$a['pid'], $a['encounter'] ? (int)$a['encounter'] : null, $actorId, [
                'alert_id' => (int)$a['id'],
                'escalated_to' => $escTo
            ]);
            $count++;
        }

        return ['success' => true, 'escalated' => $count, 'message' => $count > 0 ? "Escalated $count unacknowledged critical alert(s) to Department Head." : "No overdue unacknowledged alerts to escalate."];
    }

    /**
     * List Critical & Post-Discharge Alerts
     */
    public static function getAlerts(): array
    {
        $rows = sqlStatement(
            "SELECT a.*, p.fname, p.lname, p.pubpid,
                    CONCAT(u1.fname, ' ', u1.lname) AS target_name,
                    CONCAT(u2.fname, ' ', u2.lname) AS escalated_to_name
             FROM `hms_alert_ack` a
             LEFT JOIN `patient_data` p ON p.pid = a.pid
             LEFT JOIN `users` u1 ON u1.id = a.target_user_id
             LEFT JOIN `users` u2 ON u2.id = a.escalated_to_user_id
             ORDER BY FIELD(a.status, 'escalated', 'unacknowledged', 'acknowledged'), a.id DESC
             LIMIT 50"
        );
        $list = [];
        while ($r = sqlFetchArray($rows)) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * List Panic Rules
     */
    public static function getAlertRules(): array
    {
        $rows = sqlStatement("SELECT * FROM `hms_alert_rule` ORDER BY `id` ASC");
        $list = [];
        while ($r = sqlFetchArray($rows)) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * List recent OpenEMR procedure orders
     */
    public static function getRecentOrders(): array
    {
        $rows = sqlStatement(
            "SELECT o.procedure_order_id, o.patient_id, o.encounter_id, o.date_ordered, o.order_priority, o.order_status, o.procedure_order_type,
                    c.procedure_code, c.procedure_name,
                    p.fname, p.lname, p.pubpid,
                    CONCAT(u.fname, ' ', u.lname) AS provider_name
             FROM `procedure_order` o
             LEFT JOIN `procedure_order_code` c ON c.procedure_order_id = o.procedure_order_id AND c.procedure_order_seq = 1
             LEFT JOIN `patient_data` p ON p.pid = o.patient_id
             LEFT JOIN `users` u ON u.id = o.provider_id
             ORDER BY o.procedure_order_id DESC
             LIMIT 40"
        );
        $list = [];
        while ($r = sqlFetchArray($rows)) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * Compute CPOE Reports (Spec Section 18.5):
     * - Unacknowledged Results
     * - Turnaround by Test
     * - Orders by Status
     */
    public static function getCpoeReports(): array
    {
        $unackRows = sqlStatement(
            "SELECT a.*, p.fname, p.lname, p.pubpid, CONCAT(u.fname, ' ', u.lname) AS target_name
             FROM `hms_alert_ack` a
             LEFT JOIN `patient_data` p ON p.pid = a.pid
             LEFT JOIN `users` u ON u.id = a.target_user_id
             WHERE a.status != 'acknowledged'
             ORDER BY a.triggered_at DESC"
        );
        $unack = [];
        while ($r = sqlFetchArray($unackRows)) {
            $unack[] = $r;
        }

        $tatRows = sqlStatement(
            "SELECT COALESCE(NULLIF(c.procedure_name, ''), res.result_text, 'Unknown Test') AS test_name,
                    COUNT(*) AS completed_count,
                    ROUND(AVG(TIMESTAMPDIFF(MINUTE, o.date_ordered, r.date_report)), 1) AS avg_tat_minutes
             FROM `procedure_report` r
             INNER JOIN `procedure_order` o ON o.procedure_order_id = r.procedure_order_id
             LEFT JOIN `procedure_order_code` c ON c.procedure_order_id = o.procedure_order_id AND c.procedure_order_seq = 1
             LEFT JOIN `procedure_result` res ON res.procedure_report_id = r.procedure_report_id
             WHERE o.date_ordered IS NOT NULL AND r.date_report IS NOT NULL
             GROUP BY test_name
             ORDER BY completed_count DESC"
        );
        $tat = [];
        while ($r = sqlFetchArray($tatRows)) {
            $tat[] = $r;
        }

        $statusRows = sqlStatement(
            "SELECT COALESCE(NULLIF(order_status, ''), 'unspecified') AS status, COUNT(*) AS cnt
             FROM `procedure_order`
             GROUP BY status
             ORDER BY cnt DESC"
        );
        $ordersByStatus = [];
        while ($r = sqlFetchArray($statusRows)) {
            $ordersByStatus[] = $r;
        }

        return [
            'unacknowledged_results' => $unack,
            'turnaround_by_test' => $tat,
            'orders_by_status' => $ordersByStatus,
        ];
    }
}
