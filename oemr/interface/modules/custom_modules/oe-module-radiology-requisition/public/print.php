<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_chrome.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\RadiologyRequisition\Catalog;
use OpenEMR\Modules\RadiologyRequisition\OrderRepository;
use OpenEMR\Modules\RadiologyRequisition\Sanitizer;

$pid = radreq_pid();
$o = radreq_load_order((int)($_GET['id'] ?? 0), $pid);
OrderRepository::event((int)$o['id'], radreq_user_id(), 'viewed');
$unknown = Sanitizer::unknownCount($o['studies']);
$answers = Catalog::ANSWERS;
$kv = function (string $label, string $value, string $cls = ''): void {
    echo '<div' . ($cls !== '' ? ' class="' . attr($cls) . '"' : '') . '><div class="k">' . xlt($label) . '</div><div class="v">' . text($value) . '</div></div>';
};
$statusText = ['draft' => xl('DRAFT - not finalized'), 'final' => xl('Final'), 'cancelled' => xl('CANCELLED')][$o['status']] ?? $o['status'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Radiology Requisition'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <link rel="stylesheet" href="assets/radreq.css">
</head>
<body class="body_top">
<div class="radreq radreq-doc">
<?php radreq_head(); ?>
<div class="actions noprint">
    <?php if ($o['status'] !== 'cancelled') { ?>
        <button type="button" class="btn btn-primary" onclick="window.print()"><?php echo xlt('Print / Save as PDF'); ?></button>
    <?php } ?>
    <?php if ($o['status'] === 'draft') { ?>
        <a class="btn btn-secondary" href="order.php?id=<?php echo attr_url($o['id']); ?>"><?php echo xlt('Edit'); ?></a>
    <?php } ?>
    <a class="btn btn-secondary" href="index.php"><?php echo xlt('Back to list'); ?></a>
    <?php if ($o['status'] !== 'cancelled') { ?>
        <form method="post" action="save.php" class="d-inline" onsubmit="return confirm('<?php echo xla('Cancel this requisition?'); ?>');">
            <input type="hidden" name="csrf_token_form" value="<?php echo attr(radreq_csrf()); ?>">
            <input type="hidden" name="id" value="<?php echo attr((string)$o['id']); ?>">
            <button type="submit" name="do" value="cancel" class="btn btn-outline-danger"><?php echo xlt('Cancel requisition'); ?></button>
        </form>
    <?php } ?>
</div>
<?php if ($unknown > 0) { ?>
    <div class="alert alert-warning"><?php echo text($unknown); ?> <?php echo xlt('safety screening answer(s) are Unknown / Not assessed.'); ?></div>
<?php } ?>

<section class="card2">
    <h2><?php echo xlt('Patient & Encounter'); ?> <span class="badge2<?php echo $o['status'] === 'final' ? '' : ' warn'; ?>"><?php echo text($statusText); ?></span></h2>
    <div class="kv">
        <?php
        $kv('Patient full name', (string)$o['patient_name'], 'span2');
        $kv('Medical record number', (string)$o['mrn']);
        $kv('Date of birth', (string)$o['dob']);
        $kv('Sex recorded at birth', (string)$o['sex']);
        $kv('Encounter ID', (string)$o['encounter_ref']);
        $kv('Order date/time', str_replace('T', ' ', (string)$o['order_datetime']));
        $kv('Facility / clinic', (string)$o['facility']);
        $kv('Contact number', (string)$o['contact'], 'span2');
        $kv('Relevant allergies', (string)($o['allergies'] !== '' ? $o['allergies'] : xl('Not assessed')), 'span2');
        $kv('Priority', (string)$o['priority']);
        $kv('Patient setting', (string)$o['setting']);
        $kv('Working diagnosis / ICD-10', (string)$o['diagnosis'], 'span2');
        ?>
    </div>
</section>

<section class="card2">
    <h2><?php echo xlt('Clinical Details'); ?></h2>
    <p class="k hint"><?php echo xlt('Clinical history / symptoms'); ?></p><div class="block"><?php echo text($o['history']); ?></div>
    <p class="k hint"><?php echo xlt('Clinical question / indication'); ?></p><div class="block"><?php echo text($o['indication']); ?></div>
    <?php if ($o['additional'] !== '' && $o['additional'] !== null) { ?>
        <p class="k hint"><?php echo xlt('Additional information / previous imaging'); ?></p><div class="block"><?php echo text($o['additional']); ?></div>
    <?php } ?>
</section>

<section class="card2">
    <h2><?php echo xlt('Requested Imaging Studies'); ?></h2>
    <?php foreach ($o['studies'] as $i => $s) { ?>
        <article class="study">
            <div class="studyhead"><h3><?php echo xlt('Study'); ?> <?php echo text($i + 1); ?>: <?php echo text($s['modality']); ?> — <?php echo text($s['examination'] === 'Other' && $s['other_examination'] !== '' ? $s['other_examination'] : $s['examination']); ?></h3></div>
            <div class="kv">
                <?php
                $kv('Laterality', (string)$s['laterality']);
                $kv('Contrast / protocol', (string)$s['contrast_protocol'], 'span2');
                $kv('Views / sequences / special instructions', (string)$s['instructions'], 'span4');
                if (in_array($s['modality'], Catalog::RENAL_BLOCK, true)) {
                    $kv('Creatinine', (string)$s['creatinine']);
                    $kv('eGFR (mL/min/1.73m²)', (string)$s['egfr']);
                    $kv('Renal results date', (string)$s['renal_date']);
                }
                if ($s['safety_notes'] !== '') {
                    $kv('Safety / contrast notes', (string)$s['safety_notes'], 'span4');
                }
                ?>
            </div>
            <?php if (Catalog::safety()[$s['modality']] ?? []) { ?>
                <div class="safety">
                    <h4><?php echo text($s['modality']); ?> <?php echo xlt('safety screening'); ?></h4>
                    <table class="sq">
                        <?php foreach (Catalog::safety()[$s['modality']] as $q) {
                            $a = $s['safety'][$q] ?? 'unknown'; ?>
                            <tr><td><?php echo xlt($q); ?></td><td class="answer-<?php echo attr($a); ?>"><?php echo xlt($answers[$a] ?? $a); ?></td></tr>
                        <?php } ?>
                    </table>
                </div>
            <?php } ?>
        </article>
    <?php } ?>
    <?php if (!$o['studies']) { ?><p class="hint"><?php echo xlt('No studies on this requisition.'); ?></p><?php } ?>
</section>

<section class="card2">
    <h2><?php echo xlt('Referring Clinician & Authorization'); ?></h2>
    <div class="kv">
        <?php
        $kv('Ordering clinician', (string)$o['clinician'], 'span2');
        $kv('Provider registration / ID', (string)$o['provider_id']);
        $kv('Contact / extension', (string)$o['provider_contact']);
        $kv('Radiology department / destination', (string)$o['destination'], 'span4');
        ?>
    </div>
    <p class="sigline">
        <?php if ($o['status'] === 'final') {
            echo xlt('Finalized electronically by') . ' <strong>' . text($o['finalized_by_name']) . '</strong> ' . xlt('on') . ' ' . text($o['finalized_at']);
        } else {
            echo '<strong>' . text($statusText) . '</strong>';
        } ?>
    </p>
</section>
<p class="hint"><?php echo xlt('Carelio Radiology Requisition. Contrast administration and imaging protocol are subject to radiology review and local policy.'); ?></p>
</div>
</body>
</html>
