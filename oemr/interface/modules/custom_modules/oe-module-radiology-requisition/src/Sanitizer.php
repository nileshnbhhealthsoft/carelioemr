<?php

namespace OpenEMR\Modules\RadiologyRequisition;

/**
 * Turns the posted form into the shape the repository stores. Choices must be ones the Catalog defines;
 * free text has tags and control characters removed and is length-capped.
 */
class Sanitizer
{
    private const LINE = [
        'patient_name' => 255, 'mrn' => 64, 'encounter_ref' => 64, 'facility' => 255, 'contact' => 64, 'allergies' => 255,
        'diagnosis' => 255, 'clinician' => 255, 'provider_id' => 64, 'provider_contact' => 64, 'destination' => 255,
    ];
    private const LONG = ['history', 'indication', 'additional'];

    /** @return array{fields:array<string,mixed>,studies:array<int,array<string,mixed>>} */
    public static function order(array $in): array
    {
        $f = [];
        foreach (self::LINE as $key => $max) {
            $f[$key] = self::str($in[$key] ?? '', $max);
        }
        foreach (self::LONG as $key) {
            $f[$key] = self::str($in[$key] ?? '', 4000, true);
        }
        $f['dob'] = self::date($in['dob'] ?? '');
        $f['sex'] = self::choice($in['sex'] ?? '', Catalog::SEX, '');
        $f['priority'] = self::choice($in['priority'] ?? '', Catalog::PRIORITY, 'Routine');
        $f['setting'] = self::choice($in['setting'] ?? '', Catalog::SETTING, 'Outpatient');
        $f['order_datetime'] = self::datetime($in['order_datetime'] ?? '');

        $studies = [];
        $raw = is_array($in['studies'] ?? null) ? array_values($in['studies']) : [];
        foreach (array_slice($raw, 0, Catalog::MAX_STUDIES) as $s) {
            if (is_array($s) && ($study = self::study($s)) !== null) {
                $studies[] = $study;
            }
        }
        return ['fields' => $f, 'studies' => $studies];
    }

    /** Problems that stop an order being finalized (a draft may be incomplete). */
    public static function finalizeErrors(array $clean, bool $acknowledgedUnknown): array
    {
        $e = [];
        $f = $clean['fields'];
        foreach (['patient_name' => 'Patient name', 'mrn' => 'Medical record number', 'history' => 'Clinical history',
                  'indication' => 'Clinical question / indication', 'clinician' => 'Ordering clinician'] as $k => $label) {
            if ($f[$k] === '') {
                $e[] = "$label is required.";
            }
        }
        if (!$clean['studies']) {
            $e[] = 'Add at least one imaging study.';
        }
        $unknown = 0;
        foreach ($clean['studies'] as $i => $s) {
            $n = $i + 1;
            if ($s['examination'] === '') {
                $e[] = "Study $n: choose an examination.";
            }
            if ($s['examination'] === 'Other' && $s['other_examination'] === '') {
                $e[] = "Study $n: describe the other examination.";
            }
            $unknown += count(array_filter($s['safety'], fn($a) => $a === 'unknown'));
        }
        if ($unknown > 0 && !$acknowledgedUnknown) {
            $e[] = "$unknown safety screening answer(s) are still Unknown / Not assessed. "
                . 'Review them, or tick the confirmation box to finalize anyway.';
        }
        return $e;
    }

    /** Number of safety answers still "unknown" across studies (used for the print-page warning). */
    public static function unknownCount(array $studies): int
    {
        $n = 0;
        foreach ($studies as $s) {
            $n += count(array_filter($s['safety'] ?? [], fn($a) => $a === 'unknown'));
        }
        return $n;
    }

    private static function study(array $s): ?array
    {
        $exams = Catalog::exams();
        $modality = (string)($s['modality'] ?? '');
        if (!isset($exams[$modality])) {
            return null;
        }
        $exam = self::choice($s['examination'] ?? '', $exams[$modality], '');
        $questions = Catalog::safety()[$modality] ?? [];
        $given = is_array($s['safety'] ?? null) ? $s['safety'] : [];
        $safety = [];
        foreach ($questions as $qi => $question) {
            $safety[$question] = self::choice($given[$qi] ?? '', array_keys(Catalog::ANSWERS), 'unknown');
        }
        return [
            'modality' => $modality,
            'examination' => $exam,
            'laterality' => self::choice($s['laterality'] ?? '', Catalog::LATERALITY, 'Not applicable'),
            'contrast_protocol' => self::choice($s['contrast_protocol'] ?? '', Catalog::CONTRAST, Catalog::defaultContrast($modality)),
            'instructions' => self::str($s['instructions'] ?? '', 255),
            'other_examination' => self::str($s['other_examination'] ?? '', 255),
            'creatinine' => self::str($s['creatinine'] ?? '', 64),
            'egfr' => self::str($s['egfr'] ?? '', 32),
            'renal_date' => self::date($s['renal_date'] ?? ''),
            'safety_notes' => self::str($s['safety_notes'] ?? '', 255),
            'safety' => $safety,
        ];
    }

    /** @param string[] $allowed */
    private static function choice(mixed $v, array $allowed, string $fallback): string
    {
        return is_string($v) && in_array($v, $allowed, true) ? $v : $fallback;
    }

    private static function date(mixed $v): ?string
    {
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d', $v) : false;
        return $d && $d->format('Y-m-d') === $v ? $v : null;
    }

    private static function datetime(mixed $v): ?string
    {
        $d = is_string($v) ? \DateTime::createFromFormat('!Y-m-d\TH:i', $v) : false;
        return $d && $d->format('Y-m-d\TH:i') === $v ? $d->format('Y-m-d H:i:s') : null;
    }

    private static function str(mixed $v, int $max, bool $multiline = false): string
    {
        $s = is_scalar($v) ? (string)$v : '';
        $s = strip_tags($s);
        $s = $multiline ? preg_replace('/[^\P{C}\n]/u', '', $s) : preg_replace('/\p{C}/u', ' ', $s);
        return mb_substr(trim((string)$s), 0, $max);
    }
}
