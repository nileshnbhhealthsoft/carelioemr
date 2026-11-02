<?php
/** @var array $triageList */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-ambulance text-danger"></i>
            Emergency & Casualty Department Triage Queue
        </h2>
        <div>
            <button class="ipd-btn ipd-btn-danger" onclick="IPD.openModal('modal-register-triage')">
                <i class="fa fa-plus-circle"></i> Register Emergency Patient
            </button>
        </div>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Triage Number</th>
                        <th>Patient Name & PID</th>
                        <th>Arrival & Wait Time</th>
                        <th>Triage Priority Level</th>
                        <th>Chief Complaint</th>
                        <th>Initial Vitals</th>
                        <th>Assigned Physician</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($triageList)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No emergency triage cases currently in queue.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($triageList as $trg): 
                            $lvl = strtolower($trg['triage_level'] ?? 'yellow');
                            $badgeColor = match($lvl) {
                                'red' => 'red',
                                'orange' => 'orange',
                                'yellow' => 'yellow',
                                'green' => 'green',
                                'blue' => 'blue',
                                default => 'yellow'
                            };
                            $fullName = htmlspecialchars(($trg['fname'] ?? '') . ' ' . ($trg['lname'] ?? ''));
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--ipd-danger);"><?php echo htmlspecialchars($trg['triage_number']); ?></strong>
                                    <?php if (!empty($trg['bay_number'])): ?>
                                        <div style="font-size:0.75rem; color:var(--ipd-slate);">Bay: <?php echo htmlspecialchars($trg['bay_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $fullName; ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        PID: <?php echo (int)$trg['pid']; ?> &bull; <?php echo htmlspecialchars($trg['sex'] ?? ''); ?> &bull; <?php echo htmlspecialchars($trg['DOB'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo date('H:i', strtotime($trg['arrival_time'])); ?></div>
                                    <small class="text-muted"><?php echo (int)($trg['wait_time_minutes'] ?? 0); ?> mins ago</small>
                                </td>
                                <td>
                                    <span class="ipd-badge <?php echo $badgeColor; ?>" style="font-weight: 800;">
                                        <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars(ucfirst($trg['triage_level'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($trg['chief_complaint']); ?>">
                                        <?php echo htmlspecialchars($trg['chief_complaint']); ?>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        BP: <?php echo (int)($trg['bp_systolic'] ?? 0); ?>/<?php echo (int)($trg['bp_diastolic'] ?? 0); ?> &bull;
                                        Pulse: <?php echo (int)($trg['pulse'] ?? 0); ?> &bull;
                                        SpO2: <?php echo (int)($trg['spo2'] ?? 0); ?>%
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($trg['doc_lname'])): ?>
                                        Dr. <?php echo htmlspecialchars($trg['doc_fname'] . ' ' . $trg['doc_lname']); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><small>Unassigned</small></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($trg['status'] === 'admitted_to_ipd'): ?>
                                        <span class="ipd-badge green"><i class="fa fa-check"></i> Admitted to IPD</span>
                                        <div style="font-size:0.72rem; color:var(--ipd-primary);"><?php echo htmlspecialchars($trg['ipd_number'] ?? ''); ?></div>
                                    <?php else: ?>
                                        <span class="ipd-badge yellow"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $trg['status']))); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($trg['status'] !== 'admitted_to_ipd'): ?>
                                        <button class="ipd-btn ipd-btn-sm ipd-btn-primary" onclick="IPD.openTriageConvertModal(<?php echo (int)$trg['id']; ?>, '<?php echo htmlspecialchars($trg['triage_number']); ?>', '<?php echo addslashes($fullName); ?>', <?php echo (int)$trg['pid']; ?>)">
                                            <i class="fa fa-bed"></i> Admit to IPD
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
