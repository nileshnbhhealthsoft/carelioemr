<?php
/** @var array $recentRounds */
/** @var array $activeMeds */
/** @var array $recentIo */
?>

<div class="ipd-form-row" style="margin-bottom: 24px;">
    <!-- Left Column: Daily Doctor Clinical Rounds -->
    <div style="grid-column: span 2;">
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2>
                    <i class="fa fa-user-md text-primary"></i>
                    Inpatient Doctor Daily Rounds & Clinical Progress Notes (SOAP)
                </h2>
            </div>
            <div class="ipd-card-body" style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table class="ipd-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient / IPD</th>
                                <th>Attending Doctor</th>
                                <th>Subjective / Objective Findings</th>
                                <th>Assessment & Treatment Plan</th>
                                <th>Diet & Activity Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRounds)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding: 24px; color: var(--ipd-slate);">
                                        No clinical progress rounds recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentRounds as $round): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M d, Y H:i', strtotime($round['round_date'])); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars(($round['fname'] ?? '') . ' ' . ($round['lname'] ?? '')); ?></strong>
                                            <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                                <?php echo htmlspecialchars($round['ipd_number'] ?? ''); ?> &bull; Bed: <?php echo htmlspecialchars($round['bed_number'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td>
                                            Dr. <?php echo htmlspecialchars(($round['doc_fname'] ?? '') . ' ' . ($round['doc_lname'] ?? '')); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($round['subjective_notes'])): ?>
                                                <div><strong>S:</strong> <?php echo htmlspecialchars($round['subjective_notes']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($round['objective_findings'])): ?>
                                                <div><strong>O:</strong> <?php echo htmlspecialchars($round['objective_findings']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($round['assessment'])): ?>
                                                <div><strong>A:</strong> <?php echo htmlspecialchars($round['assessment']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($round['treatment_plan'])): ?>
                                                <div style="color: var(--ipd-primary);"><strong>P:</strong> <?php echo htmlspecialchars($round['treatment_plan']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($round['diet_orders'])): ?>
                                                <div><i class="fa fa-utensils"></i> <?php echo htmlspecialchars($round['diet_orders']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($round['activity_orders'])): ?>
                                                <div><i class="fa fa-walking"></i> <?php echo htmlspecialchars($round['activity_orders']); ?></div>
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

<!-- Active Medication Orders Table -->
<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-prescription-bottle-alt text-primary"></i>
            Active Inpatient Medication Orders & Prescriptions
        </h2>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Patient / IPD</th>
                        <th>Medicine Name</th>
                        <th>Dosage & Route</th>
                        <th>Frequency</th>
                        <th>Start - End Date</th>
                        <th>Instructions</th>
                        <th>Prescribing Doctor</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activeMeds)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding: 24px; color: var(--ipd-slate);">
                                No active inpatient medication orders found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeMeds as $med): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(($med['fname'] ?? '') . ' ' . ($med['lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        <?php echo htmlspecialchars($med['ipd_number'] ?? ''); ?> (<?php echo htmlspecialchars($med['bed_number'] ?? ''); ?>)
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: var(--ipd-dark); font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($med['medicine_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="ipd-badge blue"><?php echo htmlspecialchars($med['dosage']); ?></span>
                                    <span class="ipd-badge" style="background:#f1f5f9; color:#475569;"><?php echo htmlspecialchars($med['route']); ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($med['frequency']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($med['start_date']); ?> 
                                    to <?php echo htmlspecialchars($med['end_date'] ?: 'Ongoing'); ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($med['instructions'] ?: '-'); ?></small></td>
                                <td>Dr. <?php echo htmlspecialchars(($med['doc_fname'] ?? '') . ' ' . ($med['doc_lname'] ?? '')); ?></td>
                                <td>
                                    <span class="ipd-badge green">Active</span>
                                </td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-primary" onclick="IPD.openMarModal(<?php echo (int)$med['id']; ?>, <?php echo (int)$med['admission_id']; ?>, '<?php echo addslashes($med['medicine_name']); ?>', '<?php echo addslashes($med['dosage']); ?>')">
                                        <i class="fa fa-check-square"></i> Administer (MAR)
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
