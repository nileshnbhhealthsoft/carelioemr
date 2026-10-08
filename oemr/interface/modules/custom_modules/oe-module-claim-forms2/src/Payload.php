<?php

namespace OpenEMR\Modules\ClaimForms;

/** Reads values out of a claim payload by dotted path, and does the money and date formatting. */
class Payload
{
    public static function get(array $payload, string $path): mixed
    {
        $cur = $payload;
        foreach (explode('.', $path) as $seg) {
            if (is_array($cur) && array_key_exists($seg, $cur)) {
                $cur = $cur[$seg];
            } else {
                return null;
            }
        }
        return $cur;
    }

    /** Sum of every line amount plus the further-services and surgical amounts, in integer cents. */
    public static function totalCents(array $payload): int
    {
        $sum = 0;
        foreach (($payload['lines'] ?? []) as $line) {
            $sum += (int)($line['amount'] ?? 0);
        }
        $sum += (int)($payload['further_services_amount'] ?? 0);
        $sum += (int)($payload['surgical_amount'] ?? 0);
        return $sum;
    }

    /**
     * Adds up the money in several payload keys, in integer cents. Each key may hold a list of rows (their
     * `amount`), a tick-and-fee checklist (each ticked item's `fee`), or a single whole number.
     */
    public static function sumKeys(array $payload, array $keys): int
    {
        $sum = 0;
        foreach ($keys as $key) {
            $v = $payload[$key] ?? null;
            if (is_array($v)) {
                foreach ($v as $item) {
                    if (is_array($item)) {
                        $sum += (int)($item['amount'] ?? $item['fee'] ?? 0);
                    }
                }
            } elseif (is_numeric($v)) {
                $sum += (int)$v;
            }
        }
        return $sum;
    }

    /** Sum of the `amount` column of a list of rows, in integer cents. */
    public static function sumRows(array $payload, string $key): int
    {
        $sum = 0;
        foreach (($payload[$key] ?? []) as $row) {
            $sum += (int)($row['amount'] ?? 0);
        }
        return $sum;
    }

    /** "125.50" or "1,250" -> integer cents. Returns 0 for blank. */
    public static function toCents(mixed $v): int
    {
        $s = str_replace([',', ' ', '$'], '', (string)$v);
        if ($s === '' || !is_numeric($s)) {
            return 0;
        }
        return (int)round(((float)$s) * 100);
    }

    public static function dollars(int $cents): string
    {
        return number_format(intdiv(abs($cents), 100));
    }

    public static function centsPart(int $cents): string
    {
        return str_pad((string)(abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    /** ISO date (YYYY-MM-DD) -> DD/MM/YYYY or DD/MM/YY. Anything unparseable is returned unchanged. */
    public static function date(?string $iso, string $format): string
    {
        if ($iso === null || $iso === '' || str_starts_with($iso, '0000')) {
            return '';
        }
        $ts = strtotime($iso);
        if ($ts === false) {
            return $iso;
        }
        return date(match ($format) {
            'DD/MM/YY' => 'd/m/y',
            'DD' => 'd',
            'MM' => 'm',
            'YY' => 'y',
            default => 'd/m/Y',
        }, $ts);
    }
}
