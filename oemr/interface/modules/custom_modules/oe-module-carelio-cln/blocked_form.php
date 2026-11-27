<?php
/**
 * Shown when LoadEncounterFormFilterEvent blocks an unapproved governed template.
 */
require_once(__DIR__ . "/../../../globals.php");
use OpenEMR\Core\Header;

$formName = htmlspecialchars((string)($_GET['formname'] ?? 'Unknown Form'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clinical Governance Block</title>
    <?php Header::setupHeader(['common']); ?>
</head>
<body class="p-4 bg-light">
<div class="container mt-4">
    <div class="alert alert-danger shadow-sm">
        <h4 class="alert-heading"><i class="fa fa-lock mr-2"></i>Clinical Governance Block</h4>
        <p class="mb-2">
            The clinical template <code><?php echo $formName; ?></code> is currently in <strong>Draft</strong> or <strong>Pending Approval</strong> status in Carelio's clinical governance registry (<code>hms_content_approval</code>).
        </p>
        <hr>
        <p class="mb-0 small">
            Per hospital policy, unapproved clinical forms cannot be used on patients until certified by the <strong>Medical Director</strong> in <em>Hospital Management (HMS) &rarr; Content Approvals</em>.
        </p>
    </div>
</div>
</body>
</html>
