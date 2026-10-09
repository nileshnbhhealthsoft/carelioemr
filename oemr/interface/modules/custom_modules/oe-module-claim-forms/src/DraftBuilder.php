<?php

namespace OpenEMR\Modules\ClaimForms;

/**
 * Builds the starting values for a claim from the chart. It only reads.
 * Every value it returns is shown on the review screen and can be edited, so a
 * wrong guess here costs the provider a click, not a wrong claim.
 *
 * Table and column names are the OpenEMR 8.0 ones. See docs/VERIFY.md for the
 * list to confirm on each install before first use.
 */
class DraftBuilder
{
    private const POS_MAP = ['11' => 'Office', '12' => 'Home', '21' => 'Hosp.', '22' => 'Hosp.', '23' => 'Hosp.', '19' => 'Hosp.'];

    /**
     * @param int[] $encounters
     * @return array{payload:array,icdOptions:array,encounters:array}
     */
    public static function build(int $pid, array $encounters): array
    {
        $encounters = Db::ints($encounters);
        $pt = Db::one("SELECT * FROM patient_data WHERE pid = ?", [$pid]) ?? [];
        $primary = self::plan($pid, 'primary');
        $secondary = self::plan($pid, 'secondary');
        $encRows = self::encounterRows($pid, $encounters);
        $first = $encRows[0] ?? [];

        $patientName = trim(($pt['fname'] ?? '') . ' ' . ($pt['lname'] ?? ''));
        $selfInsured = !$primary || self::isSelf($primary);
        $subName = $primary ? trim(($primary['subscriber_fname'] ?? '') . ' ' . ($primary['subscriber_lname'] ?? '')) : '';

        $patAddr = self::address($pt['street'] ?? '', $pt['city'] ?? '', $pt['state'] ?? '', $pt['postal_code'] ?? '');
        $subAddr = $primary ? self::address($primary['subscriber_street'] ?? '', $primary['subscriber_city'] ?? '', $primary['subscriber_state'] ?? '', $primary['subscriber_postal_code'] ?? '') : '';
        $phone = $pt['phone_cell'] ?? ($pt['phone_home'] ?? ($pt['phone_contact'] ?? ''));

        $icdOptions = self::diagnosisOptions($pid, $encounters);
        $icdCodes = array_slice(array_map(fn($o) => $o['label'], $icdOptions), 0, 4);

        $payload = [
            'coverage' => 'health',
            'policy_no' => $primary['policy_number'] ?? '',
            'policy_holder' => $selfInsured ? $patientName : $subName,
            'member_id' => $primary['group_number'] ?? '',
            'insured_name' => $selfInsured ? $patientName : $subName,
            'patient_name' => $patientName,
            'patient_dob' => self::isoDate($pt['DOB'] ?? ''),
            'spouse_employer' => (!$selfInsured && strtolower((string)($primary['subscriber_relationship'] ?? '')) === 'spouse')
                ? (string)($primary['subscriber_employer'] ?? '') : '',
            'address' => $selfInsured ? $patAddr : ($subAddr ?: $patAddr),
            'telephone' => $selfInsured ? $phone : ($primary['subscriber_phone'] ?? $phone),
            'related_employment' => 'no',
            'related_auto' => 'no',
            'related_other' => 'no',
            'condition_details' => '',
            'other_plan' => $secondary ? 'yes' : 'no',
            'other_plan_company' => $secondary ? self::companyName((int)($secondary['provider'] ?? 0)) : '',
            'other_plan_group' => $secondary['group_number'] ?? '',
            'pay_to' => $first['facility_name'] ?? '',
            'icd' => $icdCodes,
            'provider_block' => self::providerBlock($first),
            'provider_name' => (string)($first['facility_name'] ?? ''),
            'provider_address' => self::address($first['street'] ?? '', $first['city'] ?? '', $first['state'] ?? '', $first['postal_code'] ?? ''),
            'provider_phone' => (string)($first['phone'] ?? ''),
            'gender' => strtolower((string)($pt['sex'] ?? '')) === 'male' ? 'male' : (strtolower((string)($pt['sex'] ?? '')) === 'female' ? 'female' : ''),
            'relationship' => $selfInsured ? 'self' : (in_array(strtolower((string)($primary['subscriber_relationship'] ?? '')), ['spouse', 'child'], true) ? strtolower((string)$primary['subscriber_relationship']) : 'other'),
            'employer_name' => (string)($primary['subscriber_employer'] ?? ''),
            'patient_surname' => (string)($pt['lname'] ?? ''),
            'patient_first_name' => (string)($pt['fname'] ?? ''),
            'patient_middle' => strtoupper(substr((string)($pt['mname'] ?? ''), 0, 1)),
            'insured_surname' => (string)($selfInsured ? ($pt['lname'] ?? '') : ($primary['subscriber_lname'] ?? '')),
            'insured_first_name' => (string)($selfInsured ? ($pt['fname'] ?? '') : ($primary['subscriber_fname'] ?? '')),
            'insured_middle' => strtoupper(substr((string)($selfInsured ? ($pt['mname'] ?? '') : ($primary['subscriber_mname'] ?? '')), 0, 1)),
            'insured_dob' => self::isoDate((string)($selfInsured ? ($pt['DOB'] ?? '') : ($primary['subscriber_DOB'] ?? ''))),
            'phone_home' => (string)($pt['phone_home'] ?? ''),
            'phone_work' => (string)($pt['phone_biz'] ?? ''),
            'phone_cell' => (string)($pt['phone_cell'] ?? ''),
            'first_name' => (string)($pt['fname'] ?? ''),
            'surname' => (string)($pt['lname'] ?? ''),
            'referring_physician' => '',
            'referring_physician_id' => '',
            'pregnancy' => 'no',
            'last_period' => '',
            'first_symptoms' => self::isoDate($first['onset_date'] ?? ''),
            'first_consult' => self::isoDate(substr((string)($first['date'] ?? ''), 0, 10)),
            'prev_treated' => 'no',
            'prev_treated_date' => '',
            'lines' => self::lineItems($pid, $encounters, $icdOptions, $encRows),
            'further_services' => '',
            'further_services_amount' => 0,
            'has_surgery' => 'no',
            'op_date' => '',
            'op_type' => '',
            'op_surgeon' => $first['provider_name'] ?? '',
            'op_assistant' => '',
            'op_anesthetist' => '',
            'surgical_amount' => 0,
        ];

        // Default the referring physician from the patient's record when it has one.
        $refId = (int)($pt['ref_providerID'] ?? 0);
        if ($refId > 0) {
            $ref = Db::one("SELECT id, fname, lname, organization FROM users WHERE id = ?", [$refId]);
            if ($ref) {
                $payload['referring_physician_id'] = (string)$ref['id'];
                $payload['referring_physician'] = self::personName($ref);
            }
        }

        // Prefill for forms that list the doctor's visits in their own columns (date, diagnosis, fee, type, service, cost).
        $payload['doc_rows'] = array_map(fn($l) => [
            'date' => $l['date'] ?? '',
            'icd' => $icdCodes[max(0, (int)($l['dx'] ?? 1) - 1)] ?? '',
            'visit_type' => $l['place'] ?? '',
            'desc' => $l['desc'] ?? '',
            'amount' => (int)($l['amount'] ?? 0),
        ], array_slice($payload['lines'], 0, 6));
        $payload['gtm_rows'] = array_map(fn($l) => [
            'date' => $l['date'] ?? '',
            'place' => ($l['place'] ?? '') === 'Hosp.' ? 'Hospital' : ($l['place'] ?? ''),
            'desc' => $l['desc'] ?? '',
            'dx' => $icdCodes[max(0, (int)($l['dx'] ?? 1) - 1)] ?? '',
            'amount' => (int)($l['amount'] ?? 0),
        ], array_slice($payload['lines'], 0, 8));
        $payload['relationship_text'] = ucfirst((string)$payload['relationship']);
        $payload['dx_rows'] = array_map(fn($c) => ['dx' => $c], array_slice(array_column($icdOptions, 'code'), 0, 6));
        $payload['date_of_service'] = self::isoDate(substr((string)($first['date'] ?? ''), 0, 10));
        $payload['facility'] = trim(($first['facility_name'] ?? '') . ($payload['provider_address'] !== '' ? ', ' . $payload['provider_address'] : ''));
        $payload['doctor_name'] = (string)($first['provider_name'] ?? '');
        $payload['doctor_phone'] = (string)($first['phone'] ?? '');

        return ['payload' => $payload, 'icdOptions' => $icdOptions, 'encounters' => $encRows];
    }

    /** Encounters the provider can choose from on the first screen. */
    public static function encounterChoices(int $pid): array
    {
        return Db::all(
            "SELECT fe.encounter, fe.date, fe.reason, f.name AS facility_name
               FROM form_encounter fe LEFT JOIN facility f ON f.id = fe.facility_id
              WHERE fe.pid = ? ORDER BY fe.date DESC LIMIT 100",
            [$pid]
        );
    }

    /** Address Book contacts plus internal providers, for the referring physician dropdown. */
    public static function referrers(): array
    {
        $rows = Db::all(
            "SELECT id, fname, lname, organization, specialty, (username IS NULL OR username = '') AS external
               FROM users
              WHERE active = 1 AND lname IS NOT NULL AND lname <> ''
                AND (authorized = 1 OR username IS NULL OR username = '')
              ORDER BY lname, fname LIMIT 1000"
        );
        return array_map(fn($r) => [
            'id' => (string)$r['id'],
            'name' => self::personName($r),
            'detail' => trim(($r['specialty'] ?? '') . ' ' . ($r['organization'] ?? '')),
            'external' => (bool)$r['external'],
        ], $rows);
    }

    /** Search the installed ICD-10 code set (and any custom codes) for the code picker. */
    public static function searchIcd(string $q): array
    {
        $q = trim($q);
        if (strlen($q) < 2) {
            return [];
        }
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $out = [];
        if (Db::tableExists('icd10_dx_order_code')) {
            $rows = Db::all(
                "SELECT formatted_dx_code AS code, short_desc AS text FROM icd10_dx_order_code
                  WHERE active = 1 AND valid_for_coding = 1 AND (formatted_dx_code LIKE ? OR short_desc LIKE ?)
                  ORDER BY formatted_dx_code LIMIT 25",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $out[] = ['code' => $r['code'], 'label' => $r['code'], 'text' => $r['text']];
            }
        }
        if (count($out) < 25) {
            $rows = Db::all(
                "SELECT c.code, c.code_text AS text FROM codes c JOIN code_types ct ON ct.ct_id = c.code_type
                  WHERE ct.ct_key LIKE 'ICD%' AND c.active = 1 AND (c.code LIKE ? OR c.code_text LIKE ?)
                  ORDER BY c.code LIMIT 25",
                [$like, $like]
            );
            foreach ($rows as $r) {
                $out[] = ['code' => $r['code'], 'label' => $r['code'], 'text' => $r['text']];
            }
        }
        return $out;
    }

    /** CPT4 / HCPCS procedure codes from the Fee Sheet code tables, for the "Type of operation" picker. */
    public static function searchCpt(string $q): array
    {
        $q = trim($q);
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $rows = Db::all(
            "SELECT c.code, c.code_text AS text FROM codes c JOIN code_types ct ON ct.ct_id = c.code_type
              WHERE ct.ct_key IN ('CPT4', 'HCPCS') AND c.active = 1 AND (c.code LIKE ? OR c.code_text LIKE ?)
              ORDER BY c.code LIMIT 50",
            [$like, $like]
        );
        return array_map(fn($r) => ['code' => $r['code'], 'label' => trim($r['code'] . ' ' . $r['text'])], $rows);
    }

    // ---- internals ----

    private static function plan(int $pid, string $type): ?array
    {
        return Db::one(
            "SELECT * FROM insurance_data WHERE pid = ? AND type = ? AND provider IS NOT NULL AND provider <> ''
              ORDER BY (date IS NULL), date DESC LIMIT 1",
            [$pid, $type]
        );
    }

    private static function isSelf(array $plan): bool
    {
        $rel = strtolower((string)($plan['subscriber_relationship'] ?? ''));
        return $rel === '' || $rel === 'self';
    }

    private static function companyName(int $id): string
    {
        if ($id <= 0) {
            return '';
        }
        $r = Db::one("SELECT name FROM insurance_companies WHERE id = ?", [$id]);
        return $r['name'] ?? '';
    }

    private static function encounterRows(int $pid, array $encounters): array
    {
        if (!$encounters) {
            return [];
        }
        return Db::all(
            "SELECT fe.encounter, fe.date, fe.pos_code, fe.onset_date, fe.provider_id,
                    f.name AS facility_name, f.street, f.city, f.state, f.postal_code, f.phone,
                    CONCAT(COALESCE(u.fname,''), ' ', COALESCE(u.lname,'')) AS provider_name
               FROM form_encounter fe
               LEFT JOIN facility f ON f.id = fe.facility_id
               LEFT JOIN users u ON u.id = fe.provider_id
              WHERE fe.pid = ? AND fe.encounter IN (" . Db::placeholders(count($encounters)) . ")
              ORDER BY fe.date ASC",
            array_merge([$pid], $encounters)
        );
    }

    /** @return array<int,array{code:string,label:string}> diagnoses on the chosen encounters, then the issue list */
    private static function diagnosisOptions(int $pid, array $encounters): array
    {
        $seen = [];
        $out = [];
        $add = function (string $code, string $text) use (&$seen, &$out): void {
            $code = trim($code);
            if ($code === '' || isset($seen[$code])) {
                return;
            }
            $seen[$code] = true;
            $out[] = ['code' => $code, 'label' => $code];
        };
        if ($encounters) {
            $rows = Db::all(
                "SELECT code, code_text FROM billing
                  WHERE pid = ? AND activity = 1 AND code_type LIKE 'ICD%' AND encounter IN (" . Db::placeholders(count($encounters)) . ")
                  ORDER BY id",
                array_merge([$pid], $encounters)
            );
            foreach ($rows as $r) {
                $add((string)$r['code'], (string)$r['code_text']);
            }
        }
        $issues = Db::all(
            "SELECT diagnosis, title FROM lists WHERE pid = ? AND type = 'medical_problem' AND activity = 1 AND diagnosis <> '' ORDER BY begdate DESC",
            [$pid]
        );
        foreach ($issues as $r) {
            foreach (explode(';', (string)$r['diagnosis']) as $d) {
                $parts = explode(':', $d, 2);
                if (count($parts) === 2 && stripos($parts[0], 'ICD') === 0) {
                    $add($parts[1], (string)$r['title']);
                }
            }
        }
        return $out;
    }

    private static function lineItems(int $pid, array $encounters, array $icdOptions, array $encRows): array
    {
        if (!$encounters) {
            return [];
        }
        $posByEnc = [];
        foreach ($encRows as $e) {
            $posByEnc[$e['encounter']] = self::POS_MAP[(string)($e['pos_code'] ?? '')] ?? 'Office';
        }
        $rows = Db::all(
            "SELECT encounter, date, code, code_text, modifier, fee, justify FROM billing
              WHERE pid = ? AND activity = 1 AND code_type NOT LIKE 'ICD%' AND encounter IN (" . Db::placeholders(count($encounters)) . ")
              ORDER BY date, id",
            array_merge([$pid], $encounters)
        );
        $codes = array_column($icdOptions, 'code');
        $lines = [];
        foreach ($rows as $r) {
            $dx = 1;
            foreach (explode(':', (string)$r['justify']) as $j) {
                $j = trim(preg_replace('/^[A-Z0-9\-]+\|/i', '', $j));
                $pos = array_search($j, $codes, true);
                if ($j !== '' && $pos !== false && $pos < 4) {
                    $dx = $pos + 1;
                    break;
                }
            }
            $lines[] = [
                'date' => self::isoDate(substr((string)$r['date'], 0, 10)),
                'place' => $posByEnc[$r['encounter']] ?? 'Office',
                'desc' => trim($r['code'] . ' ' . $r['code_text'] . ($r['modifier'] ? ' -' . $r['modifier'] : '')),
                'dx' => (string)$dx,
                'amount' => Payload::toCents($r['fee']),
            ];
        }
        return $lines;
    }

    private static function providerBlock(array $enc): string
    {
        if (!$enc) {
            return '';
        }
        $addr = self::address($enc['street'] ?? '', $enc['city'] ?? '', $enc['state'] ?? '', $enc['postal_code'] ?? '');
        $line1 = trim(($enc['facility_name'] ?? '') . ($addr ? ', ' . $addr : ''));
        return $line1 . (!empty($enc['phone']) ? "\nTel: " . $enc['phone'] : '');
    }

    private static function address(string $street, string $city, string $state, string $zip): string
    {
        return implode(', ', array_filter([trim($street), trim($city), trim($state), trim($zip)], fn($p) => $p !== ''));
    }

    private static function personName(array $u): string
    {
        $n = trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? ''));
        return $n !== '' ? 'Dr. ' . $n : (string)($u['organization'] ?? '');
    }

    private static function isoDate(?string $d): string
    {
        $d = trim((string)$d);
        return ($d === '' || str_starts_with($d, '0000')) ? '' : substr($d, 0, 10);
    }
}
