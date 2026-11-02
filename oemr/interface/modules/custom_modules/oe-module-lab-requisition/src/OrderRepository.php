<?php

namespace OpenEMR\Modules\LabRequisition;

/** Stores requisitions in the module's own tables. */
class OrderRepository
{
    private const COLUMNS = [
        'patient_name', 'dob', 'sex', 'reg_no', 'address', 'doctor', 'contact', 'order_date',
        'specimen_line1', 'specimen_line2', 'signed', 'clinical_details', 'histology_site', 'is_urgent', 'is_stat',
    ];

    public static function create(int $pid, ?int $encounter, array $clean, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $cols = implode(', ', self::COLUMNS);
        $marks = Db::placeholders(count(self::COLUMNS));
        $id = Db::insert(
            "INSERT INTO labreq_order (pid, encounter, status, created_by, created_at, updated_at, $cols)
             VALUES (?, ?, 'draft', ?, ?, ?, $marks)",
            array_merge([$pid, $encounter, $userId, $now, $now], self::values($clean['fields']))
        );
        self::syncItems($id, $clean['items']);
        self::event($id, $userId, 'created');
        return $id;
    }

    public static function update(int $id, array $clean, int $userId): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = ?", self::COLUMNS));
        Db::exec(
            "UPDATE labreq_order SET $set, updated_at = ? WHERE id = ? AND status = 'draft'",
            array_merge(self::values($clean['fields']), [date('Y-m-d H:i:s'), $id])
        );
        self::syncItems($id, $clean['items']);
        self::event($id, $userId, 'edited');
    }

    public static function finalize(int $id, int $userId, string $signerName): void
    {
        $now = date('Y-m-d H:i:s');
        Db::exec(
            "UPDATE labreq_order SET status = 'final', finalized_by = ?, finalized_at = ?, updated_at = ?,
                    signed = IF(signed = '', ?, signed)
              WHERE id = ? AND status = 'draft'",
            [$userId, $now, $now, $signerName, $id]
        );
        self::event($id, $userId, 'finalized');
    }

    public static function cancel(int $id, int $userId): void
    {
        Db::exec("UPDATE labreq_order SET status = 'cancelled', updated_at = ? WHERE id = ? AND status <> 'cancelled'", [date('Y-m-d H:i:s'), $id]);
        self::event($id, $userId, 'cancelled');
    }

    public static function get(int $id): ?array
    {
        $row = Db::one("SELECT * FROM labreq_order WHERE id = ?", [$id]);
        if (!$row) {
            return null;
        }
        $row['specimen'] = [];
        $row['tests'] = [];
        $row['detail'] = [];
        foreach (Db::all("SELECT kind, code, detail FROM labreq_order_item WHERE order_id = ?", [$id]) as $it) {
            if ($it['kind'] === 'specimen') {
                $row['specimen'][] = $it['code'];
                $row['detail']['specimen_' . $it['code']] = $it['detail'];
            } else {
                $row['tests'][] = $it['code'];
                $row['detail'][$it['code']] = $it['detail'];
            }
        }
        return $row;
    }

    public static function listForPatient(int $pid): array
    {
        return Db::all(
            "SELECT o.id, o.status, o.is_urgent, o.is_stat, o.order_date, o.updated_at,
                    (SELECT COUNT(*) FROM labreq_order_item i WHERE i.order_id = o.id AND i.kind = 'test') AS test_count
               FROM labreq_order o WHERE o.pid = ? ORDER BY o.updated_at DESC LIMIT 100",
            [$pid]
        );
    }

    public static function event(?int $orderId, int $userId, string $action, string $detail = ''): void
    {
        Db::exec(
            "INSERT INTO labreq_event (order_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, ?)",
            [$orderId, $userId, $action, mb_substr($detail, 0, 255), date('Y-m-d H:i:s')]
        );
    }

    private static function values(array $fields): array
    {
        return array_map(fn($c) => $fields[$c], self::COLUMNS);
    }

    private static function syncItems(int $id, array $items): void
    {
        Db::exec("DELETE FROM labreq_order_item WHERE order_id = ?", [$id]);
        foreach ($items as $it) {
            Db::exec(
                "INSERT INTO labreq_order_item (order_id, kind, code, detail) VALUES (?, ?, ?, ?)",
                [$id, $it['kind'], $it['code'], $it['detail']]
            );
        }
    }
}
