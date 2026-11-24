<?php

namespace OpenEMR\Modules\CarelioCore\Services;

class PrivacySensitivityService
{
    public const LEVEL_NORMAL = 'normal';
    public const LEVEL_RESTRICTED = 'restricted';
    public const LEVEL_HIGHLY_RESTRICTED = 'highly_restricted';
    public const LEVEL_STAFF_AS_PATIENT = 'staff_as_patient';
    public const LEVEL_PROTECTED_IDENTITY = 'protected_identity';

    /**
     * Get all sensitivity flagged charts
     */
    public static function getRestrictedRecords(): array
    {
        $sql = "SELECT rr.*, 
                       p.fname, p.lname, p.pubpid, p.DOB,
                       u.fname as flagger_fname, u.lname as flagger_lname
                FROM `hms_restricted_record` rr
                LEFT JOIN `patient_data` p ON rr.`record_id` = p.`pid` AND rr.`record_type` = 'patient'
                LEFT JOIN `users` u ON rr.`flagged_by` = u.`id`
                ORDER BY rr.`id` DESC";

        $rows = sqlStatement($sql);
        $records = [];
        while ($row = sqlFetchArray($rows)) {
            $records[] = $row;
        }

        return $records;
    }

    /**
     * Flag a patient or encounter chart with a 5-tier sensitivity level
     */
    public static function flagRecord(string $recordType, int $recordId, string $level, string $reason, int $userId): int
    {
        $sql = "INSERT INTO `hms_restricted_record`
                (`record_type`, `record_id`, `sensitivity_level`, `reason`, `flagged_by`, `created_at`)
                VALUES (?, ?, ?, ?, ?, NOW())";
        return (int) sqlInsert($sql, [$recordType, $recordId, $level, $reason, $userId]);
    }

    /**
     * Log a clinical break-glass emergency override event
     */
    public static function logBreakGlass(int $pid, ?int $encounter, int $userId, string $level, string $reason): int
    {
        $now = date('Y-m-d H:i:s');
        $due = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $sql = "INSERT INTO `hms_breakglass_log`
                (`pid`, `encounter`, `user_id`, `sensitivity_level`, `reason`, `accessed_at`, `due_by`, `review_status`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_review', NOW())";
        $id = (int) sqlInsert($sql, [$pid, $encounter, $userId, $level, $reason, $now, $due]);

        // Publish to internal event bus
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('breakglass_triggered', ?, ?, ?, NOW(), NOW(), ?, 'published')",
            [$pid, $encounter ?? 0, $userId, json_encode(['breakglass_id' => $id, 'level' => $level, 'reason' => $reason, 'due_by' => $due])]
        );

        return $id;
    }

    /**
     * Get break-glass review queue for Privacy Officer
     */
    public static function getBreakGlassEvents(?string $status = null): array
    {
        $params = [];
        $where = "";
        if (!empty($status)) {
            $where = "WHERE bg.`review_status` = ?";
            $params[] = $status;
        }

        $sql = "SELECT bg.*, 
                       p.fname as pt_fname, p.lname as pt_lname, p.pubpid,
                       u.fname as user_fname, u.lname as user_lname,
                       rev.fname as rev_fname, rev.lname as rev_lname,
                       (bg.`due_by` < NOW() AND bg.`review_status` = 'pending_review') as is_overdue
                FROM `hms_breakglass_log` bg
                LEFT JOIN `patient_data` p ON bg.`pid` = p.`pid`
                LEFT JOIN `users` u ON bg.`user_id` = u.`id`
                LEFT JOIN `users` rev ON bg.`reviewed_by` = rev.`id`
                $where
                ORDER BY bg.`id` DESC";

        $rows = sqlStatement($sql, $params);
        $events = [];
        while ($row = sqlFetchArray($rows)) {
            $events[] = $row;
        }

        return $events;
    }

    /**
     * Privacy Officer certifies/reviews break-glass emergency access
     */
    public static function reviewBreakGlass(int $id, int $officerId, string $status, string $notes): bool
    {
        $sql = "UPDATE `hms_breakglass_log` SET 
                `reviewed_by` = ?, 
                `reviewed_at` = NOW(), 
                `review_status` = ?, 
                `review_notes` = ?
                WHERE `id` = ?";
        return sqlStatement($sql, [$officerId, $status, $notes, $id]) !== false;
    }
}
