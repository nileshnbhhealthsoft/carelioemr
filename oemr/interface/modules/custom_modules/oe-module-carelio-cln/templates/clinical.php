<?php
/**
 * Carelio Hospital EHR - Module 4: CLN (Clinical Documentation Governance & Reports)
 * Uses OpenEMR's native Encounter, Vitals, Allergies, Referrals, and LBF screens;
 * provides only the extra Hospital Clinical Governance, LBF Seeding, and CLN Reports.
 */

use OpenEMR\Modules\CarelioCore\Services\ClinicalDocumentationService;
use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;

ClinicalDocumentationService::seedSpecialtyLbfLayouts((int)($_SESSION['authUserID'] ?? 1));

$formTemplates = ContentApprovalService::getFormsCatalog();
$clnReports = ClinicalDocumentationService::getClnReports();
?>

<div class="alert alert-info mb-3">
    <i class="fa fa-info-circle mr-1"></i>
    <strong>Native OpenEMR Integration:</strong> Build forms in OpenEMR's <strong>Admin &rarr; Forms &rarr; Layouts</strong>. New LBF forms automatically enter <strong>Draft</strong> here and stay hidden/blocked in Patient Encounters until approved by the Medical Director.
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm border-left-success p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Certified Templates</h6>
                    <h3 class="mb-0 font-weight-bold text-success"><?php echo (int)$clnReports['counts']['approved_templates']; ?></h3>
                    <small class="text-muted">Usable in Native Encounter</small>
                </div>
                <div class="bg-success text-white rounded-circle p-3"><i class="fa fa-check-circle fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-left-warning p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Awaiting Approval</h6>
                    <h3 class="mb-0 font-weight-bold text-warning"><?php echo (int)$clnReports['counts']['awaiting_approval']; ?></h3>
                    <small class="text-muted">Blocked in Native Encounter</small>
                </div>
                <div class="bg-warning text-white rounded-circle p-3"><i class="fa fa-lock fa-2x"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-left-primary p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 11px;">Total Encounters</h6>
                    <h3 class="mb-0 font-weight-bold text-primary"><?php echo (int)$clnReports['counts']['total_encounters']; ?></h3>
                    <small class="text-muted">Tracked Across Clinics</small>
                </div>
                <div class="bg-primary text-white rounded-circle p-3"><i class="fa fa-stethoscope fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<div id="clnAlertBox" class="alert d-none mb-3" role="alert"></div>

<ul class="nav nav-tabs mb-3" id="clnSubTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#cln-templates" role="tab">
            <i class="fa fa-check-square-o mr-1"></i> Specialty Templates &amp; Governance (<?php echo count($formTemplates); ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#cln-reports" role="tab">
            <i class="fa fa-bar-chart mr-1"></i> CLN Reports
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: SPECIALTY TEMPLATES & GOVERNANCE -->
    <div class="tab-pane fade show active" id="cln-templates" role="tabpanel">
        <div class="card shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="fa fa-shield text-primary mr-1"></i> Clinical Form Templates &amp; Medical Director Governance</h5>
                    <small class="text-muted">Approved templates appear in OpenEMR's native Encounter &rarr; Clinical menu. Editing an approved template creates a new version in <strong>Draft</strong> and blocks it until re-approved.</small>
                </div>
                <a href="../oe-module-carelio-core/index.php?page=approvals" class="btn btn-outline-primary btn-sm"><i class="fa fa-external-link mr-1"></i> Full Approval Queue</a>
            </div>
            <table class="table table-sm table-hover">
                <thead class="thead-light"><tr><th>Code</th><th>Title</th><th>Version</th><th>Status</th><th>Encounter Gate</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($formTemplates as $tpl): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($tpl['content_code']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($tpl['title']); ?></strong></td>
                            <td>v<?php echo htmlspecialchars($tpl['version']); ?></td>
                            <td>
                                <?php if ($tpl['status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php elseif ($tpl['status'] === 'pending_approval'): ?>
                                    <span class="badge badge-warning text-dark">Pending Approval</span>
                                <?php elseif ($tpl['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tpl['status'] === 'approved'): ?>
                                    <span class="text-success small"><i class="fa fa-unlock mr-1"></i> Visible in Encounter</span>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fa fa-lock mr-1"></i> Hidden &amp; Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-outline-secondary btn-xs btn-edit-tpl"
                                        data-id="<?php echo (int)$tpl['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($tpl['title']); ?>"
                                        data-payload="<?php echo htmlspecialchars($tpl['payload'] ?? ''); ?>">
                                    <i class="fa fa-pencil mr-1"></i> Edit
                                </button>
                                <?php if ($tpl['status'] === 'draft'): ?>
                                    <button class="btn btn-outline-warning btn-xs btn-submit-tpl" data-id="<?php echo (int)$tpl['id']; ?>">Submit</button>
                                <?php endif; ?>
                                <?php if (in_array($tpl['status'], ['draft', 'pending_approval'], true)): ?>
                                    <button class="btn btn-success btn-xs btn-approve-tpl" data-id="<?php echo (int)$tpl['id']; ?>">Approve</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: CLN REPORTS -->
    <div class="tab-pane fade" id="cln-reports" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-3"><i class="fa fa-hospital-o mr-1"></i> Encounters by Clinic / Class</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr><th>Clinic / Facility</th><th>Class</th><th>Total Encounters</th><th>Last Visit</th></tr></thead>
                        <tbody>
                            <?php if (empty($clnReports['encounters_by_clinic'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No encounter data yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clnReports['encounters_by_clinic'] as $ec): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ec['clinic_name']); ?></strong></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($ec['class_code'] ?? 'AMB'); ?></span></td>
                                        <td class="font-weight-bold text-primary"><?php echo (int)$ec['total_encounters']; ?></td>
                                        <td><small><?php echo htmlspecialchars(substr($ec['last_visit'] ?? '', 0, 16)); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm p-3 mb-3">
                    <h5 class="mb-3"><i class="fa fa-clock-o text-warning mr-1"></i> Templates Awaiting Medical Director Approval</h5>
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light"><tr><th>Code</th><th>Title</th><th>Version</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($clnReports['awaiting_approval'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">All templates are certified.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clnReports['awaiting_approval'] as $aw): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($aw['content_code']); ?></code></td>
                                        <td><?php echo htmlspecialchars($aw['title']); ?></td>
                                        <td>v<?php echo htmlspecialchars($aw['version']); ?></td>
                                        <td><span class="badge badge-warning text-dark"><?php echo htmlspecialchars($aw['status']); ?></span></td>
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

<!-- Modal: Edit Template (triggers Draft re-approval on Approved templates) -->
<div class="modal fade" id="modalEditClnTemplate" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fa fa-pencil mr-1"></i> Edit Clinical Template</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formEditClnTemplate">
                <input type="hidden" name="action" value="cln_edit_template">
                <input type="hidden" name="id" id="editTplId">
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fa fa-info-circle mr-1"></i> Editing an <strong>Approved</strong> template will supersede the old version and place the new version in <strong>Draft</strong> until re-approved by the Medical Director.
                    </div>
                    <div class="form-group">
                        <label><strong>Template Title *</strong></label>
                        <input type="text" name="title" id="editTplTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><strong>Definition (JSON)</strong></label>
                        <textarea name="payload" id="editTplPayload" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function showClnAlert(msg, isSuccess) {
        var box = $('#clnAlertBox');
        box.removeClass('d-none alert-success alert-danger')
           .addClass(isSuccess ? 'alert-success' : 'alert-danger')
           .html('<strong>' + (isSuccess ? 'Success: ' : 'Action Refused: ') + '</strong>' + esc(msg));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function clnPost(data) {
        $.post('index.php', data, function(res) {
            if (res && res.success) {
                showClnAlert(res.message || 'Done', true);
                setTimeout(function() { location.reload(); }, 900);
            } else {
                showClnAlert((res && res.error) ? res.error : 'Operation failed.', false);
            }
        }, 'json').fail(function() {
            showClnAlert('Server error communicating with Clinical Documentation endpoint.', false);
        });
    }

    $('#formEditClnTemplate').on('submit', function(e) {
        e.preventDefault();
        clnPost($(this).serialize());
    });

    $('#btnSeedClnLbf').on('click', function() { clnPost({ action: 'cln_seed_lbf' }); });

    $('.btn-edit-tpl').on('click', function() {
        $('#editTplId').val($(this).data('id'));
        $('#editTplTitle').val($(this).data('title'));
        $('#editTplPayload').val($(this).data('payload'));
        $('#modalEditClnTemplate').modal('show');
    });

    $('.btn-submit-tpl').on('click', function() { clnPost({ action: 'resubmit_content', id: $(this).data('id') }); });
    $('.btn-approve-tpl').on('click', function() { clnPost({ action: 'approve_content', id: $(this).data('id') }); });
})();
</script>
