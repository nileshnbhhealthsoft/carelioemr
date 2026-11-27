<?php
/**
 * Lightweight Follow-Up Recall popup triggered from OpenEMR's native Encounter toolbar.
 */
$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;

if (!AclMain::aclCheckCore('encounters', 'notes') && !AclMain::aclCheckCore('patients', 'demo') && !AclMain::aclCheckCore('admin', 'super')) {
    echo "<h3>Access Denied.</h3>";
    exit;
}

$pid = (int)($_SESSION['pid'] ?? 0);
$encounter = (int)($_SESSION['encounter'] ?? 0);
$userId = (int)($_SESSION['authUserID'] ?? 1);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recallDate = trim($_POST['recall_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    if ($pid <= 0 || $recallDate === '' || $reason === '') {
        $err = 'Active patient, Recall Date, and Reason are required.';
    } else {
        sqlInsert(
            "INSERT INTO `medex_recalls` (`r_PRACTID`, `r_pid`, `r_eventDate`, `r_facility`, `r_provider`, `r_reason`)
             VALUES (1, ?, ?, 1, ?, ?)",
            [$pid, $recallDate, $userId, $reason]
        );
        sqlStatement(
            "INSERT INTO `hms_event` (`event_type`, `pid`, `encounter`, `facility_id`, `actor_id`, `effective_time`, `recorded_time`, `payload`, `status`)
             VALUES ('recall_created', ?, ?, 1, ?, NOW(), NOW(), ?, 'published')",
            [$pid, $encounter ?: null, $userId, json_encode(['recall_date' => $recallDate, 'reason' => $reason])]
        );
        $msg = "Follow-up recall scheduled for $recallDate.";
    }
}

$pat = $pid > 0 ? sqlQuery("SELECT `fname`, `lname`, `pubpid` FROM `patient_data` WHERE `pid` = ? LIMIT 1", [$pid]) : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Schedule Follow-Up Recall from Encounter</title>
    <?php Header::setupHeader(['common']); ?>
</head>
<body class="p-4 bg-light">
<div class="container" style="max-width: 560px;">
    <div class="card shadow-sm p-4">
        <h5 class="mb-2"><i class="fa fa-bell text-warning mr-2"></i>Schedule Follow-Up Recall</h5>
        <p class="text-muted small mb-3">
            Patient: <strong><?php echo $pat ? htmlspecialchars($pat['fname'] . ' ' . $pat['lname'] . ' (' . ($pat['pubpid'] ?: 'PID-' . $pid) . ')') : 'No active patient in session'; ?></strong>
            <?php if ($encounter > 0): ?> &bull; Encounter #<?php echo $encounter; ?><?php endif; ?>
        </p>
        <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label><strong>Recall Due Date *</strong></label>
                <input type="date" name="recall_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
            </div>
            <div class="form-group">
                <label><strong>Follow-Up Reason *</strong></label>
                <input type="text" name="reason" class="form-control" placeholder="e.g., Review lab results, Post-op suture check" required>
            </div>
            <button type="submit" class="btn btn-warning btn-block"><i class="fa fa-save mr-1"></i> Save Follow-Up Recall</button>
        </form>
    </div>
</div>
</body>
</html>
