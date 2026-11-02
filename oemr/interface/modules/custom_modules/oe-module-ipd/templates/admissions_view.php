<?php
/** @var array $admissionsList */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-procedures text-primary"></i>
            Inpatient Admissions & Lifecycle Management
        </h2>
        <div style="display: flex; gap: 8px;">
            <button class="ipd-btn ipd-btn-primary" onclick="IPD.openModal('modal-new-admission')">
                <i class="fa fa-plus-circle"></i> New Inpatient Admission
            </button>
        </div>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>IPD Number</th>
                        <th>Patient Name & Demographics</th>
                        <th>Admission Date & Stay</th>
                        <th>Ward & Bed</th>
                        <th>Attending Physician</th>
                        <th>Provisional Diagnosis</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admissionsList)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: var(--ipd-slate);">
                                <i class="fa fa-info-circle fa-2x" style="opacity: 0.4; margin-bottom: 8px;"></i>
                                <p>No current inpatient admissions found matching filter.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admissionsList as $adm): 
                            $status = strtolower($adm['admission_status'] ?? 'admitted');
                            $badgeColor = match ($status) {
                                'admitted' => 'blue',
                                'transferred' => 'yellow',
                                'discharge_requested' => 'orange',
                                'discharged' => 'green',
                                'dama' => 'red',
                                'deceased' => 'red',
                                default => 'blue'
                            };
                            $fullName = htmlspecialchars(($adm['fname'] ?? '') . ' ' . ($adm['lname'] ?? ''));
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--ipd-primary); font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($adm['ipd_number']); ?>
                                    </strong>
                                    <div style="font-size: 0.78rem; color: var(--ipd-slate);">
                                        Type: <?php echo htmlspecialchars($adm['admission_type']); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo $fullName; ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--ipd-slate);">
                                        PID: <?php echo (int)$adm['pid']; ?> &bull; 
                                        <?php echo htmlspecialchars($adm['sex'] ?? ''); ?> &bull; 
                                        DOB: <?php echo htmlspecialchars($adm['DOB'] ?? 'N/A'); ?>
                                    </div>
                                    <?php if (!empty($adm['phone_cell'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--ipd-slate);">
                                            <i class="fa fa-phone-alt"></i> <?php echo htmlspecialchars($adm['phone_cell']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y H:i', strtotime($adm['admission_date'])); ?></div>
                                    <span class="ipd-badge" style="background: #f1f5f9; color: #475569;">
                                        <i class="fa fa-calendar-day"></i> <?php echo (int)($adm['length_of_stay_days'] ?? 0); ?> days stay
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($adm['bed_number'] ?? 'Unassigned'); ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--ipd-slate);">
                                        <?php echo htmlspecialchars($adm['ward_name'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong>Dr. <?php echo htmlspecialchars(($adm['doc_fname'] ?? '') . ' ' . ($adm['doc_lname'] ?? '')); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--ipd-slate);">
                                        <?php echo htmlspecialchars($adm['doc_specialty'] ?? 'General Medicine'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($adm['provisional_diagnosis'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($adm['provisional_diagnosis'] ?: 'Not recorded'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="ipd-badge <?php echo $badgeColor; ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status))); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Record Vital Signs" onclick="IPD.openAddVitalsModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>', '<?php echo htmlspecialchars($adm['bed_number'] ?? ''); ?>')">
                                            <i class="fa fa-heartbeat text-danger"></i>
                                        </button>
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Doctor Clinical Round" onclick="IPD.openDoctorRoundModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>')">
                                            <i class="fa fa-user-md text-primary"></i>
                                        </button>
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Prescribe Medication (MAR)" onclick="IPD.openPrescribeMedModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>')">
                                            <i class="fa fa-pills text-info"></i>
                                        </button>
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Transfer Bed" onclick="IPD.openBedTransferModal(<?php echo (int)$adm['id']; ?>, '<?php echo htmlspecialchars($adm['bed_number'] ?? ''); ?>')">
                                            <i class="fa fa-exchange-alt text-warning"></i>
                                        </button>
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Issue Visitor Pass" onclick="IPD.openAttendantPassModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>')">
                                            <i class="fa fa-id-card text-purple"></i>
                                        </button>
                                        <button class="ipd-btn ipd-btn-outline ipd-btn-sm" title="Discharge Workflow & Summary" onclick="IPD.openDischargeModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>', '<?php echo htmlspecialchars($adm['ipd_number']); ?>')">
                                            <i class="fa fa-sign-out-alt text-success"></i>
                                        </button>
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
