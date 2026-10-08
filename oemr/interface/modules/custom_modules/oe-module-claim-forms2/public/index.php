<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\ClaimForms\ClaimRepository;
use OpenEMR\Modules\ClaimForms\DraftBuilder;
use OpenEMR\Modules\ClaimForms\FormRegistry;
use OpenEMR\Modules\ClaimForms\Payload;

$pid = claimforms_pid();
$forms = FormRegistry::all();
$self = 'index.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Insurance Claim Forms'); ?></title>
    <?php Header::setupHeader(['common']); ?>
</head>
<body class="body_top">
<div class="container-fluid mt-3">
    <h3><?php echo xlt('Insurance Claim Forms'); ?></h3>
    <?php if ($pid <= 0) { ?>
        <div class="alert alert-info"><?php echo xlt('Select a patient first, then open this page again.'); ?></div>
    <?php } else {
        $claims = ClaimRepository::listForPatient($pid);
        $encs = DraftBuilder::encounterChoices($pid);
        ?>
        <div class="row">
            <div class="col-md-6">
                <h5><?php echo xlt('New claim'); ?></h5>
                <form method="get" action="claim.php">
                    <div class="form-group">
                        <label for="form"><?php echo xlt('Form'); ?></label>
                        <select class="form-control" name="form" id="form">
                            <?php foreach ($forms as $id => $def) { ?>
                                <option value="<?php echo attr($id); ?>"><?php echo text($def['title']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo xlt('Encounters to claim'); ?></label>
                        <?php if (!$encs) { ?>
                            <p class="text-muted"><?php echo xlt('This patient has no encounters.'); ?></p>
                        <?php } foreach ($encs as $e) { ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="enc[]" id="enc<?php echo attr($e['encounter']); ?>" value="<?php echo attr($e['encounter']); ?>">
                                <label class="form-check-label" for="enc<?php echo attr($e['encounter']); ?>">
                                    <?php echo text(substr((string)$e['date'], 0, 10)); ?>
                                    <?php echo text($e['facility_name'] ?? ''); ?>
                                    <span class="text-muted"><?php echo text($e['reason'] ?? ''); ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo xlt('Start claim'); ?></button>
                </form>
            </div>
            <div class="col-md-6">
                <h5><?php echo xlt('Claims for this patient'); ?></h5>
                <?php if (!$claims) { ?>
                    <p class="text-muted"><?php echo xlt('None yet.'); ?></p>
                <?php } else { ?>
                    <table class="table table-sm">
                        <thead><tr><th>#</th><th><?php echo xlt('Form'); ?></th><th><?php echo xlt('Status'); ?></th><th><?php echo xlt('Total'); ?></th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($claims as $c) { ?>
                            <tr>
                                <td><?php echo text($c['id']); ?></td>
                                <td><?php echo text($forms[$c['form_id']]['title'] ?? $c['form_id']); ?></td>
                                <td><?php echo text($c['status']); ?><?php echo $c['revision'] ? ' r' . text($c['revision']) : ''; ?></td>
                                <td><?php echo text(Payload::dollars((int)$c['total_cents']) . '.' . Payload::centsPart((int)$c['total_cents'])); ?></td>
                                <td>
                                    <a href="claim.php?id=<?php echo attr_url($c['id']); ?>"><?php echo xlt('Open'); ?></a>
                                    <?php if ($c['status'] !== 'draft') { ?>
                                        | <a href="download.php?id=<?php echo attr_url($c['id']); ?>"><?php echo xlt('PDF'); ?></a>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                <p class="mt-3"><a href="signature.php"><?php echo xlt('My signature and stamp'); ?></a></p>
            </div>
        </div>
    <?php } ?>
</div>
</body>
</html>
