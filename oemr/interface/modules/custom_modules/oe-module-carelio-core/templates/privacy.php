<?php
/**
 * Carelio Hospital EHR - 5-Tier Privacy & Break-Glass Review Queue Template
 * Conforming to Specification Section 10.1 & 10.2 (Pages 26-27)
 */

use OpenEMR\Modules\CarelioCore\Services\PrivacySensitivityService;

$bgEvents = PrivacySensitivityService::getBreakGlassEvents();
$restrictedRecords = PrivacySensitivityService::getRestrictedRecords();
?>

<div class="row">
    <!-- Left Column: Break-Glass Emergency Review Queue -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0 text-dark"><i class="fa fa-unlock-alt text-danger mr-2"></i>24-Hour Break-Glass Review Queue</h5>
                    <small class="text-muted">Mandatory privacy compliance (<code>hms_breakglass_log</code>). All emergency chart overrides must be reviewed within 24 hours.</small>
                </div>
                <div>
                    <button class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#modalSimulateBreakGlass">
                        <i class="fa fa-flash mr-1"></i> Test Break-Glass
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Accessed</th>
                                <th>Patient / Chart</th>
                                <th>Clinician</th>
                                <th>Sensitivity & Reason</th>
                                <th>24-hr Due By</th>
                                <th>Review Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bgEvents)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa fa-shield fa-2x mb-2 text-success"></i><br>
                                        No emergency break-glass events recorded. All charts accessed through standard assigned roles.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bgEvents as $ev): ?>
                                    <tr class="<?php echo !empty($ev['is_overdue']) ? 'table-danger' : ''; ?>">
                                        <td><small><?php echo htmlspecialchars(substr($ev['accessed_at'], 11, 5)); ?><br><?php echo htmlspecialchars(substr($ev['accessed_at'], 5, 5)); ?></small></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(trim(($ev['pt_fname'] ?? '') . ' ' . ($ev['pt_lname'] ?? 'PID #' . $ev['pid']))); ?></strong>
                                            <?php if (!empty($ev['pubpid'])): ?>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($ev['pubpid']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars(trim(($ev['user_fname'] ?? '') . ' ' . ($ev['user_lname'] ?? 'User #' . $ev['user_id']))); ?></small></td>
                                        <td>
                                            <span class="badge badge-warning text-dark"><?php echo strtoupper(str_replace('_', ' ', $ev['sensitivity_level'])); ?></span>
                                            <small class="d-block text-muted text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($ev['reason']); ?>">
                                                "<?php echo htmlspecialchars($ev['reason']); ?>"
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($ev['is_overdue'])): ?>
                                                <span class="badge badge-danger"><i class="fa fa-exclamation-circle mr-1"></i>OVERDUE ESCALATED</span>
                                            <?php else: ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($ev['due_by'], 11, 5)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ev['review_status'] === 'justified'): ?>
                                                <span class="badge badge-success"><i class="fa fa-check mr-1"></i>Justified</span>
                                            <?php elseif ($ev['review_status'] === 'non_compliant'): ?>
                                                <span class="badge badge-danger"><i class="fa fa-times mr-1"></i>Non-Compliant</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning text-dark"><i class="fa fa-clock-o mr-1"></i>Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right pr-3">
                                            <?php if ($ev['review_status'] === 'pending_review'): ?>
                                                <button class="btn btn-outline-primary btn-xs py-1 px-2 btn-review-bg" data-id="<?php echo $ev['id']; ?>" data-user="<?php echo htmlspecialchars($ev['user_fname'] . ' ' . $ev['user_lname']); ?>">
                                                    Review
                                                </button>
                                            <?php else: ?>
                                                <small class="text-muted">Reviewed</small>
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

    <!-- Right Column: 5-Tier Sensitivity Managed Records -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0 text-dark"><i class="fa fa-lock text-primary mr-2"></i>5-Tier Sensitivity Flags</h5>
                    <small class="text-muted">High-privacy chart restrictions (<code>hms_restricted_record</code>).</small>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalFlagPatient">
                        <i class="fa fa-plus-circle mr-1"></i> Flag Chart
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Patient</th>
                                <th>Sensitivity Level</th>
                                <th>Justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($restrictedRecords)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No patients currently flagged under extended sensitivity.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($restrictedRecords as $rec): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars(trim(($rec['fname'] ?? '') . ' ' . ($rec['lname'] ?? 'PID #' . $rec['record_id']))); ?></strong>
                                            <small class="text-muted d-block">PID: <?php echo $rec['record_id']; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($rec['sensitivity_level'] === 'restricted'): ?>
                                                <span class="badge badge-warning text-dark">RESTRICTED (Psychiatry)</span>
                                            <?php elseif ($rec['sensitivity_level'] === 'highly_restricted'): ?>
                                                <span class="badge badge-danger">HIGHLY RESTRICTED (HIV/STI)</span>
                                            <?php elseif ($rec['sensitivity_level'] === 'staff_as_patient'): ?>
                                                <span class="badge badge-info">STAFF-AS-PATIENT</span>
                                            <?php elseif ($rec['sensitivity_level'] === 'protected_identity'): ?>
                                                <span class="badge badge-dark">PROTECTED IDENTITY</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">NORMAL</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($rec['reason'] ?? '-'); ?></small></td>
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

<!-- Modal: Flag Patient Sensitivity -->
<div class="modal fade" id="modalFlagPatient" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-lock mr-1"></i> Apply Sensitivity Flag to Patient Chart</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formFlagPatient">
                <input type="hidden" name="action" value="flag_sensitivity">
                <input type="hidden" name="record_type" value="patient">
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Patient PID *</strong></label>
                        <input type="number" name="record_id" class="form-control" placeholder="Enter Patient PID (e.g. 1)" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Sensitivity Level * (Section 10.1)</strong></label>
                        <select name="sensitivity_level" class="form-control" required>
                            <option value="restricted">Level 2: Restricted (Psychiatry / Mental Health)</option>
                            <option value="highly_restricted">Level 3: Highly Restricted (HIV/STIs, Substance Use)</option>
                            <option value="staff_as_patient">Level 4: Staff-as-Patient (Hospital Employee)</option>
                            <option value="protected_identity">Level 5: Protected Identity (VIP / Safeguarding / Crime Victim)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Clinical / Legal Justification *</strong></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Employee in nursing department undergoing surgery; privacy officer notified." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save Sensitivity Flag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Simulate Break-Glass Emergency Override -->
<div class="modal fade" id="modalSimulateBreakGlass" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-flash mr-1"></i> Clinical Break-Glass Emergency Override</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formBreakGlass">
                <input type="hidden" name="action" value="trigger_breakglass">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle mr-1"></i> <strong>Legal Warning:</strong> You are accessing a protected hospital chart. Your identity, timestamp, and clinical justification will be permanently logged in <code>hms_breakglass_log</code> and reviewed by the Privacy Officer within 24 hours.
                    </div>
                    <div class="form-group">
                        <label><strong>Target Patient PID *</strong></label>
                        <input type="number" name="pid" class="form-control" value="1" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Chart Sensitivity Level</strong></label>
                        <select name="sensitivity_level" class="form-control">
                            <option value="restricted">Restricted (Psychiatry)</option>
                            <option value="highly_restricted">Highly Restricted (HIV/STI)</option>
                            <option value="staff_as_patient">Staff-as-Patient</option>
                            <option value="protected_identity">Protected Identity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Emergency Clinical Justification *</strong></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Patient admitted to ED with acute respiratory distress; urgent verification of drug allergies and current meds required." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-unlock mr-1"></i> Confirm Emergency Break-Glass</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Flag patient
    $('#formFlagPatient').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert('Patient sensitivity flag applied successfully.');
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Break glass
    $('#formBreakGlass').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert('Emergency Break-Glass override registered. 24-hour review clock started.');
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Review break glass
    $('.btn-review-bg').on('click', function() {
        var id = $(this).data('id');
        var user = $(this).data('user');
        var decision = confirm('Certify break-glass access by ' + user + ' as clinically justified? (Click Cancel to mark Non-Compliant)');
        var status = decision ? 'justified' : 'non_compliant';
        var notes = prompt('Enter Privacy Officer review notes:', decision ? 'Clinically justified by emergency attending' : 'Non-compliant: no active emergency documented');
        if (!notes) return;

        $.post('index.php', {
            action: 'review_breakglass',
            id: id,
            status: status,
            notes: notes
        }, function(resp) {
            if (resp.success) {
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });
});
</script>
