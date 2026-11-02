<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_chrome.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\RadiologyRequisition\Catalog;
use OpenEMR\Modules\RadiologyRequisition\Chart;

$pid = radreq_pid();
if ($pid <= 0) {
    header('Location: index.php');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $o = radreq_load_order($id, $pid);
    if ($o['status'] !== 'draft') {
        header('Location: print.php?id=' . urlencode((string)$id));
        exit;
    }
} else {
    $o = Chart::defaults($pid, radreq_user_id(), radreq_encounter() ?: null);
}

// The browser builder wants safety answers by question position, in catalog order.
$studies = [];
foreach ($o['studies'] ?? [] as $s) {
    $answers = [];
    foreach (Catalog::safety()[$s['modality']] ?? [] as $q) {
        $answers[] = $s['safety'][$q] ?? 'unknown';
    }
    $studies[] = ['safety' => $answers] + $s;
}
$boot = [
    'catalog' => Catalog::forBrowser(),
    'studies' => $studies,
    'max' => Catalog::MAX_STUDIES,
    'i18n' => [
        'study' => xl('Study'), 'remove' => xl('Remove'), 'modality' => xl('Modality *'), 'examination' => xl('Examination *'),
        'laterality' => xl('Laterality'), 'contrast' => xl('Contrast / protocol'),
        'instructions' => xl('Views / sequences / special instructions'), 'otherExam' => xl('Other examination (if selected)'),
        'safetyTitle' => xl('safety screening'), 'creatinine' => xl('Creatinine (µmol/L or mg/dL; specify)'),
        'egfr' => xl('eGFR (mL/min/1.73m²)'), 'renalDate' => xl('Renal results date'), 'safetyNotes' => xl('Safety / contrast notes'),
        'tooMany' => xl('That is the maximum number of studies for one requisition.'),
    ],
];
$v = fn(string $k): string => (string)($o[$k] ?? '');
$sel = function (string $name, array $options, string $current): void {
    echo '<select name="' . attr($name) . '">';
    foreach ($options as $opt) {
        echo '<option value="' . attr($opt) . '"' . ($opt === $current ? ' selected' : '') . '>' . text($opt) . '</option>';
    }
    echo '</select>';
};
$err = (string)($_GET['err'] ?? '');
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Radiology Requisition'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <link rel="stylesheet" href="assets/radreq.css">
</head>
<body class="body_top">
<div class="radreq">
<?php radreq_head(); ?>
<form method="post" action="save.php" id="radreq-form" autocomplete="off">
    <input type="hidden" name="csrf_token_form" value="<?php echo attr(radreq_csrf()); ?>">
    <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">

    <?php if ($err !== '') { ?>
        <div class="alert alert-danger"><?php echo nl2br(text($err)); ?></div>
    <?php } elseif (isset($_GET['saved'])) { ?>
        <div class="alert alert-success"><?php echo xlt('Saved'); ?></div>
    <?php } ?>
    <div class="notice"><?php echo xlt('Screening answers start as Unknown / Not assessed. Review every applicable question before finalizing. Contrast administration and imaging protocol are subject to radiology review and local policy.'); ?></div>

    <section class="card2">
        <h2><?php echo xlt('Patient & Encounter'); ?></h2>
        <div class="grid">
            <div class="span2"><label><?php echo xlt('Patient full name *'); ?></label><input type="text" name="patient_name" maxlength="255" value="<?php echo attr($v('patient_name')); ?>"></div>
            <div><label><?php echo xlt('Medical record number *'); ?></label><input type="text" name="mrn" maxlength="64" value="<?php echo attr($v('mrn')); ?>"></div>
            <div><label><?php echo xlt('Date of birth'); ?></label><input type="date" name="dob" value="<?php echo attr($v('dob')); ?>"></div>
            <div><label><?php echo xlt('Sex recorded at birth'); ?></label>
                <select name="sex">
                    <option value=""><?php echo xlt('Not recorded'); ?></option>
                    <?php foreach (Catalog::SEX as $s) { ?><option value="<?php echo attr($s); ?>"<?php echo $s === $v('sex') ? ' selected' : ''; ?>><?php echo text($s); ?></option><?php } ?>
                </select></div>
            <div><label><?php echo xlt('Encounter ID'); ?></label><input type="text" name="encounter_ref" maxlength="64" value="<?php echo attr($v('encounter_ref')); ?>"></div>
            <div><label><?php echo xlt('Order date/time'); ?></label><input type="datetime-local" name="order_datetime" value="<?php echo attr($v('order_datetime')); ?>"></div>
            <div><label><?php echo xlt('Facility / clinic'); ?></label><input type="text" name="facility" maxlength="255" value="<?php echo attr($v('facility')); ?>"></div>
            <div class="span2"><label><?php echo xlt('Contact number'); ?></label><input type="text" name="contact" maxlength="64" value="<?php echo attr($v('contact')); ?>"></div>
            <div class="span2"><label><?php echo xlt('Relevant allergies (describe or unknown)'); ?></label><input type="text" name="allergies" maxlength="255" placeholder="<?php echo xla('Not assessed'); ?>" value="<?php echo attr($v('allergies')); ?>"></div>
        </div>
    </section>

    <section class="card2">
        <h2><?php echo xlt('Clinical Details'); ?></h2>
        <div class="grid">
            <div class="span2"><label><?php echo xlt('Clinical history / symptoms *'); ?></label><textarea name="history" maxlength="4000" placeholder="<?php echo xla('Symptoms, duration, examination findings...'); ?>"><?php echo text($v('history')); ?></textarea></div>
            <div class="span2"><label><?php echo xlt('Clinical question / indication *'); ?></label><textarea name="indication" maxlength="4000" placeholder="<?php echo xla('What question should the radiologist answer?'); ?>"><?php echo text($v('indication')); ?></textarea></div>
            <div class="span2"><label><?php echo xlt('Working diagnosis / ICD-10'); ?></label><input type="text" name="diagnosis" maxlength="255" value="<?php echo attr($v('diagnosis')); ?>"></div>
            <div><label><?php echo xlt('Priority'); ?></label><?php $sel('priority', Catalog::PRIORITY, $v('priority')); ?></div>
            <div><label><?php echo xlt('Patient setting'); ?></label><?php $sel('setting', Catalog::SETTING, $v('setting')); ?></div>
            <div class="span4"><label><?php echo xlt('Additional information / previous imaging'); ?></label><textarea name="additional" maxlength="4000" placeholder="<?php echo xla('Prior study date, relevant procedures, medications, clinical concerns...'); ?>"><?php echo text($v('additional')); ?></textarea></div>
        </div>
    </section>

    <section class="card2">
        <h2><?php echo xlt('Requested Imaging Studies'); ?></h2>
        <p class="hint"><?php echo xlt('Add as many studies as required. Each study has its own modality, protocol and safety screening. Choose "Other" and describe the examination if it is not in the list.'); ?></p>
        <div id="radreq-studies"></div>
        <div class="actions"><button type="button" class="btn btn-secondary" id="radreq-add">+ <?php echo xlt('Add another study'); ?></button></div>
    </section>

    <section class="card2">
        <h2><?php echo xlt('Referring Clinician & Authorization'); ?></h2>
        <div class="grid">
            <div class="span2"><label><?php echo xlt('Ordering clinician *'); ?></label><input type="text" name="clinician" maxlength="255" value="<?php echo attr($v('clinician')); ?>"></div>
            <div><label><?php echo xlt('Provider registration / ID'); ?></label><input type="text" name="provider_id" maxlength="64" value="<?php echo attr($v('provider_id')); ?>"></div>
            <div><label><?php echo xlt('Contact / extension'); ?></label><input type="text" name="provider_contact" maxlength="64" value="<?php echo attr($v('provider_contact')); ?>"></div>
            <div class="span4"><label><?php echo xlt('Radiology department / destination'); ?></label><input type="text" name="destination" maxlength="255" value="<?php echo attr($v('destination')); ?>"></div>
        </div>
        <p class="hint"><?php echo xlt('Finalizing locks the requisition and records the logged-in user and time as the authorization. It is an audit record, not a cryptographic signature.'); ?></p>
        <div class="ack">
            <input type="checkbox" name="ack_unknown" value="1" id="ack_unknown">
            <label for="ack_unknown"><?php echo xlt('Finalize even if some safety screening answers are still Unknown / Not assessed (I have reviewed them).'); ?></label>
        </div>
    </section>

    <div class="actions">
        <button type="submit" name="do" value="save" class="btn btn-primary"><?php echo xlt('Save draft'); ?></button>
        <button type="submit" name="do" value="finalize" class="btn btn-success"><?php echo xlt('Finalize and print'); ?></button>
        <a class="btn btn-secondary" href="index.php"><?php echo xlt('Back to list'); ?></a>
    </div>
</form>
</div>
<script>window.RADREQ = <?php echo json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="assets/radreq.js"></script>
</body>
</html>
