<?php

namespace OpenEMR\Modules\LabRequisition;

/**
 * Turns the posted form into the shape the repository stores. Only fields and codes the module
 * defines get through; free text has tags and control characters removed and is length-capped.
 */
class Sanitizer
{
    private const TEXT = [
        'patient_name' => 255, 'dob' => 20, 'sex' => 20, 'reg_no' => 64, 'address' => 255, 'doctor' => 255,
        'contact' => 64, 'order_date' => 20, 'specimen_line1' => 255, 'specimen_line2' => 255, 'signed' => 255,
        'histology_site' => 255,
    ];

    /** @return array{fields:array<string,mixed>,items:array<int,array{kind:string,code:string,detail:string}>} */
    public static function order(array $in): array
    {
        $fields = [];
        foreach (self::TEXT as $key => $max) {
            $fields[$key] = self::str($in[$key] ?? '', $max);
        }
        $fields['clinical_details'] = self::str($in['clinical_details'] ?? '', 2000, true);
        // Unchecked boxes are absent from POST, so presence is the whole signal.
        $fields['is_urgent'] = !empty($in['is_urgent']) ? 1 : 0;
        $fields['is_stat'] = !empty($in['is_stat']) ? 1 : 0;

        $details = is_array($in['detail'] ?? null) ? $in['detail'] : [];
        $items = [];
        $specimens = Catalog::specimenMap();
        foreach (self::codes($in['specimen'] ?? []) as $code) {
            if (isset($specimens[$code])) {
                $items[] = ['kind' => 'specimen', 'code' => $code,
                    'detail' => $specimens[$code]['detail'] ? self::str($details['specimen_' . $code] ?? '', 255) : ''];
            }
        }
        $tests = Catalog::tests();
        foreach (self::codes($in['tests'] ?? []) as $code) {
            if (isset($tests[$code])) {
                $items[] = ['kind' => 'test', 'code' => $code,
                    'detail' => $tests[$code]['detail'] ? self::str($details[$code] ?? '', 255) : ''];
            }
        }
        return ['fields' => $fields, 'items' => $items];
    }

    /** Problems that stop an order being finalized (a draft may be incomplete). */
    public static function finalizeErrors(array $clean): array
    {
        $errors = [];
        if ($clean['fields']['patient_name'] === '') {
            $errors[] = 'Patient name is required.';
        }
        $hasTest = (bool)array_filter($clean['items'], fn($i) => $i['kind'] === 'test');
        if (!$hasTest && $clean['fields']['histology_site'] === '') {
            $errors[] = 'Tick at least one test, or state a histology site.';
        }
        return $errors;
    }

    /** @return string[] */
    private static function codes(mixed $v): array
    {
        return is_array($v) ? array_values(array_unique(array_filter($v, 'is_string'))) : [];
    }

    private static function str(mixed $v, int $max, bool $multiline = false): string
    {
        $s = is_scalar($v) ? (string)$v : '';
        $s = strip_tags($s);
        $s = $multiline ? preg_replace('/[^\P{C}\n]/u', '', $s) : preg_replace('/\p{C}/u', ' ', $s);
        return mb_substr(trim((string)$s), 0, $max);
    }
}
