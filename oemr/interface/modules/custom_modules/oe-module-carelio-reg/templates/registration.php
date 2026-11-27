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
                <a class="nav-link font-weight-bold" id="tab-register-patient" data-toggle="tab" href="#content-register-patient" role="tab">
                    <i class="fa fa-user-plus text-primary mr-1"></i> Register Patient
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

            <!-- 1b. Unified Front-Desk Patient Registration -->
            <div class="tab-pane fade" id="content-register-patient" role="tabpanel">
                <div class="alert alert-light border py-2 mb-3">
                    <i class="fa fa-id-card text-primary mr-1"></i> <strong>Front-Desk Registration (Role: Front Desk):</strong> Register a patient with demographics, contacts, guardian, language/interpreter need, insurance, and consent. Potential duplicates are flagged automatically before saving.
                </div>
                <div id="regDuplicateWarning" class="alert alert-warning border-warning" style="display:none;"></div>
                <form id="formRegisterPatient">
                    <input type="hidden" name="action" value="register_patient">
                    <input type="hidden" name="force_register" id="regForceRegister" value="0">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary border-bottom pb-1">1. Demographics</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">First Name *</label>
                                    <input type="text" name="fname" class="form-control form-control-sm" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Middle Name</label>
                                    <input type="text" name="mname" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Last Name *</label>
                                    <input type="text" name="lname" class="form-control form-control-sm" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Date of Birth *</label>
                                    <input type="date" name="DOB" class="form-control form-control-sm" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Sex *</label>
                                    <select name="sex" class="form-control form-control-sm" required>
                                        <option value="Female">Female</option>
                                        <option value="Male">Male</option>
                                        <option value="Unknown">Unknown</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">National ID (NIC / SS)</label>
                                    <input type="text" name="ss" class="form-control form-control-sm" placeholder="e.g. LC-19890101">
                                </div>
                            </div>
                            <h6 class="font-weight-bold text-primary border-bottom pb-1 mt-2">2. Contacts &amp; Address</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Mobile Phone</label>
                                    <input type="text" name="phone_cell" class="form-control form-control-sm" placeholder="+1 758-555-0100">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Home Phone</label>
                                    <input type="text" name="phone_home" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="small font-weight-bold">Street Address</label>
                                    <input type="text" name="street" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold">City / Community</label>
                                    <input type="text" name="city" class="form-control form-control-sm" placeholder="Castries">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold">Parish / State</label>
                                    <input type="text" name="state" class="form-control form-control-sm" placeholder="Castries">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary border-bottom pb-1">3. Guardian &amp; Language / Interpreter</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Guardian / Emergency Contact</label>
                                    <input type="text" name="guardian_name" class="form-control form-control-sm" placeholder="Full Name">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Relationship</label>
                                    <input type="text" name="guardian_relationship" class="form-control form-control-sm" placeholder="Parent / Spouse">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Guardian Phone</label>
                                    <input type="text" name="phone_contact" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Preferred Language</label>
                                    <input type="text" name="language" class="form-control form-control-sm" value="English">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Interpreter Needed?</label>
                                    <select name="interpreter_needed" class="form-control form-control-sm">
                                        <option value="no">No</option>
                                        <option value="yes">Yes</option>
                                        <option value="unknown">Unknown</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Interpreter Notes</label>
                                    <input type="text" name="interpreter" class="form-control form-control-sm" placeholder="e.g. Kwéyòl / French">
                                </div>
                            </div>
                            <h6 class="font-weight-bold text-primary border-bottom pb-1 mt-2">4. Insurance &amp; Initial Consent (Optional)</h6>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="small font-weight-bold">Insurer / Plan Name</label>
                                    <input type="text" name="ins_plan_name" class="form-control form-control-sm" placeholder="e.g. National Health Scheme">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="small font-weight-bold">Policy Number</label>
                                    <input type="text" name="ins_policy_number" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="small font-weight-bold">Copay (XCD)</label>
                                    <input type="text" name="ins_copay" class="form-control form-control-sm" value="0.00">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="small font-weight-bold">Consent Signer Name</label>
                                    <input type="text" name="consent_signer_name" class="form-control form-control-sm" placeholder="Leave blank to capture later">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="small font-weight-bold">Consent Type</label>
                                    <select name="consent_type" class="form-control form-control-sm">
                                        <option value="general_treatment">General Inpatient Treatment</option>
                                        <option value="financial_guarantee">Financial &amp; Deposit Guarantee</option>
                                        <option value="hipaa_release">HIPAA / Information Release</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-right mt-2">
                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="fa fa-check-circle mr-1"></i> Register Patient (with Duplicate Check)
                        </button>
                    </div>
                </form>
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
                            <form id="formCaptureConsent" enctype="multipart/form-data">
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
                                <div class="form-group">
                                    <label class="small font-weight-bold">Attach Signed Scan (PDF / PNG / JPG)</label>
                                    <input type="file" name="consent_scan" class="form-control-file form-control-sm" accept=".pdf,.png,.jpg,.jpeg">
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
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No pre-admission deposits collected yet.</td>
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
                                        <td>
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2 btn-print-receipt"
                                                data-receipt="<?php echo htmlspecialchars($d['receipt_number']); ?>"
                                                data-patient="<?php echo htmlspecialchars($d['fname'] . ' ' . $d['lname']); ?>"
                                                data-pid="<?php echo (int)$d['pid']; ?>"
                                                data-amount="<?php echo number_format((float)$d['deposit_amount'], 2); ?>"
                                                data-currency="<?php echo htmlspecialchars($d['currency']); ?>"
                                                data-method="<?php echo strtoupper(htmlspecialchars($d['payment_method'])); ?>"
                                                data-ref="<?php echo htmlspecialchars($d['payment_reference'] ?: '—'); ?>"
                                                data-date="<?php echo date('d-M-Y H:i', strtotime($d['created_at'])); ?>">
                                                <i class="fa fa-print"></i> Print
                                            </button>
                                        </td>
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
                    <!-- Daily Registrations by Day & Site Table -->
                    <div class="col-md-6">
                        <div class="card p-3 mb-3">
                            <h6 class="font-weight-bold text-dark"><i class="fa fa-calendar mr-1"></i> Patient Registrations by Day &amp; Site (Last 7 Days)</h6>
                            <table class="table table-sm table-bordered mt-2">
                                <thead class="thead-light">
                                    <tr><th>Date</th><th>Site</th><th>New Patient Volume</th></tr>
                                </thead>
                                <tbody>
                                    <?php $daySiteRows = $reports['volume_by_day_and_site'] ?? []; ?>
                                    <?php if (empty($daySiteRows)): ?>
                                        <tr><td colspan="3" class="text-muted text-center">No registrations recorded this week.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($daySiteRows as $dv): ?>
                                            <tr>
                                                <td><strong><?php echo date('d-M-Y (D)', strtotime($dv['reg_date'])); ?></strong></td>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($dv['site_name']); ?></span></td>
                                                <td><span class="badge badge-primary px-2 py-1 font-weight-bold"><?php echo (int)$dv['count']; ?> patients</span></td>
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
                    <div class="alert alert-info py-2">
                        <i class="fa fa-info-circle mr-1"></i> <strong>Step 1 — Search Patient:</strong> When documents arrive, search below to see if the patient already has a chart. If found, click <strong>Select</strong>. If not found, leave PID empty and fill in <strong>Create New Permanent Patient</strong> below.
                    </div>
                    <div class="form-row mb-3">
                        <div class="col-md-5">
                            <label><strong>Source Temporary Chart:</strong></label>
                            <input type="text" id="reconcileTempMrn" class="form-control font-weight-bold" readonly>
                        </div>
                        <div class="col-md-7">
                            <label><strong>Target Verified Patient PID (If Found):</strong></label>
                            <input type="number" name="target_pid" id="reconcileTargetPid" class="form-control font-weight-bold" placeholder="Leave empty if patient is new">
                            <small class="text-muted">Auto-filled when you click Select below, or leave empty to register new</small>
                        </div>
                    </div>

                    <!-- Quick Patient Search inside Reconcile Modal -->
                    <div class="card bg-light p-2 mb-3 border">
                        <div class="small font-weight-bold mb-1"><i class="fa fa-search mr-1"></i> Search Existing Patient by Name / DOB / National ID</div>
                        <div class="form-row align-items-center">
                            <div class="col-md-3 mb-1"><input type="text" id="recSearchFname" class="form-control form-control-sm" placeholder="First Name"></div>
                            <div class="col-md-3 mb-1"><input type="text" id="recSearchLname" class="form-control form-control-sm" placeholder="Last Name"></div>
                            <div class="col-md-3 mb-1"><input type="date" id="recSearchDob" class="form-control form-control-sm"></div>
                            <div class="col-md-3 mb-1">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-block font-weight-bold" id="btnReconcileSearch">
                                    <i class="fa fa-search mr-1"></i> Find Patient
                                </button>
                            </div>
                        </div>
                        <div id="reconcileSearchResults" class="mt-2" style="display:none;"></div>
                    </div>

                    <!-- If Patient Not Found: Create New Permanent Patient -->
                    <div class="card border-success p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small font-weight-bold text-success"><i class="fa fa-user-plus mr-1"></i> Patient Not Found? Create New Permanent Patient &amp; Reconcile</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" id="btnClearTargetPid">Clear PID</button>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">First Name</label>
                                <input type="text" name="new_fname" id="recNewFname" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">Last Name</label>
                                <input type="text" name="new_lname" id="recNewLname" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">Date of Birth</label>
                                <input type="date" name="new_DOB" id="recNewDob" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">Sex</label>
                                <select name="new_sex" class="form-control form-control-sm">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">Mobile Phone</label>
                                <input type="text" name="new_phone_cell" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-4 mb-1">
                                <label class="small mb-0">National ID (NIC / SS)</label>
                                <input type="text" name="new_ss" class="form-control form-control-sm">
                            </div>
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

<!-- Printable Deposit Receipt Modal -->
<div class="modal fade" id="modalPrintReceipt" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white d-print-none">
                <h5 class="modal-title"><i class="fa fa-print mr-2"></i> Official Deposit Receipt</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="printableReceiptArea">
                <div class="text-center border-bottom pb-2 mb-3">
                    <h5 class="mb-0 font-weight-bold">Carelio Hospital EHR</h5>
                    <small class="text-muted">Official Pre-Admission Deposit Receipt</small>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th>Receipt #:</th><td id="rcptNumber" class="font-weight-bold"></td></tr>
                    <tr><th>Date:</th><td id="rcptDate"></td></tr>
                    <tr><th>Patient:</th><td id="rcptPatient"></td></tr>
                    <tr><th>Patient PID:</th><td id="rcptPid"></td></tr>
                    <tr><th>Payment Method:</th><td id="rcptMethod"></td></tr>
                    <tr><th>Reference:</th><td id="rcptRef"></td></tr>
                    <tr class="border-top"><th>Amount Paid:</th><td id="rcptAmount" class="font-weight-bold text-success h5"></td></tr>
                </table>
            </div>
            <div class="modal-footer d-print-none">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="location.reload()">Close</button>
                <button type="button" class="btn btn-primary font-weight-bold" onclick="window.print()"><i class="fa fa-print mr-1"></i> Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 0. Unified Front-Desk Patient Registration with Duplicate Flagging
    $('#formRegisterPatient').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                $('#regDuplicateWarning').hide();
                $('#regForceRegister').val('0');
                alert(resp.message);
                location.reload();
            } else if (resp.duplicate_warning) {
                var html = '<strong><i class="fa fa-exclamation-triangle mr-1"></i> ' + resp.error + '</strong><ul class="mb-2 mt-1 small">';
                resp.matches.forEach(function(m) {
                    html += '<li><strong>' + m.fname + ' ' + m.lname + '</strong> (PID #' + m.pid + ', DOB: ' + (m.DOB || '—') + ') — ' + m.match_score + '% (' + m.reasons + ')</li>';
                });
                html += '</ul><button type="button" class="btn btn-sm btn-danger font-weight-bold" id="btnForceRegisterAnyway">Not a Duplicate — Register Anyway</button>';
                $('#regDuplicateWarning').html(html).show();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    $(document).on('click', '#btnForceRegisterAnyway', function() {
        $('#regForceRegister').val('1');
        $('#formRegisterPatient').submit();
    });

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
        $('#reconcileTargetPid').val('');
        $('#reconcileSearchResults').hide().empty();
        $('#modalReconcileIdentity').modal('show');
    });

    // 2b. Search Existing Patient inside Reconcile Modal
    $('#btnReconcileSearch').on('click', function() {
        var fname = $('#recSearchFname').val();
        var lname = $('#recSearchLname').val();
        var dob = $('#recSearchDob').val();
        $.post('index.php', { action: 'detect_duplicates', fname: fname, lname: lname, DOB: dob }, function(resp) {
            if (resp.success && resp.matches && resp.matches.length > 0) {
                var html = '<table class="table table-sm table-bordered bg-white mb-0"><thead><tr><th>PID</th><th>Name</th><th>DOB</th><th>Match</th><th></th></tr></thead><tbody>';
                resp.matches.forEach(function(m) {
                    html += '<tr><td>#' + m.pid + '</td><td><strong>' + m.fname + ' ' + m.lname + '</strong></td><td>' + (m.DOB || '—') + '</td><td>' + m.match_score + '%</td>';
                    html += '<td><button type="button" class="btn btn-xs btn-primary py-0 px-2 btn-select-reconcile-pid" data-pid="' + m.pid + '">Select</button></td></tr>';
                });
                html += '</tbody></table>';
                $('#reconcileSearchResults').html(html).show();
            } else {
                $('#reconcileSearchResults').html('<div class="alert alert-warning py-1 mb-0 small">No existing patient found. Leave PID empty and fill in <strong>Create New Permanent Patient</strong> below.</div>').show();
                $('#recNewFname').val(fname);
                $('#recNewLname').val(lname);
                $('#recNewDob').val(dob);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-select-reconcile-pid', function() {
        $('#reconcileTargetPid').val($(this).data('pid'));
    });

    $('#btnClearTargetPid').on('click', function() {
        $('#reconcileTargetPid').val('');
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

    // 4. Collect Deposit Submit (opens printable receipt)
    function showReceiptModal(rcpt) {
        $('#rcptNumber').text(rcpt.receipt_number);
        $('#rcptDate').text(rcpt.collected_at);
        $('#rcptPatient').text(rcpt.patient_name);
        $('#rcptPid').text('#' + rcpt.pid);
        $('#rcptMethod').text((rcpt.payment_method || '').toUpperCase());
        $('#rcptRef').text(rcpt.payment_reference || '—');
        $('#rcptAmount').text('$' + parseFloat(rcpt.amount).toFixed(2) + ' ' + rcpt.currency);
        $('#modalPrintReceipt').modal('show');
    }

    $('#formCollectDeposit').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                $('#modalCollectDeposit').modal('hide');
                showReceiptModal(resp);
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    $(document).on('click', '.btn-print-receipt', function() {
        showReceiptModal({
            receipt_number: $(this).data('receipt'),
            collected_at: $(this).data('date'),
            patient_name: $(this).data('patient'),
            pid: $(this).data('pid'),
            payment_method: $(this).data('method'),
            payment_reference: $(this).data('ref'),
            amount: $(this).data('amount'),
            currency: $(this).data('currency')
        });
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
        var defaultSrc = (String(tgtPid) === '1') ? '' : '1';
        var srcPid = prompt('Enter a DIFFERENT suspected duplicate Source PID to compare against Master #' + tgtPid + ':', defaultSrc);
        if (!srcPid) {
            return;
        }
        if (String(srcPid).trim() === String(tgtPid).trim()) {
            alert('Cannot compare PID #' + tgtPid + ' with itself. Please enter a different duplicate patient PID.');
            return;
        }
        loadSideBySide(srcPid, tgtPid);
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
                var badgeClass = ins.has_insurance ? 'text-success' : 'text-warning';
                var html = '<h6 class="font-weight-bold ' + badgeClass + '"><i class="fa fa-check-circle mr-1"></i> ' + ins.status + '</h6>';
                if (!ins.has_insurance || ins.plans.length === 0) {
                    html += '<p class="small text-muted mb-0">No private insurance policy linked. Patient is billed under Standard Saint Lucia Public Healthcare / Cash Deposit.</p>';
                } else {
                    html += '<table class="table table-sm table-bordered mt-2"><thead><tr><th>Type</th><th>Insurer / Plan</th><th>Policy #</th><th>Effective / End</th><th>Copay</th><th>Eligibility</th></tr></thead><tbody>';
                    ins.plans.forEach(function(plan) {
                        var elBadge = plan.eligibility_status === 'Expired' ? 'badge-danger' : 'badge-success';
                        html += '<tr>';
                        html += '<td>' + (plan.type || 'primary').toUpperCase() + '</td>';
                        html += '<td>' + (plan.insurer_name || plan.plan_name || 'Private Insurer') + '</td>';
                        html += '<td>' + (plan.policy_number || '—') + '</td>';
                        html += '<td>' + (plan.date || '—') + ' / ' + (plan.date_end && plan.date_end !== '0000-00-00' ? plan.date_end : 'Open') + '</td>';
                        html += '<td>' + (plan.copay || '0.00') + '</td>';
                        html += '<td><span class="badge ' + elBadge + '">' + (plan.eligibility_status || 'Active') + '</span></td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                }
                $('#insuranceResultContainer').html(html).show();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // 12. Capture Consent (supports file scan upload)
    $('#formCaptureConsent').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    alert(resp.message);
                    $('#formCaptureConsent')[0].reset();
                } else {
                    alert('Error: ' + resp.error);
                }
            }
        });
    });
});
</script>
