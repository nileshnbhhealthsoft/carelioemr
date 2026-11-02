<?php
/** @var array $passesList */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-id-badge text-primary"></i>
            Patient Attendant, Visitor Badges & Dietary Services
        </h2>
        <div>
            <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-issue-pass')">
                <i class="fa fa-plus-circle"></i> Issue New Attendant Pass
            </button>
        </div>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Pass Number</th>
                        <th>Inpatient / Room</th>
                        <th>Attendant Name & Relation</th>
                        <th>Phone</th>
                        <th>Validity Period</th>
                        <th>Dietary / Meal Plan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($passesList)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No attendant passes currently issued.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($passesList as $pass): 
                            $status = strtolower($pass['status']);
                            $badge = ($status === 'active') ? 'green' : 'red';
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--ipd-purple);"><?php echo htmlspecialchars($pass['pass_number']); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(($pass['pat_fname'] ?? '') . ' ' . ($pass['pat_lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        IPD: <?php echo htmlspecialchars($pass['ipd_number'] ?? ''); ?> (Bed: <?php echo htmlspecialchars($pass['bed_number'] ?? ''); ?>)
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pass['attendant_name']); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);"><?php echo htmlspecialchars($pass['relationship'] ?? 'Family'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($pass['phone'] ?: '-'); ?></td>
                                <td>
                                    <div>From: <?php echo date('M d, Y', strtotime($pass['valid_from'])); ?></div>
                                    <small class="text-muted">To: <?php echo date('M d, Y', strtotime($pass['valid_to'])); ?></small>
                                </td>
                                <td>
                                    <span class="ipd-badge" style="background:#f1f5f9; color:#334155;">
                                        <i class="fa fa-utensils"></i> <?php echo htmlspecialchars($pass['meal_plan'] ?? 'Normal'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="ipd-badge <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status === 'active'): ?>
                                        <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updatePassStatus(<?php echo (int)$pass['id']; ?>, 'returned')">
                                            <i class="fa fa-undo"></i> Mark Returned
                                        </button>
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
