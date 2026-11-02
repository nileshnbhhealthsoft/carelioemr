<?php
/** @var array $bedsList */
/** @var array $wardsList */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-cogs text-primary"></i>
            Hospital Ward & Bed Infrastructure Configuration
        </h2>
        <div style="display: flex; gap: 8px;">
            <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-add-ward')">
                <i class="fa fa-plus"></i> Add Ward
            </button>
            <button class="ipd-btn ipd-btn-success" onclick="IPD.openModal('modal-add-bed')">
                <i class="fa fa-bed"></i> Add Bed
            </button>
        </div>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Bed Number</th>
                        <th>Ward & Department</th>
                        <th>Room</th>
                        <th>Category</th>
                        <th>Daily Tariff Rate</th>
                        <th>Status</th>
                        <th>Isolation</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bedsList)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No beds configured yet. Use the "Add Bed" button above.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bedsList as $bed): ?>
                            <tr>
                                <td>
                                    <strong><i class="fa fa-bed text-muted"></i> <?php echo htmlspecialchars($bed['bed_number']); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($bed['ward_name']); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--ipd-slate);">
                                        <?php echo htmlspecialchars($bed['ward_type'] ?? 'General'); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($bed['room_number'] ?? 'General Hall'); ?></td>
                                <td>
                                    <span class="ipd-badge" style="background: #f1f5f9; color: #334155;">
                                        <?php echo htmlspecialchars($bed['bed_category']); ?>
                                    </span>
                                </td>
                                <td><strong>$<?php echo number_format((float)$bed['daily_charge'], 2); ?></strong> / day</td>
                                <td>
                                    <span class="ipd-bed-badge <?php echo strtolower($bed['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($bed['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($bed['is_isolation'])): ?>
                                        <span class="ipd-badge red"><i class="fa fa-biohazard"></i> Isolation</span>
                                    <?php else: ?>
                                        <span class="text-muted"><small>Standard</small></span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($bed['notes'] ?? '-'); ?></small></td>
                                <td>
                                    <?php if ($bed['status'] === 'cleaning'): ?>
                                        <button class="ipd-btn ipd-btn-sm ipd-btn-info" onclick="IPD.updateBedStatus(<?php echo (int)$bed['id']; ?>, 'available')">
                                            <i class="fa fa-check"></i> Mark Clean
                                        </button>
                                    <?php elseif ($bed['status'] === 'available'): ?>
                                        <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updateBedStatus(<?php echo (int)$bed['id']; ?>, 'maintenance')">
                                            <i class="fa fa-wrench"></i> Maintenance
                                        </button>
                                    <?php elseif ($bed['status'] === 'maintenance'): ?>
                                        <button class="ipd-btn ipd-btn-sm ipd-btn-success" onclick="IPD.updateBedStatus(<?php echo (int)$bed['id']; ?>, 'available')">
                                            <i class="fa fa-check"></i> Ready
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
