<?php
/**
 * Carelio Hospital EHR - Module 2: REG (Registration & Master Patient Index)
 * Conforming to Specification Section 18.2 (Pages 37-38)
 */

use OpenEMR\Modules\CarelioCore\Services\RegistrationService;

$activeTemp = RegistrationService::getActiveTempIdentities();
$reconciled = RegistrationService::getReconciledIdentities();
$deposits = RegistrationService::getRecentDeposits();
$nextTempMrn = RegistrationService::generateTempMrn();
$mergeHistory = RegistrationService::getMergeHistory();
$reports = RegistrationService::getRegistrationReports();
?>

<div class="row mb-3">
    <!-- Metric 1: Active Temp IDs -->
    <div class="col-md-3">
        <div class="card shadow-sm border-left-danger p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Active Emergency Trauma Patients</h6>
                    <h3 class="mb-0 font-weight-bold text-danger"><?php echo count($activeTemp); ?></h3>
                    <small class="text-muted">Unidentified / Temporary MRNs</small>
                </div>
                <div class="bg-danger text-white rounded-circle p-3">
                    <i class="fa fa-ambulance fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Metric 2: Reconciled & Merged Charts -->
    <div class="col-md-3">
        <div class="card shadow-sm border-left-success p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Reconciled & Merged Charts</h6>
                    <h3 class="mb-0 font-weight-bold text-success"><?php echo count($mergeHistory) + count($reconciled); ?></h3>
                    <small class="text-muted">Audit snapshot preserved</small>
                </div>
                <div class="bg-success text-white rounded-circle p-3">
                    <i class="fa fa-link fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Metric 3: Suspected Duplicates -->
    <div class="col-md-3">
        <div class="card shadow-sm border-left-warning p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Suspected Duplicates</h6>
                    <h3 class="mb-0 font-weight-bold text-warning"><?php echo count($reports['suspected_duplicates']); ?></h3>
                    <small class="text-muted">Requiring Medical Records Review</small>
                </div>
                <div class="bg-warning text-white rounded-circle p-3">
                    <i class="fa fa-clone fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Metric 4: Deposits -->
    <div class="col-md-3">
        <div class="card shadow-sm border-left-primary p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Pre-Admission Deposits</h6>
                    <h3 class="mb-0 font-weight-bold text-primary">
                        <?php 
                        $totalDep = 0;
                        foreach ($deposits as $d) { $totalDep += (float)$d['deposit_amount']; }
                        echo '$' . number_format($totalDep, 2);
                        ?>
                    </h3>
                    <small class="text-muted">East Caribbean Dollars (XCD)</small>
                </div>
                <div class="bg-primary text-white rounded-circle p-3">
                    <i class="fa fa-money fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div>
            <h5 class="mb-0 text-dark"><i class="fa fa-user-plus text-primary mr-2"></i>Module 2: REG — Registration &amp; Master Patient Index</h5>
            <small class="text-muted">Rapid ED Intake, Duplicate Detection, Side-by-Side Review, Reversible Merge/Unmerge &amp; Deposit Ledger.</small>
        </div>
        <div>
            <button class="btn btn-danger btn-sm mr-2" data-toggle="modal" data-target="#modalRapidEmergency">
                <i class="fa fa-flash mr-1"></i> 1-Click Rapid ED Intake
            </button>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalCollectDeposit">
                <i class="fa fa-money mr-1"></i> Collect Deposit
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Sub-Tabs -->
        <ul class="nav nav-tabs px-3 pt-2 bg-light" id="regSubTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold" id="tab-active-temp" data-toggle="tab" href="#content-active-temp" role="tab">
                    <i class="fa fa-ambulance text-danger mr-1"></i> Rapid ED Patients (<?php echo count($activeTemp); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-duplicate-check" data-toggle="tab" href="#content-duplicate-check" role="tab">
                    <i class="fa fa-search text-warning mr-1"></i> Duplicate Check &amp; Comparison
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-merge-history" data-toggle="tab" href="#content-merge-history" role="tab">
                    <i class="fa fa-history text-info mr-1"></i> Merge &amp; Unmerge Audit Trail (<?php echo count($mergeHistory); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-reconciled" data-toggle="tab" href="#content-reconciled" role="tab">
                    <i class="fa fa-check-circle text-success mr-1"></i> Reconciled ED History (<?php echo count($reconciled); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-insurance-consent" data-toggle="tab" href="#content-insurance-consent" role="tab">
                    <i class="fa fa-shield text-secondary mr-1"></i> Insurance &amp; Consents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-deposits" data-toggle="tab" href="#content-deposits" role="tab">
                    <i class="fa fa-file-text-o text-primary mr-1"></i> Deposits Ledger
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-reports" data-toggle="tab" href="#content-reports" role="tab">
                    <i class="fa fa-bar-chart text-dark mr-1"></i> REG Reports
                </a>
            </li>
        </ul>

        <div class="tab-content p-3">
            <!-- 1. Active Temporary Patients Queue -->
            <div class="tab-pane fade show active" id="content-active-temp" role="tabpanel">
                <div class="alert alert-info py-2">
                    <i class="fa fa-info-circle mr-1"></i> <strong>Trauma Workflow:</strong> Unidentified patients receive immediate temporary MRNs. Clinical staff can place blood, laboratory, and imaging orders right away. Once identified, click <strong>"Reconcile / Merge Chart"</strong> to transfer orders into their permanent record.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Triage</th>
                                <th>Temporary MRN</th>
                                <th>PID</th>
                                <th>Estimated Profile</th>
                                <th>Intake Time</th>
                                <th>Arrival Notes</th>
                                <th>Intake Clerk</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeTemp)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-check-circle fa-2x text-success mb-2"></i><br>
                                        No active unidentified emergency patients in trauma queue.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activeTemp as $t): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $triageBadge = 'badge-danger';
                                            if ($t['triage_color'] === 'orange') $triageBadge = 'badge-warning text-dark';
                                            elseif ($t['triage_color'] === 'yellow') $triageBadge = 'badge-warning';
                                            elseif ($t['triage_color'] === 'green') $triageBadge = 'badge-success';
                                            ?>
                                            <span class="badge <?php echo $triageBadge; ?> px-2 py-1 font-weight-bold">
                                                <?php echo strtoupper($t['triage_color']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($t['temp_mrn']); ?></strong></td>
                                        <td><span class="badge badge-light border">#<?php echo $t['temp_pid']; ?></span></td>
                                        <td>
                                            <?php echo htmlspecialchars($t['estimated_gender']); ?>, 
                                            ~<?php echo (int)$t['estimated_age']; ?> yrs
                                        </td>
                                        <td><small class="text-muted"><?php echo date('d-M-Y H:i', strtotime($t['created_at'])); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($t['arrival_notes']); ?></small></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars(($t['creator_fname'] ?? '') . ' ' . ($t['creator_lname'] ?? 'Staff')); ?></small></td>
                                        <td class="text-right">
                                            <button class="btn btn-primary btn-sm btn-open-reconcile" data-id="<?php echo $t['id']; ?>" data-mrn="<?php echo htmlspecialchars($t['temp_mrn']); ?>" data-pid="<?php echo $t['temp_pid']; ?>">
                                                <i class="fa fa-link mr-1"></i> Reconcile / Merge Chart
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Duplicate Check & Side-by-Side Review -->
            <div class="tab-pane fade" id="content-duplicate-check" role="tabpanel">
                <div class="alert alert-warning py-2">
                    <i class="fa fa-clone mr-1"></i> <strong>Duplicate Detection &amp; Side-by-Side Comparison:</strong> Enter demographic criteria to search for existing duplicate records using soundex and exact match scoring. Select any suspected match to view side-by-side comparison before merging.
                </div>
                <div class="card p-3 mb-3 bg-light border">
                    <h6><i class="fa fa-filter mr-1"></i> Patient Registration Duplicate Checker</h6>
                    <form id="formCheckDuplicates" class="form-row align-items-end">
                        <input type="hidden" name="action" value="detect_duplicates">
                        <div class="col-md-2">
                            <label class="small font-weight-bold">First Name</label>
                            <input type="text" name="fname" class="form-control form-control-sm" placeholder="e.g. Jean">
                        </div>
                        <div class="col-md-2">
                            <label class="small font-weight-bold">Last Name</label>
                            <input type="text" name="lname" class="form-control form-control-sm" placeholder="e.g. Baptiste">
                        </div>
                        <div class="col-md-2">
                            <label class="small font-weight-bold">Date of Birth</label>
                            <input type="date" name="DOB" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="small font-weight-bold">Cell Phone</label>
                            <input type="text" name="phone_cell" class="form-control form-control-sm" placeholder="e.g. 758-...">
                        </div>
                        <div class="col-md-2">
                            <label class="small font-weight-bold">National ID / NIC</label>
                            <input type="text" name="ss" class="form-control form-control-sm" placeholder="e.g. LC-...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning btn-sm btn-block font-weight-bold">
                                <i class="fa fa-search mr-1"></i> Check Duplicates
                            </button>
                        </div>
                    </form>
                </div>

                <div id="duplicateResultsContainer">
                    <p class="text-muted text-center py-4">Enter patient details above and click <strong>"Check Duplicates"</strong> to run detection.</p>
                </div>
            </div>

            <!-- 3. Merge & Unmerge Audit Trail -->
            <div class="tab-pane fade" id="content-merge-history" role="tabpanel">
                <div class="alert alert-info py-2">
                    <i class="fa fa-history mr-1"></i> <strong>Reversible Merge Audit Trail:</strong> Every chart consolidation creates an immutable snapshot of all moved encounters, vital signs, orders, and prescriptions. If a merge was performed mistakenly, Medical Records can click <strong>"Unmerge Chart"</strong> to safely restore the records without losing audit history.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>Log ID</th>
                                <th>Status</th>
                                <th>Source Chart (Merged From)</th>
                                <th>Target Chart (Merged To)</th>
                                <th>Merged By</th>
                                <th>Merge Time</th>
                                <th>Unmerged Info</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mergeHistory)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No patient-to-patient merge records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mergeHistory as $m): ?>
                                    <tr>
                                        <td>#<?php echo $m['id']; ?></td>
                                        <td>
                                            <?php if ($m['status'] === 'merged'): ?>
                                                <span class="badge badge-primary px-2 py-1"><i class="fa fa-link mr-1"></i>MERGED</span>
                                            <?php else: ?>
                                                <span class="badge badge-success px-2 py-1"><i class="fa fa-undo mr-1"></i>UNMERGED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($m['src_fname'] . ' ' . $m['src_lname']); ?></strong>
                                            <span class="badge badge-light border">PID #<?php echo $m['source_pid']; ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($m['src_pubpid']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($m['tgt_fname'] . ' ' . $m['tgt_lname']); ?></strong>
                                            <span class="badge badge-light border">PID #<?php echo $m['target_pid']; ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($m['tgt_pubpid']); ?></small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars(($m['merger_fname'] ?? '') . ' ' . ($m['merger_lname'] ?? 'Staff')); ?></small></td>
                                        <td><small class="text-muted"><?php echo date('d-M-Y H:i', strtotime($m['merged_at'])); ?></small></td>
                                        <td>
                                            <?php if ($m['status'] === 'unmerged'): ?>
                                                <small class="text-success font-weight-bold">
                                                    Unmerged on <?php echo date('d-M-Y H:i', strtotime($m['unmerged_at'])); ?><br>
                                                    by <?php echo htmlspecialchars(($m['unmerger_fname'] ?? '') . ' ' . ($m['unmerger_lname'] ?? '')); ?>
                                                </small>
                                                <?php if (!empty($m['unmerge_reason'])): ?>
                                                    <small class="text-muted d-block">Reason: <?php echo htmlspecialchars($m['unmerge_reason']); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($m['status'] === 'merged'): ?>
                                                <button class="btn btn-outline-danger btn-sm btn-open-unmerge" data-id="<?php echo $m['id']; ?>" data-src="<?php echo htmlspecialchars($m['src_fname'] . ' ' . $m['src_lname']); ?>" data-srcpid="<?php echo $m['source_pid']; ?>" data-tgt="<?php echo htmlspecialchars($m['tgt_fname'] . ' ' . $m['tgt_lname']); ?>" data-tgtpid="<?php echo $m['target_pid']; ?>">
                                                    <i class="fa fa-undo mr-1"></i> Unmerge Chart
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled><i class="fa fa-check mr-1"></i> Reverted</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Reconciled ED History -->
            <div class="tab-pane fade" id="content-reconciled" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>Temp MRN</th>
                                <th>Source PID</th>
                                <th>Merged Into Permanent Patient</th>
                                <th>Target PID</th>
                                <th>Reconciliation Timestamp</th>
                                <th>Staff Authorizer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reconciled)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No emergency identities reconciled yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reconciled as $r): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($r['temp_mrn']); ?></strong></td>
                                        <td><span class="badge badge-light border">#<?php echo $r['temp_pid']; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($r['real_fname'] . ' ' . $r['real_lname']); ?></strong></td>
                                        <td><span class="badge badge-success">#<?php echo $r['reconciled_pid']; ?></span></td>
                                        <td><small><?php echo date('d-M-Y H:i', strtotime($r['reconciled_at'])); ?></small></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars(($r['merger_fname'] ?? '') . ' ' . ($r['merger_lname'] ?? 'Staff')); ?></small></td>
                                        <td><span class="badge badge-success px-2 py-1">RECONCILED</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Insurance & Consents -->
            <div class="tab-pane fade" id="content-insurance-consent" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h6 class="font-weight-bold text-primary"><i class="fa fa-shield mr-1"></i> Patient Insurance Verification</h6>
                            <p class="text-muted small">Lookup active inpatient health insurance, policy coverage, and copayment eligibility.</p>
                            <div class="input-group mb-3">
                                <input type="number" id="inputInsurancePid" class="form-control" placeholder="Enter Patient PID (e.g. 1)">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" id="btnVerifyInsurance"><i class="fa fa-check mr-1"></i> Verify Policy</button>
                                </div>
                            </div>
                            <div id="insuranceResultContainer" class="p-3 bg-light border rounded" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h6 class="font-weight-bold text-success"><i class="fa fa-pencil-square-o mr-1"></i> Patient Consent Capture</h6>
                            <p class="text-muted small">Record signed patient consent forms (Inpatient Treatment, Financial Responsibility, HIPAA).</p>
                            <form id="formCaptureConsent">
                                <input type="hidden" name="action" value="capture_consent">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="small font-weight-bold">Patient PID *</label>
                                        <input type="number" name="pid" class="form-control form-control-sm" required placeholder="e.g. 1">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="small font-weight-bold">Consent Agreement *</label>
                                        <select name="consent_type" class="form-control form-control-sm" required>
                                            <option value="general_treatment">General Inpatient Treatment</option>
                                            <option value="financial_guarantee">Financial &amp; Deposit Guarantee</option>
                                            <option value="hipaa_release">HIPAA / Information Release</option>
                                            <option value="surgical_anesthesia">Surgical &amp; Anesthesia Consent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="small font-weight-bold">Signer Name *</label>
                                        <input type="text" name="signer_name" class="form-control form-control-sm" required placeholder="e.g. Patient or Legal Guardian">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="small font-weight-bold">Relationship *</label>
                                        <select name="signer_relationship" class="form-control form-control-sm">
                                            <option value="self">Self (Patient)</option>
                                            <option value="parent">Parent</option>
                                            <option value="spouse">Spouse</option>
                                            <option value="legal_guardian">Legal Guardian</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm btn-block font-weight-bold">
                                    <i class="fa fa-save mr-1"></i> Record Signed Consent
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Pre-Admission Deposits Ledger -->
            <div class="tab-pane fade" id="content-deposits" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>Receipt #</th>
                                <th>Patient Name</th>
                                <th>PID</th>
                                <th>Amount (XCD)</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th>Collection Date</th>
                                <th>Cashier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No pre-admission deposits collected yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $d): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($d['receipt_number']); ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($d['fname'] . ' ' . $d['lname']); ?></strong></td>
                                        <td><span class="badge badge-light border">#<?php echo $d['pid']; ?></span></td>
                                        <td class="font-weight-bold text-success">$<?php echo number_format((float)$d['deposit_amount'], 2); ?> <?php echo htmlspecialchars($d['currency']); ?></td>
                                        <td><span class="badge badge-info"><?php echo strtoupper(htmlspecialchars($d['payment_method'])); ?></span></td>
                                        <td><small><?php echo htmlspecialchars($d['payment_reference'] ?: '—'); ?></small></td>
                                        <td><small><?php echo date('d-M-Y H:i', strtotime($d['created_at'])); ?></small></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars(($d['cashier_fname'] ?? '') . ' ' . ($d['cashier_lname'] ?? 'Cashier')); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 7. Registration Reports -->
            <div class="tab-pane fade" id="content-reports" role="tabpanel">
                <div class="row">
                    <!-- Daily Registrations Table -->
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h6 class="font-weight-bold text-dark"><i class="fa fa-calendar mr-1"></i> Patient Registrations by Day (Last 7 Days)</h6>
                            <table class="table table-sm table-bordered mt-2">
                                <thead class="thead-light">
                                    <tr><th>Date</th><th>New Patient Volume</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports['daily_volume'])): ?>
                                        <tr><td colspan="3" class="text-muted text-center">No registrations recorded this week.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reports['daily_volume'] as $dv): ?>
                                            <tr>
                                                <td><strong><?php echo date('d-M-Y (D)', strtotime($dv['reg_date'])); ?></strong></td>
                                                <td><span class="badge badge-primary px-2 py-1 font-weight-bold"><?php echo $dv['count']; ?> patients</span></td>
                                                <td><span class="badge badge-success">OKEU Main Site</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Suspected Duplicates Report -->
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h6 class="font-weight-bold text-warning"><i class="fa fa-clone mr-1"></i> Suspected Duplicate Pairs in Database</h6>
                            <table class="table table-sm table-bordered mt-2">
                                <thead class="thead-light">
                                    <tr><th>Patient A (PID)</th><th>Patient B (PID)</th><th>Shared Criteria</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports['suspected_duplicates'])): ?>
                                        <tr><td colspan="4" class="text-success text-center"><i class="fa fa-check mr-1"></i> Clean! No obvious duplicate pairs detected in Master Patient Index.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($reports['suspected_duplicates'] as $dup): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($dup['fname1'] . ' ' . $dup['lname1']); ?></strong> (#<?php echo $dup['pid1']; ?>)</td>
                                                <td><strong><?php echo htmlspecialchars($dup['fname2'] . ' ' . $dup['lname2']); ?></strong> (#<?php echo $dup['pid2']; ?>)</td>
                                                <td><span class="badge badge-warning text-dark">Exact Name Match</span></td>
                                                <td>
                                                    <button class="btn btn-outline-primary btn-xs py-0 px-1 btn-compare-pair" data-pid1="<?php echo $dup['pid1']; ?>" data-pid2="<?php echo $dup['pid2']; ?>">
                                                        Compare
                                                    </button>
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
        </div>
    </div>
</div>

<!-- Modal 1: 1-Click Rapid Emergency Registration -->
<div class="modal fade" id="modalRapidEmergency" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-ambulance mr-1"></i> 1-Click Rapid Emergency Registration (Trauma Protocol)</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formRapidEmergency">
                <input type="hidden" name="action" value="rapid_register">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-info-circle mr-1"></i> <strong>Trauma Protocol:</strong> Generates a temporary emergency MRN and immediately opens an emergency encounter. Doctors can order blood tests, X-rays, and medications right away without waiting for identity papers.
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Temporary Emergency MRN</strong></label>
                            <input type="text" class="form-control font-weight-bold" value="<?php echo $nextTempMrn; ?>" readonly>
                            <small class="text-muted">Auto-generated unique emergency sequence</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Triage Acuity Tag *</strong></label>
                            <select name="triage_color" class="form-control font-weight-bold" required>
                                <option value="red" selected>Level 1 - RED (Immediate Resuscitation)</option>
                                <option value="orange">Level 2 - ORANGE (Emergent)</option>
                                <option value="yellow">Level 3 - YELLOW (Urgent)</option>
                                <option value="green">Level 4 - GREEN (Less Urgent)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Estimated Gender *</strong></label>
                            <select name="estimated_gender" class="form-control" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Unknown" selected>Indeterminate / Unknown</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Estimated Age (Years) *</strong></label>
                            <input type="number" name="estimated_age" class="form-control" value="30" min="0" max="120" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Arrival Notes / Circumstances</strong></label>
                        <textarea name="arrival_notes" class="form-control" rows="3" placeholder="e.g. Unconscious male found at Castries highway accident; transported via St. Lucia Fire Service Ambulance. No identification found."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold"><i class="fa fa-ambulance mr-1"></i> Create Emergency Chart Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Reconcile Emergency Identity -->
<div class="modal fade" id="modalReconcileIdentity" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-link mr-1"></i> Identity Reconciliation &amp; Safe Chart Merge</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formReconcileIdentity">
                <input type="hidden" name="action" value="reconcile_identity">
                <input type="hidden" name="temp_id" id="reconcileTempId">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle mr-1"></i> <strong>Safe Merge:</strong> When the patient's verified identification is confirmed, enter their permanent PID. All emergency notes, encounters, lab orders, and prescriptions will safely transfer to their real chart.
                    </div>
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <label><strong>Source Temporary Chart:</strong></label>
                            <input type="text" id="reconcileTempMrn" class="form-control font-weight-bold" readonly>
                        </div>
                        <div class="col-md-6">
                            <label><strong>Target Verified Patient PID: *</strong></label>
                            <input type="number" name="target_pid" class="form-control font-weight-bold" placeholder="e.g. 1" required>
                            <small class="text-muted">Enter the verified patient PID from OpenEMR</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fa fa-check mr-1"></i> Execute Safe Merge &amp; Transfer Chart</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Side-by-Side Comparison & Merge -->
<div class="modal fade" id="modalSideBySideMerge" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-columns mr-1"></i> Side-by-Side Duplicate Chart Comparison &amp; Consolidation</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formExecutePatientMerge">
                <input type="hidden" name="action" value="merge_patients">
                <input type="hidden" name="source_pid" id="mergeSourcePid">
                <input type="hidden" name="target_pid" id="mergeTargetPid">
                <div class="modal-body">
                    <div class="alert alert-danger py-2">
                        <i class="fa fa-warning mr-1"></i> <strong>Caution:</strong> Merging will transfer all encounters, vital signs, prescriptions, and lab orders from <strong>Source (Duplicate)</strong> into <strong>Target (Master)</strong>. An audit snapshot is created so you can <strong>Unmerge</strong> anytime if needed.
                    </div>
                    <div class="row">
                        <!-- Patient A (Source to be merged/inactivated) -->
                        <div class="col-md-6 border-right">
                            <div class="p-2 bg-light rounded mb-2 text-center">
                                <h6 class="text-danger font-weight-bold mb-0">SOURCE (Duplicate to be Inactivated)</h6>
                            </div>
                            <div id="sideBySideSource">Loading...</div>
                        </div>
                        <!-- Patient B (Target Master) -->
                        <div class="col-md-6">
                            <div class="p-2 bg-light rounded mb-2 text-center">
                                <h6 class="text-success font-weight-bold mb-0">TARGET (Master Retained Chart)</h6>
                            </div>
                            <div id="sideBySideTarget">Loading...</div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label class="font-weight-bold">Merge Justification / Audit Reason *</label>
                        <input type="text" name="reason" class="form-control" value="Consolidated duplicate chart identified at registration" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold"><i class="fa fa-link mr-1"></i> Execute Merge &amp; Log Snapshot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Unmerge Confirmation -->
<div class="modal fade" id="modalUnmerge" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-undo mr-1"></i> Revert &amp; Unmerge Patient Chart</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formUnmergePatient">
                <input type="hidden" name="action" value="unmerge_patients">
                <input type="hidden" name="merge_log_id" id="unmergeLogId">
                <div class="modal-body">
                    <p>Are you sure you want to unmerge these charts? All clinical encounters, orders, and vitals will be restored to their original chart.</p>
                    <div class="card p-2 bg-light mb-3">
                        <small><strong>Source:</strong> <span id="unmergeSourceLabel"></span></small>
                        <small><strong>Target:</strong> <span id="unmergeTargetLabel"></span></small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Unmerge Justification / Audit Reason *</label>
                        <input type="text" name="reason" class="form-control" value="Reversing mistaken patient merge" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold"><i class="fa fa-undo mr-1"></i> Execute Unmerge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 5: Collect Pre-Admission Deposit -->
<div class="modal fade" id="modalCollectDeposit" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-money mr-1"></i> Collect Inpatient Pre-Admission Deposit</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formCollectDeposit">
                <input type="hidden" name="action" value="collect_deposit">
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Patient PID *</strong></label>
                        <input type="number" name="pid" class="form-control" placeholder="e.g. 1" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Deposit Amount *</strong></label>
                            <input type="number" name="deposit_amount" step="0.01" class="form-control font-weight-bold" placeholder="e.g. 250.00" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Currency</strong></label>
                            <select name="currency" class="form-control">
                                <option value="XCD" selected>East Caribbean Dollar (XCD $)</option>
                                <option value="USD">US Dollar (USD $)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Payment Method *</strong></label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="insurance">Insurance / Copayment Guarantee</option>
                            <option value="check">Bank Check</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Payment Reference / Card Auth #</strong></label>
                        <input type="text" name="payment_reference" class="form-control" placeholder="e.g. AUTH-98234 or Check #104">
                    </div>
                    <div class="form-group">
                        <label><strong>Notes</strong></label>
                        <input type="text" name="notes" class="form-control" value="Inpatient pre-admission deposit">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-bold"><i class="fa fa-print mr-1"></i> Collect &amp; Issue Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Rapid Register Submit
    $('#formRapidEmergency').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 2. Open Reconcile Modal
    $('.btn-open-reconcile').on('click', function() {
        $('#reconcileTempId').val($(this).data('id'));
        $('#reconcileTempMrn').val($(this).data('mrn'));
        $('#modalReconcileIdentity').modal('show');
    });

    // 3. Reconcile Submit
    $('#formReconcileIdentity').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 4. Collect Deposit Submit
    $('#formCollectDeposit').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 5. Duplicate Check Submit
    $('#formCheckDuplicates').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Checking...');
        $.post('index.php', $(this).serialize(), function(resp) {
            $btn.prop('disabled', false).html('<i class="fa fa-search mr-1"></i> Check Duplicates');
            if (resp.success) {
                var html = '';
                if (!resp.matches || resp.matches.length === 0) {
                    html = '<div class="alert alert-success"><i class="fa fa-check mr-1"></i> <strong>Zero Duplicates Detected:</strong> No existing patients match this demographic criteria. Safe to register!</div>';
                } else {
                    html = '<h6 class="font-weight-bold text-danger mb-2"><i class="fa fa-exclamation-triangle mr-1"></i> ' + resp.matches.length + ' Potential Duplicate Match(es) Found:</h6>';
                    html += '<div class="table-responsive"><table class="table table-bordered table-sm table-hover">';
                    html += '<thead class="thead-light"><tr><th>Match Confidence</th><th>PID</th><th>Patient Name</th><th>DOB</th><th>Sex</th><th>Phone</th><th>Match Reason</th><th>Action</th></tr></thead><tbody>';
                    resp.matches.forEach(function(m) {
                        var scoreClass = m.match_score >= 80 ? 'badge-danger' : (m.match_score >= 50 ? 'badge-warning text-dark' : 'badge-info');
                        html += '<tr>';
                        html += '<td><span class="badge ' + scoreClass + ' px-2 py-1 font-weight-bold">' + m.match_score + '% Confidence</span></td>';
                        html += '<td><span class="badge badge-light border">#' + m.pid + '</span></td>';
                        html += '<td><strong>' + m.fname + ' ' + m.lname + '</strong> (' + m.pubpid + ')</td>';
                        html += '<td>' + (m.DOB || '—') + '</td>';
                        html += '<td>' + (m.sex || '—') + '</td>';
                        html += '<td>' + (m.phone_cell || '—') + '</td>';
                        html += '<td><small class="text-danger">' + m.reasons + '</small></td>';
                        html += '<td><button class="btn btn-warning btn-xs py-0 px-2 btn-compare-target" data-targetpid="' + m.pid + '"><i class="fa fa-columns mr-1"></i> Compare</button></td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                }
                $('#duplicateResultsContainer').html(html);
            }
        }, 'json');
    });

    // 6. Compare target from search
    $(document).on('click', '.btn-compare-target', function() {
        var tgtPid = $(this).data('targetpid');
        var srcPid = prompt('Enter the suspected duplicate Source PID to compare against Master #' + tgtPid + ':', '1');
        if (srcPid && srcPid != tgtPid) {
            loadSideBySide(srcPid, tgtPid);
        }
    });

    // 7. Compare pair from report
    $('.btn-compare-pair').on('click', function() {
        var p1 = $(this).data('pid1');
        var p2 = $(this).data('pid2');
        loadSideBySide(p1, p2);
    });

    function renderProfileCard(p) {
        if (!p) return '<div class="alert alert-danger">Record not found.</div>';
        var html = '<table class="table table-sm">';
        html += '<tr><th>Patient Name:</th><td><strong>' + p.fname + ' ' + p.lname + '</strong></td></tr>';
        html += '<tr><th>PID / MRN:</th><td>#' + p.pid + ' (' + p.pubpid + ')</td></tr>';
        html += '<tr><th>DOB / Sex:</th><td>' + (p.DOB || '—') + ' / ' + (p.sex || '—') + '</td></tr>';
        html += '<tr><th>Cell Phone:</th><td>' + (p.phone_cell || '—') + '</td></tr>';
        html += '<tr><th>National ID:</th><td>' + (p.ss || '—') + '</td></tr>';
        html += '<tr><th>Address:</th><td>' + (p.street || '') + ' ' + (p.city || '') + '</td></tr>';
        html += '<tr><th>Clinical Encounters:</th><td><span class="badge badge-info">' + p.total_encounters + ' encounters</span></td></tr>';
        html += '<tr><th>Vital Signs Sets:</th><td><span class="badge badge-secondary">' + p.total_vitals + ' recorded</span></td></tr>';
        html += '<tr><th>Lab / Procedure Orders:</th><td><span class="badge badge-primary">' + p.total_orders + ' orders</span></td></tr>';
        html += '<tr><th>Active Prescriptions:</th><td><span class="badge badge-success">' + p.total_prescriptions + ' rx</span></td></tr>';
        html += '</table>';
        return html;
    }

    function loadSideBySide(srcPid, tgtPid) {
        $('#mergeSourcePid').val(srcPid);
        $('#mergeTargetPid').val(tgtPid);
        $('#sideBySideSource').html('<div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#sideBySideTarget').html('<div class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#modalSideBySideMerge').modal('show');

        $.post('index.php', { action: 'get_patient_profile', pid: srcPid }, function(r1) {
            $('#sideBySideSource').html(renderProfileCard(r1.patient));
        }, 'json');

        $.post('index.php', { action: 'get_patient_profile', pid: tgtPid }, function(r2) {
            $('#sideBySideTarget').html(renderProfileCard(r2.patient));
        }, 'json');
    }

    // 8. Execute Merge
    $('#formExecutePatientMerge').on('submit', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to merge these charts? All clinical notes and encounters will transfer to the Target chart.')) {
            return;
        }
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 9. Open Unmerge Modal
    $('.btn-open-unmerge').on('click', function() {
        $('#unmergeLogId').val($(this).data('id'));
        $('#unmergeSourceLabel').text($(this).data('src') + ' (PID #' + $(this).data('srcpid') + ')');
        $('#unmergeTargetLabel').text($(this).data('tgt') + ' (PID #' + $(this).data('tgtpid') + ')');
        $('#modalUnmerge').modal('show');
    });

    // 10. Execute Unmerge
    $('#formUnmergePatient').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 11. Verify Insurance
    $('#btnVerifyInsurance').on('click', function() {
        var pid = $('#inputInsurancePid').val();
        if (!pid) {
            alert('Please enter a patient PID.');
            return;
        }
        $.post('index.php', { action: 'get_insurance', pid: pid }, function(resp) {
            if (resp.success) {
                var ins = resp.insurance;
                var html = '<h6 class="font-weight-bold text-success"><i class="fa fa-check-circle mr-1"></i> ' + ins.status + '</h6>';
                if (!ins.has_insurance || ins.plans.length === 0) {
                    html += '<p class="small text-muted mb-0">No private insurance policy linked. Patient is billed under Standard Saint Lucia Public Healthcare / Cash Deposit.</p>';
                } else {
                    html += '<table class="table table-sm table-bordered mt-2">';
                    html += '<tr><th>Plan Provider:</th><td>' + (ins.plans[0].plan_name || 'Saint Lucia National Health') + '</td></tr>';
                    html += '<tr><th>Policy / Member ID:</th><td>' + (ins.plans[0].policy_number || 'POL-9021-LC') + '</td></tr>';
                    html += '<tr><th>Copay Guarantee:</th><td>100% Inpatient Covered</td></tr>';
                    html += '</table>';
                }
                $('#insuranceResultContainer').html(html).show();
            }
        }, 'json');
    });

    // 12. Capture Consent
    $('#formCaptureConsent').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert(resp.message);
                $('#formCaptureConsent')[0].reset();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });
});
</script>
