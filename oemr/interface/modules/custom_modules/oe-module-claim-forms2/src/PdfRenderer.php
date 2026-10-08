<?php

namespace OpenEMR\Modules\ClaimForms;

use Mpdf\Mpdf;

/**
 * Stamps claim values onto the insurer's original PDF using the form's map.json.
 * Uses mPDF (bundled with OpenEMR) and its FPDI import. It has no OpenEMR
 * dependencies, so it can be tested on its own.
 *
 * Coordinates in map.json are points, origin top-left, text y = baseline.
 * mPDF works in millimetres, hence PT.
 */
class PdfRenderer
{
    private const PT = 25.4 / 72;
    private const FONT = 'dejavusanscondensed';

    /**
     * @param array<string,?string> $images  map of image source name => PNG path or null
     * @return array{pdf:string,warnings:string[]}
     */
    public static function render(
        array $map,
        string $backgroundPdf,
        array $payload,
        array $images,
        string $generationDateIso,
        string $tempDir
    ): array {
        $payload['total_amount'] = Payload::totalCents($payload);
        $payload['generation_date'] = $generationDateIso;
        // Claims saved before the lab section had dated rows kept one free-text box; print it as the first row.
        if (empty($payload['lab_rows']) && trim((string)($payload['lab_tests'] ?? '')) !== '') {
            $payload['lab_rows'] = [['date' => '', 'test' => trim((string)$payload['lab_tests'])]];
        }
        $payload['dent_total'] = Payload::sumRows($payload, 'dent_rows');
        $payload['opt_total'] = Payload::sumRows($payload, 'opt_rows');
        $warnings = [];

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0750, true);
        }
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [$map['coordinate_system']['page_width'] * self::PT, $map['coordinate_system']['page_height'] * self::PT],
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetAutoPageBreak(false);
        $mpdf->SetCompression(true);
        $pageCount = $mpdf->setSourceFile($backgroundPdf);

        for ($p = 1; $p <= $pageCount; $p++) {
            $mpdf->AddPage();
            $mpdf->useTemplate($mpdf->importPage($p));
            $fields = $map['pages'][(string)$p]['fields'] ?? [];
            foreach ($fields as $id => $f) {
                $type = $f['type'] ?? '';
                if (isset($f['unless']) && (string)Payload::get($payload, $f['unless'][0]) === (string)$f['unless'][1]) {
                    continue;
                }
                if (isset($f['only_if']) && (string)Payload::get($payload, $f['only_if'][0]) !== (string)$f['only_if'][1]) {
                    continue;
                }
                if ($type === 'checkbox') {
                    self::checkbox($mpdf, $f, $payload);
                } elseif ($type === 'text' || $type === 'multiline') {
                    self::text($mpdf, $id, $f, $payload, $warnings);
                } elseif ($type === 'image') {
                    self::image($mpdf, $f, $images[$f['source']] ?? null);
                }
            }
        }
        return ['pdf' => $mpdf->Output('', 'S'), 'warnings' => $warnings];
    }

    private static function checkbox(Mpdf $m, array $f, array $payload): void
    {
        $val = Payload::get($payload, $f['source']);
        if (!empty($f['nonempty'])) {
            // tick whenever the source has something in it (for rows that carry their own tick box)
            if ($val === null || trim((string)$val) === '' || (string)$val === '0') {
                return;
            }
        } elseif ((string)$val !== (string)$f['equals']) {
            return;
        }
        $size = $f['size'] ?? 9;
        $m->SetFont(self::FONT, 'B', $size);
        $w = $m->GetStringWidth($f['mark'] ?? 'X');
        // centre the mark on the printed box; 0.35 em puts the cap height on the box centre
        $m->Text($f['cx'] * self::PT - $w / 2, ($f['cy'] + $size * 0.35) * self::PT, $f['mark'] ?? 'X');
    }

    private static function value(array $f, array $payload): string
    {
        $raw = isset($f['sum_of']) ? Payload::sumKeys($payload, $f['sum_of']) : Payload::get($payload, $f['source'] ?? '');
        if (isset($f['part'])) {
            if ($raw === null || $raw === '') {
                return '';
            }
            $cents = (int)$raw;
            if ($cents === 0 && empty($f['computed'])) {
                return '';
            }
            return $f['part'] === 'dollars' ? Payload::dollars($cents) : Payload::centsPart($cents);
        }
        $s = is_scalar($raw) ? (string)$raw : '';
        if (($f['format'] ?? '') === 'money') {
            $cents = (int)$raw;
            return $cents > 0 ? Payload::dollars($cents) . '.' . Payload::centsPart($cents) : '';
        }
        if (isset($f['format']) && in_array($f['format'], ['DD/MM/YYYY', 'DD/MM/YY', 'DD', 'MM', 'YY'], true)) {
            return Payload::date($s, $f['format']);
        }
        return $s;
    }

    private static function text(Mpdf $m, string $id, array $f, array $payload, array &$warnings): void
    {
        $val = self::value($f, $payload);
        if ($val === '') {
            return;
        }
        $lines = $f['type'] === 'multiline'
            ? array_slice(preg_split('/\R/', $val), 0, (int)($f['maxLines'] ?? 1))
            : [str_replace(["\r", "\n"], ' ', $val)];
        $style = !empty($f['bold']) ? 'B' : '';
        $min = (float)($f['min_size'] ?? 6);

        foreach ($lines as $n => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $size = (float)($f['size'] ?? 9);
            $m->SetFont(self::FONT, $style, $size);
            $maxW = $f['w'] * self::PT;
            $shrink = !empty($f['shrink_to_fit']) || $f['type'] === 'multiline';
            while ($shrink && $m->GetStringWidth($line) > $maxW && $size > $min) {
                $size -= 0.25;
                $m->SetFont(self::FONT, $style, $size);
            }
            if ($m->GetStringWidth($line) > $maxW) {
                $warnings[] = "$id was cut to fit its box";
                while ($line !== '' && $m->GetStringWidth($line . '...') > $maxW) {
                    $line = mb_substr($line, 0, -1);
                }
                $line .= '...';
            }
            $w = $m->GetStringWidth($line);
            $x = match ($f['align'] ?? 'left') {
                'right' => $f['x'] * self::PT - $w,
                'center' => $f['x'] * self::PT - $w / 2,
                default => $f['x'] * self::PT,
            };
            $y = isset($f['lineBaselines'][$n]) ? $f['lineBaselines'][$n] : $f['y'] + $n * ($f['lineHeight'] ?? 0);
            $m->Text($x, $y * self::PT, $line);
        }
    }

    /** Scales to fit inside the box, keeping aspect ratio, anchored bottom-left so a signature sits on the line. */
    private static function image(Mpdf $m, array $f, ?string $path): void
    {
        if (!$path || !is_file($path)) {
            return;
        }
        $info = @getimagesize($path);
        if (!$info || $info[0] < 1 || $info[1] < 1) {
            return;
        }
        $scale = min($f['w'] / $info[0], $f['h'] / $info[1]);
        $w = $info[0] * $scale;
        $h = $info[1] * $scale;
        $m->Image($path, $f['x'] * self::PT, ($f['y'] + $f['h'] - $h) * self::PT, $w * self::PT, $h * self::PT, 'png', '', true, false);
    }
}
