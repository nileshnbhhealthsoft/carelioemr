<?php
/** @var array $admissionsList */
/** @var array $dischargedList */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-clipboard-list text-primary"></i>
            Inpatient Multi-Disciplinary Discharge Clearances & Planning
        </h2>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>IPD & Patient</th>
                        <th>Ward & Bed</th>
                        <th>Attending Doctor</th>
                        <th>Doctor Clearance</th>
                        <th>Nursing Clearance</th>
                        <th>Pharmacy Clearance</th>
                        <th>Billing Clearance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $pendingDischarge = array_filter($admissionsList, fn($a) => in_array($a['admission_status'], ['admitted', 'transferred', 'discharge_requested']));
                    if (empty($pendingDischarge)): 
                    ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 24px; color: var(--ipd-slate);">
                                No currently admitted patients awaiting discharge planning.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingDischarge as $adm): 
                            $fullName = htmlspecialchars(($adm['fname'] ?? '') . ' ' . ($adm['lname'] ?? ''));
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo $fullName; ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        IPD: <?php echo htmlspecialchars($adm['ipd_number']); ?> (PID: <?php echo (int)$adm['pid']; ?>)
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($adm['bed_number'] ?? ''); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);"><?php echo htmlspecialchars($adm['ward_name'] ?? ''); ?></div>
                                </td>
                                <td>Dr. <?php echo htmlspecialchars(($adm['doc_fname'] ?? '') . ' ' . ($adm['doc_lname'] ?? '')); ?></td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updateClearance(<?php echo (int)$adm['id']; ?>, 'doctor', 1)">
                                        <i class="fa fa-user-md text-primary"></i> Doctor Clear
                                    </button>
                                </td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updateClearance(<?php echo (int)$adm['id']; ?>, 'nursing', 1)">
                                        <i class="fa fa-user-nurse text-info"></i> Nurse Clear
                                    </button>
                                </td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updateClearance(<?php echo (int)$adm['id']; ?>, 'pharmacy', 1)">
                                        <i class="fa fa-pills text-warning"></i> Pharmacy Clear
                                    </button>
                                </td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-outline" onclick="IPD.updateClearance(<?php echo (int)$adm['id']; ?>, 'billing', 1)">
                                        <i class="fa fa-file-invoice-dollar text-success"></i> Billing Clear
                                    </button>
                                </td>
                                <td>
                                    <button class="ipd-btn ipd-btn-sm ipd-btn-success" onclick="IPD.openDischargeModal(<?php echo (int)$adm['id']; ?>, '<?php echo addslashes($fullName); ?>', '<?php echo htmlspecialchars($adm['ipd_number']); ?>')">
                                        <i class="fa fa-sign-out-alt"></i> Complete Discharge
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

<!-- Discharged Patients Archive & Discharge Summary Generator -->
<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-archive text-primary"></i>
            Discharged Patients Archive & Medical Summaries
        </h2>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>IPD Number</th>
                        <th>Patient Name</th>
                        <th>Admission Date</th>
                        <th>Discharge Date</th>
                        <th>Length of Stay</th>
                        <th>Discharge Outcome</th>
                        <th>Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dischargedList)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No discharged patient records found in archive.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dischargedList as $dis): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dis['ipd_number']); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(($dis['fname'] ?? '') . ' ' . ($dis['lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">PID: <?php echo (int)$dis['pid']; ?></div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($dis['admission_date'])); ?></td>
                                <td><?php echo !empty($dis['discharged_at']) ? date('M d, Y H:i', strtotime($dis['discharged_at'])) : '-'; ?></td>
                                <td><?php echo (int)($dis['length_of_stay_days'] ?? 0); ?> Days</td>
                                <td>
                                    <span class="ipd-badge green">
                                        <?php echo htmlspecialchars(ucfirst($dis['admission_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?view_summary=<?php echo (int)$dis['id']; ?>" target="_blank" class="ipd-btn ipd-btn-sm ipd-btn-outline">
                                        <i class="fa fa-print text-primary"></i> Discharge Summary
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
