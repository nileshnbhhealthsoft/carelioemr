<?php
/**
 * Carelio Hospital EHR - Module 5: CPOE (Orders & Results)
 * Conforming to Specification Section 18.5 (Pages 39-40)
 */

use OpenEMR\Modules\CarelioCpoe\Services\CpoeService;

CpoeService::ensureSchemaAndDefaults((int)($_SESSION['authUserID'] ?? 1));

$orderSets = CpoeService::getOrderSets();
$cosignQueue = CpoeService::getCosignQueue();
$alerts = CpoeService::getAlerts();
$alertRules = CpoeService::getAlertRules();
$recentOrders = CpoeService::getRecentOrders();
$reports = CpoeService::getCpoeReports();

// Dropdown lists from OpenEMR
$patients = [];
$pRows = sqlStatement("SELECT `pid`, `pubpid`, `fname`, `lname` FROM `patient_data` ORDER BY `pid` DESC LIMIT 200");
while ($r = sqlFetchArray($pRows)) { $patients[] = $r; }

$encounters = [];
$eRows = sqlStatement("SELECT e.encounter, e.pid, e.date, e.class_code, p.fname, p.lname FROM `form_encounter` e LEFT JOIN `patient_data` p ON p.pid = e.pid ORDER BY e.id DESC LIMIT 100");
while ($r = sqlFetchArray($eRows)) { $encounters[] = $r; }

$providers = [];
$uRows = sqlStatement("SELECT `id`, `fname`, `lname` FROM `users` WHERE `active` = 1 AND `authorized` = 1 ORDER BY `lname` ASC");
while ($r = sqlFetchArray($uRows)) { $providers[] = $r; }

$pendingCosignCount = count(array_filter($cosignQueue, fn($c) => $c['status'] === 'pending_cosign'));
$unackAlertCount = count(array_filter($alerts, fn($a) => $a['status'] !== 'acknowledged'));
?>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-left-primary p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Clinical Order Sets</h6>
                    <h3 class="mb-0 font-weight-bold text-primary"><?php echo count($orderSets); ?></h3>
                    <small class="text-muted">Governed 1-Click Bundles</small>
                </div>
                <div class="bg-primary text-white rounded-circle p-3"><i class="fa fa-list-ol fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-warning p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Pending Co-Signature</h6>
                    <h3 class="mb-0 font-weight-bold text-warning"><?php echo $pendingCosignCount; ?></h3>
                    <small class="text-muted">Resident High-Risk Queue</small>
                </div>
                <div class="bg-warning text-white rounded-circle p-3"><i class="fa fa-pencil-square-o fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-danger p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Critical / Panic Alerts</h6>
                    <h3 class="mb-0 font-weight-bold text-danger"><?php echo $unackAlertCount; ?></h3>
                    <small class="text-muted">Unacknowledged / Escalated</small>
                </div>
                <div class="bg-danger text-white rounded-circle p-3"><i class="fa fa-bell fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-left-success p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Recent Orders</h6>
                    <h3 class="mb-0 font-weight-bold text-success"><?php echo count($recentOrders); ?></h3>
                    <small class="text-muted">OpenEMR Procedure Orders</small>
                </div>
                <div class="bg-success text-white rounded-circle p-3"><i class="fa fa-flask fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<div id="cpoeAlertBox" class="alert d-none mb-3" role="alert"></div>

<ul class="nav nav-tabs mb-3" id="cpoeSubTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#cpoe-ordersets" role="tab">
            <i class="fa fa-list-ol mr-1"></i> 1-Click Order Sets
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#cpoe-cosign" role="tab">
            <i class="fa fa-pencil-square-o mr-1"></i> Resident Co-Signature Queue (<?php echo $pendingCosignCount; ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#cpoe-alerts" role="tab">
            <i class="fa fa-exclamation-triangle mr-1"></i> Critical Alerts &amp; Results (<?php echo $unackAlertCount; ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#cpoe-reports" role="tab">
            <i class="fa fa-bar-chart mr-1"></i> CPOE Reports
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: 1-CLICK CLINICAL ORDER SETS -->
    <div class="tab-pane fade show active" id="cpoe-ordersets" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-bolt text-primary mr-1"></i> Apply Order Set</h5>
                    <p class="text-muted small">Draft or pending order sets are automatically blocked by Medical Director governance.</p>
                    <form id="formApplyOrderSet">
                        <input type="hidden" name="action" value="cpoe_apply_order_set">
                        <div class="form-group">
                            <label><strong>Clinical Order Set *</strong></label>
                            <select name="order_set_id" class="form-control form-control-sm" required>
                                <?php foreach ($orderSets as $os): ?>
                                    <option value="<?php echo (int)$os['id']; ?>">
                                        [<?php echo strtoupper(htmlspecialchars($os['approval_status'])); ?>] <?php echo htmlspecialchars($os['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Encounter *</strong></label>
                            <select name="encounter" class="form-control form-control-sm cpoe-enc-select" required>
                                <option value="">-- Select Encounter --</option>
                                <?php foreach ($encounters as $e): ?>
                                    <option value="<?php echo (int)$e['encounter']; ?>" data-pid="<?php echo (int)$e['pid']; ?>">
                                        #<?php echo (int)$e['encounter']; ?> &mdash; <?php echo htmlspecialchars(trim(($e['fname'] ?? '') . ' ' . ($e['lname'] ?? ''))); ?> (<?php echo htmlspecialchars($e['class_code'] ?? 'AMB'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="pid" class="cpoe-enc-pid" value="">
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="chkIsResident" name="is_resident" value="1">
                            <label class="form-check-label small" for="chkIsResident">Ordering as <strong>Resident / Intern</strong> (queues high-risk items for Attending co-sign)</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-check mr-1"></i> Execute 1-Click Order Set</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fa fa-cubes mr-1"></i> Governed Order Set Catalog</h5>
                        <a href="../oe-module-carelio-core/index.php?page=approvals" class="btn btn-outline-primary btn-xs"><i class="fa fa-check-square-o mr-1"></i> Approve Order Sets</a>
                    </div>
                    <?php foreach ($orderSets as $os): ?>
                        <div class="border rounded p-2 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <strong><?php echo htmlspecialchars($os['title']); ?></strong>
                                    <code class="ml-1"><?php echo htmlspecialchars($os['set_code']); ?></code>
                                </div>
                                <span class="badge badge-<?php echo $os['approval_status'] === 'approved' ? 'success' : 'warning text-dark'; ?>">
                                    <?php echo strtoupper(htmlspecialchars($os['approval_status'])); ?>
                                </span>
                            </div>
                            <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($os['description'] ?? ''); ?></small>
                            <ul class="mb-0 pl-3 small">
                                <?php foreach ($os['items'] as $it): ?>
                                    <li>
                                        <span class="badge badge-secondary"><?php echo strtoupper(htmlspecialchars($it['item_type'])); ?></span>
                                        <strong><?php echo htmlspecialchars($it['item_name']); ?></strong>
                                        &mdash; <?php echo htmlspecialchars($it['dosage_or_instructions'] ?? ''); ?>
                                        <?php if ((int)$it['is_high_risk'] === 1): ?><span class="badge badge-danger ml-1">High-Risk (Co-Sign)</span><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card shadow-sm p-3">
                    <h5 class="mb-2"><i class="fa fa-list mr-1"></i> Recent OpenEMR Procedure Orders</h5>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light"><tr><th>Order #</th><th>Patient</th><th>Encounter</th><th>Test / Procedure</th><th>Priority</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No procedure orders placed yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $ro): ?>
                                    <tr>
                                        <td>#<?php echo (int)$ro['procedure_order_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars(trim(($ro['fname'] ?? '') . ' ' . ($ro['lname'] ?? ''))); ?></strong></td>
                                        <td>#<?php echo (int)$ro['encounter_id']; ?></td>
                                        <td><?php echo htmlspecialchars($ro['procedure_name'] ?: $ro['procedure_code']); ?></td>
                                        <td><span class="badge badge-info"><?php echo strtoupper(htmlspecialchars($ro['order_priority'])); ?></span></td>
                                        <td><span class="badge badge-<?php echo $ro['order_status'] === 'complete' ? 'success' : ($ro['order_status'] === 'pending' ? 'warning text-dark' : 'primary'); ?>"><?php echo htmlspecialchars($ro['order_status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: RESIDENT CO-SIGNATURE QUEUE -->
    <div class="tab-pane fade" id="cpoe-cosign" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-user-md text-warning mr-1"></i> Place Resident High-Risk Order</h5>
                    <p class="text-muted small">High-risk orders placed by interns/residents wait in pending status until co-signed by an Attending Physician.</p>
                    <form id="formResidentOrder">
                        <input type="hidden" name="action" value="cpoe_resident_order">
                        <div class="form-group">
                            <label><strong>Encounter *</strong></label>
                            <select name="encounter" class="form-control form-control-sm cpoe-enc-select" required>
                                <option value="">-- Select Encounter --</option>
                                <?php foreach ($encounters as $e): ?>
                                    <option value="<?php echo (int)$e['encounter']; ?>" data-pid="<?php echo (int)$e['pid']; ?>">
                                        #<?php echo (int)$e['encounter']; ?> &mdash; <?php echo htmlspecialchars(trim(($e['fname'] ?? '') . ' ' . ($e['lname'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="pid" class="cpoe-enc-pid" value="">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><strong>Order Type</strong></label>
                                <select name="order_type" class="form-control form-control-sm">
                                    <option value="medication">High-Risk Medication</option>
                                    <option value="procedure">High-Risk Procedure</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label><strong>Attending MD</strong></label>
                                <select name="attending_id" class="form-control form-control-sm">
                                    <?php foreach ($providers as $pr): ?>
                                        <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['fname'] . ' ' . $pr['lname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Order Details *</strong></label>
                            <input type="text" name="order_summary" class="form-control form-control-sm" placeholder="e.g., Heparin IV Infusion 18 units/kg/hr" required>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm btn-block"><i class="fa fa-clock-o mr-1"></i> Submit for Co-Signature</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-3"><i class="fa fa-check-square-o mr-1"></i> Attending Physician Co-Signature Queue</h5>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light"><tr><th>Patient</th><th>Type</th><th>Order</th><th>Resident</th><th>Status</th><th>Attending Action</th></tr></thead>
                        <tbody>
                            <?php if (empty($cosignQueue)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No orders awaiting co-signature.</td></tr>
                            <?php else: ?>
                                <?php foreach ($cosignQueue as $cq): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars(trim(($cq['fname'] ?? '') . ' ' . ($cq['lname'] ?? ''))); ?></strong><br><small class="text-muted">Enc #<?php echo (int)$cq['encounter']; ?></small></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($cq['order_type']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($cq['order_summary']); ?></strong></td>
                                        <td><small><?php echo htmlspecialchars(trim($cq['resident_name'] ?? 'Resident')); ?></small></td>
                                        <td><span class="badge badge-<?php echo $cq['status'] === 'cosigned' ? 'success' : ($cq['status'] === 'rejected' ? 'danger' : 'warning text-dark'); ?>"><?php echo htmlspecialchars($cq['status']); ?></span></td>
                                        <td>
                                            <?php if ($cq['status'] === 'pending_cosign'): ?>
                                                <button class="btn btn-success btn-xs btn-cosign" data-id="<?php echo (int)$cq['id']; ?>" data-decision="cosigned">Co-Sign &amp; Release</button>
                                                <button class="btn btn-outline-danger btn-xs btn-cosign" data-id="<?php echo (int)$cq['id']; ?>" data-decision="rejected">Reject</button>
                                            <?php else: ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($cq['cosigner_name'] ?? '—'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: CRITICAL / PANIC ALERTS & RESULTS -->
    <div class="tab-pane fade" id="cpoe-alerts" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-2"><i class="fa fa-flask text-danger mr-1"></i> Enter / Evaluate Lab Result</h5>
                    <p class="text-muted small">Checks panic rules automatically and routes post-discharge results to the accountable clinician.</p>
                    <form id="formEvalResult">
                        <input type="hidden" name="action" value="cpoe_record_result">
                        <div class="form-group">
                            <label><strong>Encounter *</strong></label>
                            <select name="encounter" class="form-control form-control-sm cpoe-enc-select" required>
                                <option value="">-- Select Encounter --</option>
                                <?php foreach ($encounters as $e): ?>
                                    <option value="<?php echo (int)$e['encounter']; ?>" data-pid="<?php echo (int)$e['pid']; ?>">
                                        #<?php echo (int)$e['encounter']; ?> &mdash; <?php echo htmlspecialchars(trim(($e['fname'] ?? '') . ' ' . ($e['lname'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="pid" class="cpoe-enc-pid" value="">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-7">
                                <label><strong>Test (LOINC) *</strong></label>
                                <select name="test_code" class="form-control form-control-sm">
                                    <?php foreach ($alertRules as $rl): ?>
                                        <option value="<?php echo htmlspecialchars($rl['test_code']); ?>"><?php echo htmlspecialchars($rl['test_name']); ?> (<?php echo htmlspecialchars($rl['test_code']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-5">
                                <label><strong>Result Value *</strong></label>
                                <input type="text" name="result_value" class="form-control form-control-sm" placeholder="e.g. 6.9" required>
                            </div>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="chkPostDischarge" name="is_post_discharge" value="1">
                            <label class="form-check-label small" for="chkPostDischarge">Result arrived <strong>after discharge</strong> (route to accountable MD)</label>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm btn-block"><i class="fa fa-save mr-1"></i> Record &amp; Evaluate Result</button>
                    </form>
                </div>

                <div class="card shadow-sm p-3">
                    <h6 class="mb-2"><i class="fa fa-sliders mr-1"></i> Active Panic Rules</h6>
                    <table class="table table-sm mb-0">
                        <thead class="thead-light"><tr><th>Test</th><th>Low</th><th>High</th><th>Esc.</th></tr></thead>
                        <tbody>
                            <?php foreach ($alertRules as $rl): ?>
                                <tr>
                                    <td><small><strong><?php echo htmlspecialchars($rl['test_name']); ?></strong></small></td>
                                    <td><small>&lt; <?php echo htmlspecialchars($rl['panic_low']); ?></small></td>
                                    <td><small>&gt; <?php echo htmlspecialchars($rl['panic_high']); ?></small></td>
                                    <td><small><?php echo (int)$rl['escalation_minutes']; ?>m</small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fa fa-bell text-danger mr-1"></i> Critical-Value &amp; Post-Discharge Alert Queue</h5>
                        <button type="button" class="btn btn-outline-danger btn-xs" id="btnEscalateOverdue"><i class="fa fa-level-up mr-1"></i> Escalate Overdue Alerts</button>
                    </div>
                    <table class="table table-sm table-hover">
                        <thead class="thead-light"><tr><th>Patient</th><th>Test &amp; Result</th><th>Type</th><th>Assigned To</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($alerts)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No critical or post-discharge alerts triggered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($alerts as $al): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars(trim(($al['fname'] ?? '') . ' ' . ($al['lname'] ?? ''))); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars(substr($al['triggered_at'], 0, 16)); ?></small></td>
                                        <td><strong><?php echo htmlspecialchars($al['test_name']); ?></strong><br><span class="text-danger font-weight-bold"><?php echo htmlspecialchars($al['result_value']); ?></span></td>
                                        <td><span class="badge badge-<?php echo $al['severity'] === 'critical' ? 'danger' : 'info'; ?>"><?php echo htmlspecialchars($al['severity']); ?></span></td>
                                        <td><small><?php echo htmlspecialchars(trim($al['target_name'] ?? 'Attending')); ?></small></td>
                                        <td><span class="badge badge-<?php echo $al['status'] === 'acknowledged' ? 'success' : ($al['status'] === 'escalated' ? 'danger' : 'warning text-dark'); ?>"><?php echo htmlspecialchars($al['status']); ?></span></td>
                                        <td>
                                            <?php if ($al['status'] !== 'acknowledged'): ?>
                                                <button class="btn btn-success btn-xs btn-ack-alert" data-id="<?php echo (int)$al['id']; ?>">Acknowledge</button>
                                                <?php if ($al['status'] === 'unacknowledged'): ?>
                                                    <button class="btn btn-outline-danger btn-xs btn-escalate-alert" data-id="<?php echo (int)$al['id']; ?>">Escalate</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-success"><i class="fa fa-check"></i> Ack'd</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: CPOE REPORTS -->
    <div class="tab-pane fade" id="cpoe-reports" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-3"><i class="fa fa-pie-chart mr-1"></i> Orders by Status</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php if (empty($reports['orders_by_status'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-3">No orders yet.</td></tr>
                            <?php else: foreach ($reports['orders_by_status'] as $os): ?>
                                <tr><td><strong><?php echo htmlspecialchars($os['status']); ?></strong></td><td><?php echo (int)$os['cnt']; ?></td></tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-3"><i class="fa fa-clock-o mr-1"></i> Turnaround Time by Test</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr><th>Test</th><th>Completed</th><th>Avg TAT (min)</th></tr></thead>
                        <tbody>
                            <?php if (empty($reports['turnaround_by_test'])): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No completed order reports yet.</td></tr>
                            <?php else: foreach ($reports['turnaround_by_test'] as $t): ?>
                                <tr><td><?php echo htmlspecialchars($t['test_name']); ?></td><td><?php echo (int)$t['completed_count']; ?></td><td><?php echo htmlspecialchars($t['avg_tat_minutes']); ?></td></tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-3"><i class="fa fa-exclamation-circle text-danger mr-1"></i> Unacknowledged Results</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr><th>Patient</th><th>Test &amp; Result</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($reports['unacknowledged_results'])): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">All results acknowledged.</td></tr>
                            <?php else: foreach ($reports['unacknowledged_results'] as $u): ?>
                                <tr><td><?php echo htmlspecialchars(trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? ''))); ?></td><td><?php echo htmlspecialchars($u['test_name'] . ': ' . $u['result_value']); ?></td><td><span class="badge badge-danger"><?php echo htmlspecialchars($u['status']); ?></span></td></tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showCpoeAlert(msg, isSuccess) {
        var box = $('#cpoeAlertBox');
        box.removeClass('d-none alert-success alert-danger')
           .addClass(isSuccess ? 'alert-success' : 'alert-danger')
           .html('<strong>' + (isSuccess ? 'Success: ' : 'Action Refused: ') + '</strong>' + esc(msg));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function cpoePost(data) {
        $.post('index.php', data, function(res) {
            if (res && res.success) {
                showCpoeAlert(res.message || 'Done', true);
                setTimeout(function() { location.reload(); }, 900);
            } else {
                showCpoeAlert((res && res.error) ? res.error : 'Operation failed.', false);
            }
        }, 'json').fail(function() {
            showCpoeAlert('Server error communicating with CPOE endpoint.', false);
        });
    }

    $('.cpoe-enc-select').on('change', function() {
        var pid = $(this).find(':selected').data('pid') || '';
        $(this).closest('form').find('.cpoe-enc-pid').val(pid);
    });

    $('#formApplyOrderSet, #formResidentOrder, #formEvalResult').on('submit', function(e) {
        e.preventDefault();
        cpoePost($(this).serialize());
    });

    $('.btn-cosign').on('click', function() {
        cpoePost({ action: 'cpoe_cosign', cosign_id: $(this).data('id'), decision: $(this).data('decision') });
    });

    $('.btn-ack-alert').on('click', function() {
        cpoePost({ action: 'cpoe_ack_alert', alert_id: $(this).data('id') });
    });

    $('.btn-escalate-alert').on('click', function() {
        cpoePost({ action: 'cpoe_escalate_alert', alert_id: $(this).data('id') });
    });

    $('#btnEscalateOverdue').on('click', function() {
        cpoePost({ action: 'cpoe_escalate_alert', alert_id: 0 });
    });
})();
</script>
