<?php

$mdPath = 'E:/xampp/htdocs/1page/Carelio_Technical_Documentation.md';
$htmlPath = 'E:/xampp/htdocs/1page/Carelio_Technical_Documentation.html';

$content = file_get_contents($mdPath);

// Convert markdown headings and blocks
$lines = explode("\n", $content);
$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>CarelioEMR Technical Documentation</title>';
$html .= '<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #fff; color: #1e293b; max-width: 980px; margin: 40px auto; padding: 0 30px; line-height: 1.6; }
h1 { font-size: 2.2rem; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; }
h2 { font-size: 1.5rem; color: #0369a1; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; margin-top: 36px; margin-bottom: 14px; }
h3 { font-size: 1.2rem; color: #0f172a; margin-top: 24px; margin-bottom: 10px; }
pre { background: #0f172a; color: #f8fafc; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 0.88rem; font-family: Consolas, monospace; }
code { background: #f1f5f9; color: #0284c7; padding: 2px 6px; border-radius: 4px; font-size: 0.88rem; }
pre code { background: transparent; color: inherit; padding: 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem; }
th, td { border: 1px solid #cbd5e1; padding: 10px 14px; text-align: left; }
th { background: #f8fafc; font-weight: 700; color: #475569; }
ul, ol { margin-left: 24px; margin-bottom: 14px; }
li { margin-bottom: 6px; }
p { margin-bottom: 14px; }
hr { border: none; border-top: 1px solid #e2e8f0; margin: 30px 0; }
.btn-print { position: fixed; top: 15px; right: 25px; padding: 8px 16px; background: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
@media print { .btn-print { display: none; } body { max-width: 100%; margin: 0; padding: 0; } h2 { page-break-before: always; } }
</style></head><body>';
$html .= '<button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>';

$inPre = false;
$inTable = false;

foreach ($lines as $line) {
    if (str_starts_with(trim($line), '```')) {
        if ($inPre) {
            $html .= "</code></pre>\n";
            $inPre = false;
        } else {
            $html .= "<pre><code>";
            $inPre = true;
        }
        continue;
    }
    if ($inPre) {
        $html .= htmlspecialchars($line) . "\n";
        continue;
    }

    if (str_starts_with(trim($line), '|')) {
        if (!$inTable) {
            $html .= "<table>\n";
            $inTable = true;
        }
        if (str_contains($line, '---')) {
            continue;
        }
        $cells = array_values(array_filter(array_map('trim', explode('|', $line)), fn($c) => $c !== ''));
        $tag = !str_contains($html, '<tbody>') ? 'th' : 'td';
        $html .= "<tr>" . implode('', array_map(fn($c) => "<$tag>" . htmlspecialchars($c) . "</$tag>", $cells)) . "</tr>\n";
        continue;
    } else {
        if ($inTable) {
            $html .= "</table>\n";
            $inTable = false;
        }
    }

    $trimmed = trim($line);
    if (empty($trimmed)) {
        continue;
    }
    if (str_starts_with($trimmed, '# ')) {
        $html .= "<h1>" . htmlspecialchars(substr($trimmed, 2)) . "</h1>\n";
    } elseif (str_starts_with($trimmed, '## ')) {
        $html .= "<h2>" . htmlspecialchars(substr($trimmed, 3)) . "</h2>\n";
    } elseif (str_starts_with($trimmed, '### ')) {
        $html .= "<h3>" . htmlspecialchars(substr($trimmed, 4)) . "</h3>\n";
    } elseif (str_starts_with($trimmed, '---')) {
        $html .= "<hr>\n";
    } elseif (str_starts_with($trimmed, '* ') || str_starts_with($trimmed, '- ')) {
        $html .= "<li>" . htmlspecialchars(substr($trimmed, 2)) . "</li>\n";
    } else {
        $html .= "<p>" . htmlspecialchars($trimmed) . "</p>\n";
    }
}
if ($inPre) $html .= "</code></pre>\n";
if ($inTable) $html .= "</table>\n";

$html .= "</body></html>";
file_put_contents($htmlPath, $html);
echo "HTML generated successfully at {$htmlPath}\n";

