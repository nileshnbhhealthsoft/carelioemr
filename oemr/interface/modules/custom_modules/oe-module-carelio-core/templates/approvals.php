<?php
/**
 * Carelio Hospital EHR - Clinical Content Approval Queue
 * Conforming to Specification Section 4 (Page 6) and Section 18.1 (Page 37)
 */

use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;

$filterStatus = $_GET['status_filter'] ?? null;
$approvals = ContentApprovalService::getAll($filterStatus);
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0 text-dark"><i class="fa fa-stethoscope text-primary mr-2"></i>Medical Director Clinical Content Approval Queue</h5>
                    <small class="text-muted">Clinical governance gate (<code>hms_content_approval</code>). Forms, order sets, and alert rules remain locked in DRAFT until signed off by the Medical Director.</small>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalNewContent">
                        <i class="fa fa-plus-circle mr-1"></i> New Order Set / Alert Rule
                    </button>
                </div>
            </div>

            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="?page=approvals" class="btn btn-<?php echo empty($filterStatus) ? 'primary' : 'outline-secondary'; ?>">All Items</a>
                    <a href="?page=approvals&status_filter=pending_approval" class="btn btn-<?php echo $filterStatus === 'pending_approval' ? 'warning text-dark' : 'outline-secondary'; ?>">Pending Approval</a>
                    <a href="?page=approvals&status_filter=draft" class="btn btn-<?php echo $filterStatus === 'draft' ? 'secondary' : 'outline-secondary'; ?>">Drafts</a>
                    <a href="?page=approvals&status_filter=approved" class="btn btn-<?php echo $filterStatus === 'approved' ? 'success' : 'outline-secondary'; ?>">Approved Live</a>
                    <a href="?page=approvals&status_filter=rejected" class="btn btn-<?php echo $filterStatus === 'rejected' ? 'danger' : 'outline-secondary'; ?>">Rejected</a>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Type</th>
                                <th>Code</th>
                                <th>Title & Clinical Scope</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Author</th>
                                <th>Medical Director Sign-Off</th>
                                <th class="text-right">Governance Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approvals)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa fa-check-circle fa-2x mb-2 text-success"></i><br>
                                        No items currently matching filter.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($approvals as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['content_type'] === 'order_set'): ?>
                                                <span class="badge badge-info"><i class="fa fa-list-ol mr-1"></i>Order Set</span>
                                            <?php elseif ($item['content_type'] === 'form'): ?>
                                                <span class="badge badge-primary"><i class="fa fa-file-text-o mr-1"></i>Form</span>
                                            <?php elseif ($item['content_type'] === 'alert_rule'): ?>
                                                <span class="badge badge-danger"><i class="fa fa-bell-o mr-1"></i>Panic Alert</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($item['content_type']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($item['content_code']); ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                            <?php if (!empty($item['rejection_reason'])): ?>
                                                <div class="text-danger small mt-1">
                                                    <i class="fa fa-exclamation-triangle mr-1"></i> Reason: <?php echo htmlspecialchars($item['rejection_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>v<?php echo htmlspecialchars($item['version']); ?></td>
                                        <td>
                                            <?php if ($item['status'] === 'approved'): ?>
                                                <span class="badge badge-success px-2 py-1"><i class="fa fa-check mr-1"></i>Approved Live</span>
                                            <?php elseif ($item['status'] === 'pending_approval'): ?>
                                                <span class="badge badge-warning text-dark px-2 py-1"><i class="fa fa-clock-o mr-1"></i>Pending Review</span>
                                            <?php elseif ($item['status'] === 'rejected'): ?>
                                                <span class="badge badge-danger px-2 py-1"><i class="fa fa-times mr-1"></i>Rejected</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary px-2 py-1"><i class="fa fa-pencil mr-1"></i>Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars(trim(($item['author_fname'] ?? '') . ' ' . ($item['author_lname'] ?? 'Dr. Richard Burt'))); ?></small></td>
                                        <td>
                                            <?php if (!empty($item['approved_at'])): ?>
                                                <small class="text-success font-weight-bold">
                                                    <i class="fa fa-certificate mr-1"></i> Signed: <?php echo htmlspecialchars(substr($item['approved_at'], 0, 10)); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Awaiting signature</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($item['status'] === 'pending_approval' || $item['status'] === 'draft'): ?>
                                                <button class="btn btn-success btn-xs py-1 px-2 btn-approve-content" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                    <i class="fa fa-check"></i> Sign & Approve
                                                </button>
                                                <?php if ($item['status'] === 'pending_approval'): ?>
                                                    <button class="btn btn-outline-danger btn-xs py-1 px-2 btn-reject-content" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>">
                                                        <i class="fa fa-times"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                            <?php elseif ($item['status'] === 'rejected'): ?>
                                                <button class="btn btn-outline-primary btn-xs py-1 px-2 btn-resubmit-content" data-id="<?php echo $item['id']; ?>">
                                                    <i class="fa fa-refresh"></i> Resubmit
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fa fa-lock mr-1"></i> Certified</span>
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
</div>

<!-- Modal: New Clinical Content -->
<div class="modal fade" id="modalNewContent" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-plus-circle mr-1"></i> Draft New Clinical Content</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formNewContent">
                <input type="hidden" name="action" value="create_content">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Content Type *</strong></label>
                            <select name="content_type" class="form-control" required>
                                <option value="order_set">Clinical Order Set (Bundle)</option>
                                <option value="alert_rule">Panic Value Alert Rule</option>
                                <option value="document">Discharge Summary / Template</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Unique Content Code *</strong></label>
                            <input type="text" name="content_code" class="form-control" placeholder="e.g. ORD-PNEUMONIA-01, FORM-PICU-NOTE" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-9">
                            <label><strong>Title *</strong></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Community-Acquired Pneumonia Inpatient Order Set" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label><strong>Version</strong></label>
                            <input type="text" name="version" class="form-control" value="1.0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Clinical Content Definition (JSON / Protocol Items)</strong></label>
                        <textarea name="payload" class="form-control" rows="5" placeholder='{"orders": ["Blood Culture STAT", "Ceftriaxone 1g IV Q24H", "Chest X-Ray Portable", "Supplemental O2 to maintain SpO2 > 94%"]}'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i> Save as Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Approve item
    $('.btn-approve-content').on('click', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        if (!confirm('As Medical Director, do you certify and approve "' + title + '" for live clinical use?')) {
            return;
        }
        $.post('index.php', { action: 'approve_content', id: id }, function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Reject item
    $('.btn-reject-content').on('click', function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        var reason = prompt('Please enter clinical rejection reason for "' + title + '":');
        if (!reason) return;

        $.post('index.php', { action: 'reject_content', id: id, reason: reason }, function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Resubmit item
    $('.btn-resubmit-content').on('click', function() {
        var id = $(this).data('id');
        $.post('index.php', { action: 'resubmit_content', id: id }, function(resp) {
            if (resp.success) {
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });

    // Create new content submit
    $('#formNewContent').on('submit', function(e) {
        e.preventDefault();
        $.post('index.php', $(this).serialize(), function(resp) {
            if (resp.success) {
                alert('Clinical item created in Draft status.');
                location.reload();
            } else {
                alert('Error: ' + resp.error);
            }
        }, 'json');
    });
});
</script>
