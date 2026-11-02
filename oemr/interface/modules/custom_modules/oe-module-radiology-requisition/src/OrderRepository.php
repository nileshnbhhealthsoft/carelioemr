<?php

namespace OpenEMR\Modules\RadiologyRequisition;

/** Stores requisitions in the module's own tables. */
class OrderRepository
{
    private const COLUMNS = [
        'patient_name', 'mrn', 'dob', 'sex', 'encounter_ref', 'order_datetime', 'facility', 'contact', 'allergies',
        'history', 'indication', 'diagnosis', 'priority', 'setting', 'additional',
        'clinician', 'provider_id', 'provider_contact', 'destination',
    ];

    public static function create(int $pid, ?int $encounter, array $clean, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $id = Db::insert(
            "INSERT INTO radreq_order (pid, encounter, status, created_by, created_at, updated_at, " . implode(', ', self::COLUMNS) . ")
             VALUES (?, ?, 'draft', ?, ?, ?, " . Db::placeholders(count(self::COLUMNS)) . ")",
            array_merge([$pid, $encounter, $userId, $now, $now], self::values($clean['fields']))
        );
        self::syncStudies($id, $clean['studies']);
        self::event($id, $userId, 'created');
        return $id;
    }

    public static function update(int $id, array $clean, int $userId): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = ?", self::COLUMNS));
        Db::exec(
            "UPDATE radreq_order SET $set, updated_at = ? WHERE id = ? AND status = 'draft'",
            array_merge(self::values($clean['fields']), [date('Y-m-d H:i:s'), $id])
        );
        self::syncStudies($id, $clean['studies']);
        self::event($id, $userId, 'edited');
    }

    /** Locks the order and records the authenticated user who finalized it. */
    public static function finalize(int $id, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        Db::exec(
            "UPDATE radreq_order SET status = 'final', finalized_by = ?, finalized_at = ?, updated_at = ? WHERE id = ? AND status = 'draft'",
            [$userId, $now, $now, $id]
        );
        self::event($id, $userId, 'finalized');
    }

    public static function cancel(int $id, int $userId): void
    {
        Db::exec("UPDATE radreq_order SET status = 'cancelled', updated_at = ? WHERE id = ? AND status <> 'cancelled'", [date('Y-m-d H:i:s'), $id]);
        self::event($id, $userId, 'cancelled');
    }

    public static function get(int $id): ?array
    {
        $row = Db::one("SELECT * FROM radreq_order WHERE id = ?", [$id]);
        if (!$row) {
            return null;
        }
        $row['dob'] = (string)($row['dob'] ?? '');
        $row['order_datetime'] = $row['order_datetime'] ? date('Y-m-d\TH:i', strtotime((string)$row['order_datetime'])) : '';
        $row['finalized_by_name'] = $row['finalized_by'] ? Chart::userName((int)$row['finalized_by']) : '';
        $row['studies'] = [];
        foreach (Db::all("SELECT * FROM radreq_study WHERE order_id = ? ORDER BY study_no", [$id]) as $s) {
            $s['renal_date'] = (string)($s['renal_date'] ?? '');
            $s['safety'] = json_decode((string)$s['safety_json'], true) ?: [];
            $row['studies'][] = $s;
        }
        return $row;
    }

    public static function listForPatient(int $pid): array
    {
        return Db::all(
            "SELECT o.id, o.status, o.priority, o.order_datetime, o.updated_at,
                    (SELECT GROUP_CONCAT(CONCAT(s.modality, ' ', IF(s.examination = 'Other' AND s.other_examination <> '', s.other_examination, s.examination)) ORDER BY s.study_no SEPARATOR '; ')
                       FROM radreq_study s WHERE s.order_id = o.id) AS studies
               FROM radreq_order o WHERE o.pid = ? ORDER BY o.updated_at DESC LIMIT 100",
            [$pid]
        );
    }

    public static function event(?int $orderId, int $userId, string $action, string $detail = ''): void
    {
        Db::exec(
            "INSERT INTO radreq_event (order_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, ?)",
            [$orderId, $userId, $action, mb_substr($detail, 0, 255), date('Y-m-d H:i:s')]
        );
    }

    private static function values(array $fields): array
    {
        return array_map(fn($c) => $fields[$c], self::COLUMNS);
    }

    private static function syncStudies(int $id, array $studies): void
    {
        Db::exec("DELETE FROM radreq_study WHERE order_id = ?", [$id]);
        foreach ($studies as $i => $s) {
            Db::exec(
                "INSERT INTO radreq_study (order_id, study_no, modality, examination, laterality, contrast_protocol, instructions,
                        other_examination, creatinine, egfr, renal_date, safety_notes, safety_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$id, $i + 1, $s['modality'], $s['examination'], $s['laterality'], $s['contrast_protocol'], $s['instructions'],
                 $s['other_examination'], $s['creatinine'], $s['egfr'], $s['renal_date'], $s['safety_notes'],
                 json_encode($s['safety'], JSON_UNESCAPED_UNICODE)]
            );
        }
    }
}
