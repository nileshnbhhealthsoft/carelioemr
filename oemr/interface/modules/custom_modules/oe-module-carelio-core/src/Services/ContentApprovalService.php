<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class ContentApprovalService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUPERSEDED = 'superseded';

    /**
     * Get all content approval records with user details
     */
    public static function getAll(?string $filterStatus = null): array
    {
        $params = [];
        $where = "";
        if (!empty($filterStatus)) {
            $where = "WHERE ca.`status` = ?";
            $params[] = $filterStatus;
        }

        $sql = "SELECT ca.*, 
                       u1.fname as author_fname, u1.lname as author_lname,
                       u2.fname as approver_fname, u2.lname as approver_lname
                FROM `hms_content_approval` ca
                LEFT JOIN `users` u1 ON ca.`author_id` = u1.`id`
                LEFT JOIN `users` u2 ON ca.`approved_by` = u2.`id`
                $where
                ORDER BY FIELD(ca.status, 'pending_approval', 'draft', 'approved', 'rejected'), ca.id DESC";

        $rows = sqlStatement($sql, $params);
        $items = [];
        while ($row = sqlFetchArray($rows)) {
            $items[] = $row;
        }

        if (empty($items) && empty($filterStatus)) {
            self::seedDefaultTemplates(1);
            return self::getAll();
        }

        return $items;
    }

    /**
     * Seed baseline clinical content from Carelio specification
     */
    public static function seedDefaultTemplates(int $authorId = 1): void
    {
        $defaults = [
            [
                'content_type' => 'form',
                'content_code' => 'FORM-INP-ROUNDS-SOAP',
                'version' => '1.0',
                'title' => 'Inpatient Daily Rounding SOAP Form (Multidisciplinary)',
                'payload' => json_encode([
                    'specialty' => 'Internal Medicine',
                    'sections' => ['Subjective', 'Objective / Vitals', 'Assessment & Problem List', 'Plan / Estimated Discharge Date']
                ]),
                'status' => self::STATUS_APPROVED,
                'approved_by' => $authorId,
                'approved_at' => date('Y-m-d H:i:s')
            ],
            [
                'content_type' => 'order_set',
                'content_code' => 'ORD-SEPSIS-HOUR1',
                'version' => '1.0',
                'title' => 'Adult Sepsis Resuscitation Bundle (Hour-1 Protocol)',
                'payload' => json_encode([
                    'orders' => [
                        'Serum Lactate STAT',
                        'Blood Cultures x 2 Sites before Antibiotics',
                        'Broad-Spectrum IV Antibiotics STAT (Pip/Tazo 4.5g IV)',
                        '30 mL/kg Crystalloid IV Fluid Bolus for Hypotension'
                    ]
                ]),
                'status' => self::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null
            ],
            [
                'content_type' => 'order_set',
                'content_code' => 'ORD-DKA-ADULT',
                'version' => '1.0',
                'title' => 'Adult Diabetic Ketoacidosis (DKA) Management Protocol',
                'payload' => json_encode([
                    'orders' => [
                        '0.9% Normal Saline 1000 mL/hr initial bolus',
                        'Regular Insulin IV Infusion 0.1 units/kg/hr',
                        'Hourly Bedside Capillary Blood Glucose (CBG)',
                        'Serum Electrolytes & Potassium monitoring Q2H'
                    ]
                ]),
                'status' => self::STATUS_DRAFT,
                'approved_by' => null,
                'approved_at' => null
            ],
            [
                'content_type' => 'alert_rule',
                'content_code' => 'ALERT-PANIC-POTASSIUM',
                'version' => '1.0',
                'title' => 'Critical Panic Lab Alert: Potassium > 6.5 or < 2.8 mmol/L',
                'payload' => json_encode([
                    'trigger' => 'LOINC-2823-3',
                    'panic_high' => 6.5,
                    'panic_low' => 2.8,
                    'escalation_time_minutes' => 30,
                    'escalate_to' => 'Head of Clinical Department'
                ]),
                'status' => self::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null
            ]
        ];

        foreach ($defaults as $d) {
            $sql = "INSERT INTO `hms_content_approval`
                    (`content_type`, `content_code`, `version`, `title`, `payload`, `status`, `author_id`, `approved_by`, `approved_at`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE `title` = VALUES(`title`)";
            sqlStatement($sql, [
                $d['content_type'],
                $d['content_code'],
                $d['version'],
                $d['title'],
                $d['payload'],
                $d['status'],
                $authorId,
                $d['approved_by'],
                $d['approved_at']
            ]);
        }
    }

    /**
     * Create a new draft item
     */
    public static function createItem(array $data, int $authorId): int
    {
        $sql = "INSERT INTO `hms_content_approval`
                (`content_type`, `content_code`, `version`, `title`, `payload`, `status`, `author_id`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        return (int) sqlInsert($sql, [
            $data['content_type'],
            $data['content_code'],
            $data['version'] ?? '1.0',
            $data['title'],
            is_array($data['payload']) ? json_encode($data['payload']) : ($data['payload'] ?? ''),
            self::STATUS_DRAFT,
            $authorId
        ]);
    }

    /**
     * Submit draft for Medical Director approval
     */
    public static function submitForApproval(int $id): bool
    {
        $sql = "UPDATE `hms_content_approval` SET `status` = ? WHERE `id` = ?";
        return sqlStatement($sql, [self::STATUS_PENDING, $id]) !== false;
    }

    /**
     * Medical Director signs off and approves clinical item
     */
    public static function approveItem(int $id, int $directorId): bool
    {
        $sql = "UPDATE `hms_content_approval` SET `status` = ?, `approved_by` = ?, `approved_at` = NOW(), `rejection_reason` = NULL WHERE `id` = ?";
        $res = sqlStatement($sql, [self::STATUS_APPROVED, $directorId, $id]);

        // Publish approval event to hms_event
        $item = sqlQuery("SELECT * FROM `hms_content_approval` WHERE `id` = ?", [$id]);
        if ($item) {
            sqlStatement(
                "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
                 VALUES ('clinical_content_approved', 0, 0, ?, NOW(), NOW(), ?, 'processed')",
                [$directorId, json_encode(['content_code' => $item['content_code'], 'title' => $item['title'], 'version' => $item['version']])]
            );
        }

        return $res !== false;
    }

    /**
     * Medical Director rejects item with comments
     */
    public static function rejectItem(int $id, int $directorId, string $reason): bool
    {
        $sql = "UPDATE `hms_content_approval` SET `status` = ?, `approved_by` = ?, `approved_at` = NOW(), `rejection_reason` = ? WHERE `id` = ?";
        return sqlStatement($sql, [self::STATUS_REJECTED, $directorId, $reason, $id]) !== false;
    }
}
