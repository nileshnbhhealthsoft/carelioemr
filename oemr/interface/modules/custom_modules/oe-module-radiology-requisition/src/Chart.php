<?php

namespace OpenEMR\Modules\RadiologyRequisition;

/**
 * Starting values for a new requisition, read from the chart. It only reads.
 * Every value lands in an editable box, so a wrong guess costs a keystroke, not a wrong order.
 * Column names are the OpenEMR 8.0 ones; docs/VERIFY.md lists them for checking on each install.
 */
class Chart
{
    public static function defaults(int $pid, int $userId, ?int $encounter): array
    {
        $pt = Db::one("SELECT * FROM patient_data WHERE pid = ?", [$pid]) ?? [];
        $user = Db::one("SELECT fname, lname, npi, phonew1 FROM users WHERE id = ?", [$userId]) ?? [];
        $encounter = $encounter && self::encounterBelongs($pid, $encounter) ? $encounter : null;

        $dob = (string)($pt['DOB'] ?? '');
        $sex = (string)($pt['sex'] ?? '');

        return [
            'encounter' => $encounter,
            'patient_name' => trim(($pt['fname'] ?? '') . ' ' . ($pt['lname'] ?? '')),
            'mrn' => (string)($pt['pubpid'] ?? ''),
            'dob' => ($dob !== '' && !str_starts_with($dob, '0000')) ? substr($dob, 0, 10) : '',
            'sex' => $sex === '' ? '' : (in_array($sex, Catalog::SEX, true) ? $sex : 'Unknown'),
            'encounter_ref' => $encounter ? (string)$encounter : '',
            'order_datetime' => date('Y-m-d\TH:i'),
            'facility' => self::facility($userId, $encounter),
            'contact' => (string)(($pt['phone_cell'] ?? '') ?: ($pt['phone_home'] ?? '') ?: ($pt['phone_contact'] ?? '')),
            'allergies' => self::allergies($pid),
            'priority' => 'Routine',
            'setting' => 'Outpatient',
            'clinician' => trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')),
            'provider_id' => (string)($user['npi'] ?? ''),
            'provider_contact' => (string)($user['phonew1'] ?? ''),
        ];
    }

    public static function encounterBelongs(int $pid, int $encounter): bool
    {
        return Db::one("SELECT 1 AS ok FROM form_encounter WHERE pid = ? AND encounter = ?", [$pid, $encounter]) !== null;
    }

    public static function userName(int $userId): string
    {
        $u = Db::one("SELECT fname, lname FROM users WHERE id = ?", [$userId]) ?? [];
        return trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? ''));
    }

    /** The encounter's facility, else the user's default facility, else the first facility on file. */
    private static function facility(int $userId, ?int $encounter): string
    {
        if ($encounter) {
            $r = Db::one(
                "SELECT f.name FROM form_encounter fe JOIN facility f ON f.id = fe.facility_id WHERE fe.encounter = ? LIMIT 1",
                [$encounter]
            );
            if ($r && ($r['name'] ?? '') !== '') {
                return (string)$r['name'];
            }
        }
        $r = Db::one("SELECT f.name FROM users u JOIN facility f ON f.id = u.facility_id WHERE u.id = ?", [$userId])
            ?? Db::one("SELECT name FROM facility ORDER BY id LIMIT 1");
        return (string)($r['name'] ?? '');
    }

    /** Active allergies from the issue list, comma-separated; blank when none are recorded. */
    private static function allergies(int $pid): string
    {
        $rows = Db::all(
            "SELECT title FROM lists WHERE pid = ? AND type = 'allergy' AND (enddate IS NULL OR enddate = '' OR enddate > NOW()) ORDER BY title LIMIT 20",
            [$pid]
        );
        return mb_substr(implode(', ', array_filter(array_column($rows, 'title'))), 0, 255);
    }
}
