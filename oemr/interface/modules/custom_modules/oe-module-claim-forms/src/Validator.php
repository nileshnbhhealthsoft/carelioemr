<?php

namespace OpenEMR\Modules\ClaimForms;

/** Pure checks, with no database access. Returns a list of messages; an empty list means the claim can be generated. */
class Validator
{
    /**
     * @param array<string,?string> $images
     * @return string[]
     */
    public static function validate(array $map, array $payload, array $images): array
    {
        $errors = [];
        $max = (int)($map['max_line_items'] ?? 3);

        foreach ($map['pages'] as $page) {
            foreach (($page['fields'] ?? []) as $id => $f) {
                if (empty($f['required'])) {
                    continue;
                }
                if (($f['type'] ?? '') === 'image') {
                    if (empty($images[$f['source']])) {
                        $errors[] = 'No signature image on file for the logged-in provider. Upload one under Signature and Stamp.';
                    }
                    continue;
                }
                $val = Payload::get($payload, $f['source']);
                if ($val === null || trim((string)$val) === '') {
                    $errors[] = 'Missing: ' . self::label($id);
                }
            }
        }

        $lines = array_values(array_filter($payload['lines'] ?? [], fn($l) => !empty($l['desc']) || !empty($l['amount'])));
        // Forms with max_line_items 0 (e.g. GTM) collect their services in their own table, not in the generic line items.
        if ($max > 0 && count($lines) === 0) {
            $errors[] = 'Add at least one line item.';
        }
        if ($max > 0 && count($lines) > $max) {
            $errors[] = "The form has room for $max line items; this claim has " . count($lines) . '. Split it into a second claim.';
        }

        $icd = array_values(array_filter(array_map('trim', $payload['icd'] ?? []), fn($c) => $c !== ''));
        if (count($icd) !== count(array_unique($icd))) {
            $errors[] = 'The same diagnosis code is selected more than once.';
        }
        foreach ($max > 0 ? $lines : [] as $i => $l) {
            $dx = (int)($l['dx'] ?? 0);
            if ($dx < 1 || $dx > 4 || empty($payload['icd'][$dx - 1] ?? '')) {
                $errors[] = 'Line ' . ($i + 1) . ': the diagnosis number must point to a selected code.';
            }
        }
        return array_values(array_unique($errors));
    }

    private static function label(string $fieldId): string
    {
        return ucfirst(str_replace(['_', 's3'], [' ', ''], $fieldId));
    }
}
