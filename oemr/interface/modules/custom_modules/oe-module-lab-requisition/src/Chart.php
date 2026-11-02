<?php

namespace OpenEMR\Modules\LabRequisition;

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
        $user = Db::one("SELECT fname, lname FROM users WHERE id = ?", [$userId]) ?? [];

        $address = trim(implode(', ', array_filter([
            trim((string)($pt['street'] ?? '')),
            trim((string)($pt['city'] ?? '')),
            trim((string)($pt['state'] ?? '')),
            trim((string)($pt['postal_code'] ?? '')),
        ], fn($v) => $v !== '')));

        return [
            'encounter' => $encounter && self::encounterBelongs($pid, $encounter) ? $encounter : null,
            'patient_name' => trim(($pt['fname'] ?? '') . ' ' . ($pt['lname'] ?? '')),
            'dob' => self::displayDate((string)($pt['DOB'] ?? '')),
            'sex' => (string)($pt['sex'] ?? ''),
            'reg_no' => (string)($pt['pubpid'] ?? ''),
            'address' => $address,
            'doctor' => trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')),
            'contact' => (string)(($pt['phone_cell'] ?? '') ?: ($pt['phone_home'] ?? '') ?: ($pt['phone_contact'] ?? '')),
            'order_date' => date('d/m/Y'),
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

    /** 1985-03-07 -> 07/03/1985 (the form is Day/Month/Year). */
    public static function displayDate(string $iso): string
    {
        if ($iso === '' || str_starts_with($iso, '0000')) {
            return '';
        }
        $t = strtotime($iso);
        return $t ? date('d/m/Y', $t) : '';
    }
}
