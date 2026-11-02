<?php
/** @var array $bedMatrixData */
$wards = $bedMatrixData['wards'] ?? [];
$stats = $bedMatrixData['stats'] ?? [];
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-th-large text-primary"></i>
            Real-Time Bed Matrix & Ward Availability
        </h2>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-new-admission')">
                <i class="fa fa-user-plus"></i> New Inpatient Admission
            </button>
            <button class="ipd-btn ipd-btn-outline" onclick="window.location.reload()">
                <i class="fa fa-sync-alt"></i> Refresh Bed Board
            </button>
        </div>
    </div>
    <div class="ipd-card-body">
        <!-- Bed Status Legend Filter -->
        <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--ipd-border);">
            <span style="font-weight: 700; font-size: 0.85rem; align-self: center; margin-right: 8px;">Filter by Status:</span>
            <span class="ipd-badge green" style="cursor: pointer;"><i class="fa fa-circle"></i> Available (<?php echo (int)($stats['available'] ?? 0); ?>)</span>
            <span class="ipd-badge blue" style="cursor: pointer;"><i class="fa fa-circle"></i> Occupied (<?php echo (int)($stats['occupied'] ?? 0); ?>)</span>
            <span class="ipd-badge info" style="cursor: pointer; background: #cffafe; color: #155e75;"><i class="fa fa-circle"></i> Cleaning (<?php echo (int)($stats['cleaning'] ?? 0); ?>)</span>
            <span class="ipd-badge yellow" style="cursor: pointer;"><i class="fa fa-circle"></i> Reserved (<?php echo (int)($stats['reserved'] ?? 0); ?>)</span>
            <span class="ipd-badge red" style="cursor: pointer;"><i class="fa fa-circle"></i> Blocked (<?php echo (int)($stats['blocked'] ?? 0); ?>)</span>
            <span class="ipd-badge" style="background:#e2e8f0; color:#334155; cursor: pointer;"><i class="fa fa-circle"></i> Maintenance (<?php echo (int)($stats['maintenance'] ?? 0); ?>)</span>
        </div>

        <?php if (empty($wards)): ?>
            <div style="text-align: center; padding: 40px; color: var(--ipd-slate);">
                <i class="fa fa-bed fa-3x" style="opacity: 0.3; margin-bottom: 12px;"></i>
                <p>No active hospital wards or beds configured yet. Please configure wards under Bed & Ward Management.</p>
            </div>
        <?php else: ?>
            <?php foreach ($wards as $ward): ?>
                <div class="ipd-ward-section">
                    <div class="ipd-ward-header">
                        <div>
                            <i class="fa fa-hospital-alt" style="margin-right: 6px; color: var(--ipd-primary);"></i>
                            <strong><?php echo htmlspecialchars($ward['ward_name']); ?></strong> 
                            <span style="font-weight: normal; font-size: 0.85rem; color: var(--ipd-slate);">
                                (<?php echo htmlspecialchars($ward['ward_code']); ?> &bull; <?php echo htmlspecialchars($ward['ward_type']); ?> &bull; <?php echo htmlspecialchars($ward['department']); ?>)
                            </span>
                        </div>
                        <div>
                            <span style="font-size: 0.82rem; color: var(--ipd-slate);">
                                Total: <strong><?php echo (int)($ward['stats']['total'] ?? 0); ?></strong> |
                                Available: <strong style="color: var(--ipd-success);"><?php echo (int)($ward['stats']['available'] ?? 0); ?></strong> |
                                Occupied: <strong style="color: var(--ipd-primary);"><?php echo (int)($ward['stats']['occupied'] ?? 0); ?></strong>
                            </span>
                        </div>
                    </div>

                    <div class="ipd-bed-grid">
                        <?php if (empty($ward['beds'])): ?>
                            <div style="grid-column: 1 / -1; color: var(--ipd-slate); font-size: 0.85rem; padding: 12px;">
                                No beds added to this ward yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($ward['beds'] as $bed): 
                                $statusClass = 'status-' . strtolower($bed['status']);
                                $isOccupied = ($bed['status'] === 'occupied');
                            ?>
                                <div class="ipd-bed-card <?php echo $statusClass; ?>">
                                    <div class="ipd-bed-top">
                                        <span class="ipd-bed-num">
                                            <i class="fa fa-bed" style="font-size: 0.9rem; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($bed['bed_number']); ?>
                                        </span>
                                        <span class="ipd-bed-badge <?php echo strtolower($bed['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($bed['status'])); ?>
                                        </span>
                                    </div>

                                    <div class="ipd-bed-details">
                                        <div><small>Category:</small> <strong><?php echo htmlspecialchars($bed['bed_category']); ?></strong></div>
                                        <div><small>Daily Rate:</small> $<?php echo number_format((float)$bed['daily_charge'], 2); ?></div>
                                    </div>

                                    <?php if ($isOccupied && !empty($bed['fname'])): ?>
                                        <div class="ipd-bed-patient" title="<?php echo htmlspecialchars($bed['fname'] . ' ' . $bed['lname']); ?>">
                                            <i class="fa fa-user" style="color: var(--ipd-primary); margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($bed['fname'] . ' ' . $bed['lname']); ?>
                                        </div>
                                        <div class="ipd-bed-details">
                                            <div>IPD No: <strong><?php echo htmlspecialchars($bed['ipd_number'] ?? ''); ?></strong></div>
                                            <div>Admitted: <?php echo (int)($bed['days_admitted'] ?? 0); ?> days ago</div>
                                            <?php if (!empty($bed['doc_lname'])): ?>
                                                <div>Dr. <?php echo htmlspecialchars($bed['doc_fname'] . ' ' . $bed['doc_lname']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($bed['status'] === 'cleaning'): ?>
                                        <div style="margin-top: 8px; font-size: 0.82rem; color: #0891b2;">
                                            <i class="fa fa-broom"></i> Undergoing Sanitation
                                        </div>
                                    <?php elseif ($bed['status'] === 'available'): ?>
                                        <div style="margin-top: 8px; font-size: 0.82rem; color: var(--ipd-success);">
                                            <i class="fa fa-check-circle"></i> Ready for Admission
                                        </div>
                                    <?php endif; ?>

                                    <div class="ipd-bed-actions">
                                        <?php if ($bed['status'] === 'available'): ?>
                                            <button class="ipd-btn ipd-btn-success ipd-btn-sm" style="width:100%; justify-content:center;" onclick="IPD.openModal('modal-new-admission')">
                                                <i class="fa fa-plus"></i> Allocate
                                            </button>
                                        <?php elseif ($bed['status'] === 'cleaning'): ?>
                                            <button class="ipd-btn ipd-btn-sm" style="width:100%; justify-content:center; background:#06b6d4; color:#fff;" onclick="IPD.updateBedStatus(<?php echo (int)$bed['id']; ?>, 'available')">
                                                <i class="fa fa-check"></i> Mark Clean & Available
                                            </button>
                                        <?php elseif ($isOccupied && !empty($bed['current_admission_id'])): ?>
                                            <button class="ipd-btn ipd-btn-outline ipd-btn-sm" style="flex:1;" onclick="IPD.openBedTransferModal(<?php echo (int)$bed['current_admission_id']; ?>, '<?php echo htmlspecialchars($bed['bed_number']); ?>')">
                                                <i class="fa fa-exchange-alt"></i> Transfer
                                            </button>
                                            <button class="ipd-btn ipd-btn-primary ipd-btn-sm" style="flex:1;" onclick="IPD.openAddVitalsModal(<?php echo (int)$bed['current_admission_id']; ?>, '<?php echo htmlspecialchars($bed['fname'] . ' ' . $bed['lname']); ?>', '<?php echo htmlspecialchars($bed['bed_number']); ?>')">
                                                <i class="fa fa-heartbeat"></i> Vitals
                                            </button>
                                        <?php else: ?>
                                            <button class="ipd-btn ipd-btn-outline ipd-btn-sm" style="width:100%; justify-content:center;" onclick="IPD.updateBedStatus(<?php echo (int)$bed['id']; ?>, 'available')">
                                                <i class="fa fa-unlock"></i> Set Available
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
