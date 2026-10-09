<?php

namespace OpenEMR\Modules\ClaimForms;

/** Coerces whatever the browser sends into the shapes the renderer expects. No HTML is allowed to survive. */
class Sanitizer
{
    private const LONG = ['further_services', 'address', 'provider_block', 'condition_details', 'lab_tests', 'dent_address'];

    /** Repeating tables on page 2: key => [max rows, text columns with their max length, date column]. */
    private const ROWS = [
        'dent_rows' => [7, ['tooth' => 10, 'surface' => 10, 'desc' => 120]],
        'opt_rows' => [4, ['dx' => 60]],
        'lab_rows' => [2, ['test' => 120]],
    ];

    public static function payload(array $in): array
    {
        $out = [];
        foreach ($in as $key => $val) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,48}$/', $key)) {
                continue;
            }
            if ($key === 'icd') {
                $out['icd'] = array_slice(array_map(fn($v) => self::str($v, 120), is_array($val) ? array_values($val) : []), 0, 4);
            } elseif (str_ends_with($key, '_checks')) {
                $out[$key] = self::checks(is_array($val) ? $val : []);
            } elseif (str_ends_with($key, '_rows') && !isset(self::ROWS[$key])) {
                $out[$key] = self::genericRows(is_array($val) ? $val : []);
            } elseif (isset(self::ROWS[$key])) {
                $out[$key] = self::rows(is_array($val) ? $val : [], self::ROWS[$key][0], self::ROWS[$key][1]);
            } elseif ($key === 'lines') {
                $out['lines'] = self::lines(is_array($val) ? $val : []);
            } elseif (str_ends_with($key, '_amount')) {
                $out[$key] = max(0, (int)$val);
            } elseif (is_scalar($val)) {
                $long = in_array($key, self::LONG, true) || str_ends_with($key, '_ml');
                $out[$key] = self::str($val, $long ? 2000 : 255, $long);
            }
        }
        return $out;
    }

    /** @param array<string,int> $text text column => max length */
    private static function rows(array $rows, int $max, array $text): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, $max) as $r) {
            $r = is_array($r) ? $r : [];
            $row = ['date' => self::str($r['date'] ?? '', 10), 'amount' => max(0, (int)($r['amount'] ?? 0))];
            foreach ($text as $col => $len) {
                $row[$col] = self::str($r[$col] ?? '', $len);
            }
            $out[] = $row;
        }
        return $out;
    }

    /** Tick-and-fee checklist: item id => ['on' => '1' or '', 'fee' => cents]. */
    private static function checks(array $items): array
    {
        $out = [];
        foreach (array_slice($items, 0, 400, true) as $id => $v) {
            $id = (string)$id; // PHP turns purely numeric keys such as "99213" into integers
            if (!preg_match('/^[A-Za-z0-9_]{1,24}$/', $id) || !is_array($v)) {
                continue;
            }
            $out[$id] = ['on' => !empty($v['on']) ? '1' : '', 'fee' => max(0, (int)($v['fee'] ?? 0))];
        }
        return $out;
    }

    /** Rows for the tables of forms added later: every column is text, except money columns (amount, fee, price...). */
    private static function genericRows(array $rows): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, 40) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $row = [];
            foreach (array_slice($r, 0, 14, true) as $col => $v) {
                if (!is_string($col) || !preg_match('/^[a-z0-9_]{1,24}$/', $col)) {
                    continue;
                }
                $row[$col] = (in_array($col, ['amount', 'fee', 'price', 'copay'], true) || str_ends_with($col, '_amount'))
                    ? max(0, (int)$v) : self::str($v, 255);
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function lines(array $lines): array
    {
        $out = [];
        foreach (array_slice(array_values($lines), 0, 20) as $l) {
            if (!is_array($l)) {
                continue;
            }
            $out[] = [
                'date' => self::str($l['date'] ?? '', 10),
                'place' => self::str($l['place'] ?? '', 16),
                'desc' => self::str($l['desc'] ?? '', 255),
                'dx' => self::str($l['dx'] ?? '', 1),
                'amount' => max(0, (int)($l['amount'] ?? 0)),
            ];
        }
        return $out;
    }

    private static function str(mixed $v, int $max, bool $multiline = false): string
    {
        $s = is_scalar($v) ? (string)$v : '';
        $s = strip_tags($s);
        $s = $multiline ? preg_replace('/[^\P{C}\n]/u', '', $s) : preg_replace('/\p{C}/u', ' ', $s);
        return mb_substr(trim((string)$s), 0, $max);
    }
}
