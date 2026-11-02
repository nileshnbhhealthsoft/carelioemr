<?php
namespace Carelio\DentalEncounter;

final class Report
{
    private static function escape($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function render(array $data, int $revision): string
    {
        $schema = Payload::schema();
        $html = '<div class="dental-encounter-report"><h4>Dental Encounter</h4><p>Revision ' . $revision . '</p>';
        $html .= '<table style="width:100%;border-collapse:collapse">';
        foreach ($data['fields'] ?? [] as $key => $value) {
            if ($value === '' || $value === false) {
                continue;
            }
            $label = $schema['fields'][$key]['label'] ?? $key;
            $html .= '<tr><th style="text-align:left;vertical-align:top;padding:3px 12px 3px 0">'
                . self::escape($label) . '</th><td>' . nl2br(self::escape($value === true ? 'Yes' : $value)) . '</td></tr>';
        }
        foreach ($data['groups'] ?? [] as $key => $values) {
            if ($values) {
                $label = $schema['groupLabels'][$key] ?? preg_replace('/([A-Z])/', ' $1', $key);
                $html .= '<tr><th style="text-align:left;vertical-align:top">' . self::escape(ucfirst($label))
                    . '</th><td>' . self::escape(implode(', ', $values)) . '</td></tr>';
            }
        }
        $html .= '</table>';
        $subtotal = array_sum(array_map(static fn($row) => (float)($row['amount'] ?? 0), $data['tables']['billing'] ?? []));
        $total = max(0, $subtotal - (float)($data['fields']['billDiscount'] ?? 0) + (float)($data['fields']['billTax'] ?? 0));
        $balance = $total - (float)($data['fields']['billPaid'] ?? 0);
        $html .= '<p>Billing notes: subtotal XCD ' . number_format($subtotal, 2)
            . '; estimate XCD ' . number_format($total, 2) . '; balance XCD ' . number_format($balance, 2)
            . '. These entries do not post charges or payments to OpenEMR billing.</p>';
        foreach (['adult' => 'Permanent teeth', 'pediatric' => 'Primary teeth'] as $dentition => $label) {
            if (empty($data['teeth'][$dentition])) {
                continue;
            }
            $html .= '<h5>' . $label . ' — Universal identifiers</h5><ul>';
            foreach ($data['teeth'][$dentition] as $id => $tooth) {
                $html .= '<li><strong>' . self::escape($id) . '</strong>: '
                    . self::escape(implode(', ', $tooth['findings'])) . '; '
                    . self::escape(implode(', ', $tooth['surfaces'])) . ' '
                    . nl2br(self::escape($tooth['notes'])) . '</li>';
            }
            $html .= '</ul>';
        }
        $titles = ['dx' => 'Diagnoses', 'tx' => 'Treatment plan', 'rx' => 'Medication documentation', 'billing' => 'Billing notes (XCD)'];
        foreach (Payload::TABLES as $table => $columns) {
            $rows = $data['tables'][$table] ?? [];
            if (!$rows) {
                continue;
            }
            $html .= '<h5>' . $titles[$table] . '</h5><table style="width:100%;border-collapse:collapse"><thead><tr>';
            foreach ($columns as $column) {
                $html .= '<th style="text-align:left;border-bottom:1px solid #888">' . self::escape(ucfirst($column)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($columns as $column) {
                    $html .= '<td style="vertical-align:top;padding:4px">' . nl2br(self::escape($row[$column] ?? '')) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        if (!empty($data['signature'])) {
            $html .= '<p>Signature image (OpenEMR e-sign status is shown separately)</p><img alt="Signature image"'
                . ' style="max-width:500px;max-height:120px" src="' . self::escape($data['signature']) . '">';
        }
        return $html . '</div>';
    }
}
