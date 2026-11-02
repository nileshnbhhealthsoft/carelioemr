<?php
/** @var array $theatresList */
/** @var array $surgeriesList */
?>

<div class="ipd-form-row" style="margin-bottom: 24px;">
    <!-- OT Theatre Suites Status -->
    <?php if (!empty($theatresList)): ?>
        <?php foreach ($theatresList as $ot): 
            $otStatus = strtolower($ot['status']);
            $cardBorder = ($otStatus === 'occupied') ? '#ef4444' : '#10b981';
            $badgeBg = ($otStatus === 'occupied') ? '#fee2e2' : '#d1fae5';
            $badgeColor = ($otStatus === 'occupied') ? '#991b1b' : '#065f46';
        ?>
            <div class="ipd-card" style="border-left: 5px solid <?php echo $cardBorder; ?>; margin-bottom: 0;">
                <div class="ipd-card-body" style="padding: 16px 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 800; font-size: 1.05rem;"><?php echo htmlspecialchars($ot['theatre_name']); ?></span>
                        <span class="ipd-badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>;">
                            <?php echo htmlspecialchars(ucfirst($ot['status'])); ?>
                        </span>
                    </div>
                    <div style="font-size: 0.82rem; color: var(--ipd-slate); margin-top: 4px;">
                        Code: <strong><?php echo htmlspecialchars($ot['theatre_code']); ?></strong> &bull; Type: <?php echo htmlspecialchars($ot['theatre_type']); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-cut text-primary"></i>
            Operation Theatre (OT) Surgery Scheduling & Surgical Records
        </h2>
        <div>
            <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-schedule-surgery')">
                <i class="fa fa-calendar-plus"></i> Schedule New Surgery
            </button>
        </div>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Scheduled Time</th>
                        <th>Patient / IPD</th>
                        <th>Procedure / Surgery</th>
                        <th>Operating Theatre</th>
                        <th>Lead Surgeon</th>
                        <th>Anesthesia Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($surgeriesList)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No surgical procedures scheduled.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($surgeriesList as $surg): 
                            $status = strtolower($surg['status']);
                            $badge = match($status) {
                                'scheduled' => 'blue',
                                'pre_op' => 'yellow',
                                'in_progress' => 'red',
                                'recovery' => 'orange',
                                'completed' => 'green',
                                'cancelled' => 'red',
                                default => 'blue'
                            };
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y H:i', strtotime($surg['scheduled_start'])); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--ipd-slate);">
                                        to <?php echo date('H:i', strtotime($surg['scheduled_end'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(($surg['pat_fname'] ?? '') . ' ' . ($surg['pat_lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        IPD: <?php echo htmlspecialchars($surg['ipd_number']); ?> (Bed: <?php echo htmlspecialchars($surg['bed_number'] ?? ''); ?>)
                                    </div>
                                </td>
                                <td>
                                    <strong style="color: var(--ipd-dark); font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($surg['surgery_name']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($surg['theatre_name']); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--ipd-slate);"><?php echo htmlspecialchars($surg['theatre_code']); ?></div>
                                </td>
                                <td>
                                    Dr. <?php echo htmlspecialchars(($surg['surgeon_fname'] ?? '') . ' ' . ($surg['surgeon_lname'] ?? '')); ?>
                                    <div style="font-size: 0.75rem; color: var(--ipd-slate);"><?php echo htmlspecialchars($surg['surgeon_specialty'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <span class="ipd-badge" style="background:#f1f5f9; color:#475569;">
                                        <?php echo htmlspecialchars($surg['anesthesia_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="ipd-badge <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status))); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <?php if ($status === 'scheduled'): ?>
                                            <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.openModal('modal-surgery-status-<?php echo (int)$surg['id']; ?>')">
                                                <i class="fa fa-play text-primary"></i> Start
                                            </button>
                                        <?php elseif ($status === 'in_progress'): ?>
                                            <button class="ipd-btn ipd-btn-sm ipd-btn-success" onclick="IPD.openModal('modal-surgery-status-<?php echo (int)$surg['id']; ?>')">
                                                <i class="fa fa-check"></i> Complete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
