<?php
/** @var array $kpis */
$beds = $kpis['beds'] ?? [];
$patients = $kpis['patients'] ?? [];
$financials = $kpis['financials'] ?? [];
$wardUtil = $kpis['ward_utilization'] ?? [];
$doctorWorkload = $kpis['doctor_workload'] ?? [];
?>

<div class="ipd-form-row" style="margin-bottom: 24px;">
    <!-- Key Clinical & Operational Indicators -->
    <div class="ipd-card" style="margin-bottom: 0;">
        <div class="ipd-card-header">
            <h2><i class="fa fa-chart-line text-primary"></i> Inpatient Hospital Operational Metrics</h2>
        </div>
        <div class="ipd-card-body">
            <table class="ipd-table">
                <tbody>
                    <tr>
                        <td><strong>Total Hospital Bed Capacity:</strong></td>
                        <td><strong style="color:var(--ipd-dark); font-size:1.1rem;"><?php echo (int)($beds['total'] ?? 0); ?></strong> beds</td>
                    </tr>
                    <tr>
                        <td><strong>Current Bed Occupancy Rate:</strong></td>
                        <td>
                            <strong style="color:var(--ipd-primary); font-size:1.1rem;"><?php echo number_format((float)($beds['occupancy_rate'] ?? 0), 1); ?>%</strong>
                            <div style="background:#e2e8f0; border-radius:4px; height:8px; width:100%; margin-top:4px; overflow:hidden;">
                                <div style="background:var(--ipd-primary); height:100%; width:<?php echo min(100, (float)($beds['occupancy_rate'] ?? 0)); ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Currently Hospitalized Inpatients:</strong></td>
                        <td><strong style="color:var(--ipd-primary);"><?php echo (int)($patients['current_inpatients'] ?? 0); ?></strong> patients</td>
                    </tr>
                    <tr>
                        <td><strong>Admissions Today:</strong></td>
                        <td><span class="ipd-badge blue"><?php echo (int)($patients['admissions_today'] ?? 0); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Discharges Today:</strong></td>
                        <td><span class="ipd-badge green"><?php echo (int)($patients['discharges_today'] ?? 0); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Average Length of Stay (ALOS):</strong></td>
                        <td><strong><?php echo number_format((float)($patients['average_length_of_stay'] ?? 0), 1); ?></strong> days</td>
                    </tr>
                    <tr>
                        <td><strong>Emergency Triage Cases Today:</strong></td>
                        <td><span class="ipd-badge red"><?php echo (int)($patients['emergency_today'] ?? 0); ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Performance Overview -->
    <div class="ipd-card" style="margin-bottom: 0;">
        <div class="ipd-card-header">
            <h2><i class="fa fa-coins text-success"></i> Inpatient Department (IPD) Financial Performance</h2>
        </div>
        <div class="ipd-card-body">
            <table class="ipd-table">
                <tbody>
                    <tr>
                        <td><strong>Total Inpatient Gross Billed:</strong></td>
                        <td><strong style="font-size:1.1rem;">$<?php echo number_format((float)($financials['gross_billed'] ?? 0), 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total Inpatient Net Revenue:</strong></td>
                        <td><strong style="color:var(--ipd-primary); font-size:1.1rem;">$<?php echo number_format((float)($financials['net_billed'] ?? 0), 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total Payments & Deposits Collected:</strong></td>
                        <td><strong style="color:var(--ipd-success); font-size:1.1rem;">$<?php echo number_format((float)($financials['total_collected'] ?? 0), 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Total Outstanding Inpatient Balance:</strong></td>
                        <td><strong style="color:var(--ipd-danger); font-size:1.1rem;">$<?php echo number_format((float)($financials['outstanding_balance'] ?? 0), 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="ipd-form-row">
    <!-- Ward Utilization Breakdown -->
    <div>
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2><i class="fa fa-door-open text-primary"></i> Ward & Department Occupancy Breakdown</h2>
            </div>
            <div class="ipd-card-body" style="padding: 0;">
                <table class="ipd-table">
                    <thead>
                        <tr>
                            <th>Ward Name</th>
                            <th>Department</th>
                            <th>Total Beds</th>
                            <th>Occupied</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wardUtil)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:16px;">No wards configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($wardUtil as $w): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($w['ward_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($w['department']); ?></td>
                                    <td><?php echo (int)$w['total_beds']; ?></td>
                                    <td><strong style="color:var(--ipd-primary);"><?php echo (int)$w['occupied_beds']; ?></strong></td>
                                    <td><strong style="color:var(--ipd-success);"><?php echo (int)$w['available_beds']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Doctor Inpatient Workload -->
    <div>
        <div class="ipd-card">
            <div class="ipd-card-header">
                <h2><i class="fa fa-user-md text-primary"></i> Doctor Inpatient Workload</h2>
            </div>
            <div class="ipd-card-body" style="padding: 0;">
                <table class="ipd-table">
                    <thead>
                        <tr>
                            <th>Attending Physician</th>
                            <th>Specialty</th>
                            <th>Active Inpatients</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doctorWorkload)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:16px;">No active inpatients currently assigned to doctors.</td></tr>
                        <?php else: ?>
                            <?php foreach ($doctorWorkload as $doc): ?>
                                <tr>
                                    <td><strong>Dr. <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($doc['specialty'] ?? 'General Medicine'); ?></td>
                                    <td>
                                        <span class="ipd-badge blue" style="font-size:0.9rem; font-weight:700;">
                                            <?php echo (int)$doc['active_patients']; ?> patients
                                        </span>
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
