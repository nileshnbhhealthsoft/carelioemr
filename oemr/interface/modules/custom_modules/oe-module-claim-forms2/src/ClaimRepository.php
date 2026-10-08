<?php

namespace OpenEMR\Modules\ClaimForms;

/** Stores claims in the module's own tables. */
class ClaimRepository
{
    public static function create(int $pid, string $formId, array $payload, int $userId, array $encounters): int
    {
        $now = date('Y-m-d H:i:s');
        $id = Db::insert(
            "INSERT INTO claimforms_claim (pid, form_id, status, payload_json, total_cents, provider_user_id, created_by, created_at, updated_at)
             VALUES (?, ?, 'draft', ?, ?, ?, ?, ?, ?)",
            [$pid, $formId, json_encode($payload), Payload::totalCents($payload), $userId, $userId, $now, $now]
        );
        self::syncChildren($id, $payload, $encounters);
        self::event($id, $userId, 'created');
        return $id;
    }

    public static function update(int $id, array $payload, int $userId, array $encounters): void
    {
        $status = self::get($id)['status'] ?? 'draft';
        Db::exec(
            "UPDATE claimforms_claim SET payload_json = ?, total_cents = ?, status = ?, provider_user_id = ?, updated_at = ? WHERE id = ?",
            [json_encode($payload), Payload::totalCents($payload), $status === 'generated' ? 'amended' : $status, $userId, date('Y-m-d H:i:s'), $id]
        );
        self::syncChildren($id, $payload, $encounters);
        self::event($id, $userId, 'edited');
    }

    public static function get(int $id): ?array
    {
        $row = Db::one("SELECT * FROM claimforms_claim WHERE id = ?", [$id]);
        if (!$row) {
            return null;
        }
        $row['payload'] = json_decode((string)$row['payload_json'], true) ?: [];
        $row['encounters'] = array_map('intval', array_column(
            Db::all("SELECT encounter FROM claimforms_claim_encounter WHERE claim_id = ?", [$id]),
            'encounter'
        ));
        return $row;
    }

    public static function listForPatient(int $pid): array
    {
        return Db::all(
            "SELECT id, form_id, status, total_cents, revision, generated_at, updated_at
               FROM claimforms_claim WHERE pid = ? ORDER BY updated_at DESC LIMIT 100",
            [$pid]
        );
    }

    public static function markGenerated(int $id, string $pdfPath, ?int $documentId): int
    {
        Db::exec(
            "UPDATE claimforms_claim SET status = 'generated', revision = revision + 1, pdf_path = ?, document_id = ?, generated_at = ?, updated_at = ? WHERE id = ?",
            [$pdfPath, $documentId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]
        );
        return (int)(self::get($id)['revision'] ?? 1);
    }

    public static function event(?int $claimId, int $userId, string $action, string $detail = ''): void
    {
        Db::exec(
            "INSERT INTO claimforms_event (claim_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, ?)",
            [$claimId, $userId, $action, mb_substr($detail, 0, 255), date('Y-m-d H:i:s')]
        );
    }

    private static function syncChildren(int $id, array $payload, array $encounters): void
    {
        Db::exec("DELETE FROM claimforms_claim_encounter WHERE claim_id = ?", [$id]);
        foreach (Db::ints($encounters) as $enc) {
            Db::exec("INSERT INTO claimforms_claim_encounter (claim_id, encounter) VALUES (?, ?)", [$id, $enc]);
        }
        Db::exec("DELETE FROM claimforms_claim_line WHERE claim_id = ?", [$id]);
        foreach (array_values($payload['lines'] ?? []) as $i => $l) {
            $date = !empty($l['date']) && strtotime($l['date']) ? date('Y-m-d', strtotime($l['date'])) : null;
            Db::exec(
                "INSERT INTO claimforms_claim_line (claim_id, line_no, service_date, place_of_service, description, dx_pointer, amount_cents)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$id, $i + 1, $date, mb_substr((string)($l['place'] ?? ''), 0, 16), mb_substr((string)($l['desc'] ?? ''), 0, 255), (int)($l['dx'] ?? 0), (int)($l['amount'] ?? 0)]
            );
        }
    }
}
