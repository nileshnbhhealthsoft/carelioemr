<?php
/** @var array $recentVitals */
/** @var array $recentMar */
/** @var array $nursingNotes */
?>

<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-heartbeat text-danger"></i>
            Inpatient Vital Signs Monitoring Sheet
        </h2>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient / Bed</th>
                        <th>Pulse (bpm)</th>
                        <th>Blood Pressure</th>
                        <th>Temp (°F)</th>
                        <th>Resp Rate</th>
                        <th>SpO2 (%)</th>
                        <th>Pain (0-10)</th>
                        <th>Blood Sugar</th>
                        <th>Consciousness / O2</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentVitals)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No vital signs recorded yet. Use the Vitals button on an active admission.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentVitals as $v): ?>
                            <tr>
                                <td><strong><?php echo date('M d, H:i', strtotime($v['recorded_at'])); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(($v['fname'] ?? '') . ' ' . ($v['lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        Bed: <?php echo htmlspecialchars($v['bed_number'] ?? ''); ?> (<?php echo htmlspecialchars($v['ipd_number'] ?? ''); ?>)
                                    </div>
                                </td>
                                <td>
                                    <?php if ($v['pulse_rate'] > 100 || $v['pulse_rate'] < 60): ?>
                                        <span class="ipd-badge red"><strong><?php echo (int)$v['pulse_rate']; ?></strong> bpm</span>
                                    <?php else: ?>
                                        <strong><?php echo (int)$v['pulse_rate']; ?></strong> bpm
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['bp_systolic'] >= 140 || $v['bp_diastolic'] >= 90): ?>
                                        <span class="ipd-badge red"><?php echo (int)$v['bp_systolic']; ?>/<?php echo (int)$v['bp_diastolic']; ?> mmHg</span>
                                    <?php else: ?>
                                        <?php echo (int)$v['bp_systolic']; ?>/<?php echo (int)$v['bp_diastolic']; ?> mmHg
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['temperature'] >= 100.4): ?>
                                        <span class="ipd-badge red"><?php echo number_format((float)$v['temperature'], 1); ?> °F</span>
                                    <?php else: ?>
                                        <?php echo number_format((float)$v['temperature'], 1); ?> °F
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int)$v['resp_rate']; ?> /min</td>
                                <td>
                                    <?php if ($v['spo2'] < 95): ?>
                                        <span class="ipd-badge red"><?php echo (int)$v['spo2']; ?>%</span>
                                    <?php else: ?>
                                        <span class="ipd-badge green"><?php echo (int)$v['spo2']; ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['pain_score'] >= 7): ?>
                                        <span class="ipd-badge red">Score <?php echo (int)$v['pain_score']; ?></span>
                                    <?php elseif ($v['pain_score'] >= 4): ?>
                                        <span class="ipd-badge yellow">Score <?php echo (int)$v['pain_score']; ?></span>
                                    <?php else: ?>
                                        Score <?php echo (int)$v['pain_score']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $v['blood_sugar'] ? number_format((float)$v['blood_sugar'], 1) . ' mg/dL' : '-'; ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($v['consciousness'] ?? 'Alert'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($v['oxygen_support'] ?? 'Room Air'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(($v['nurse_fname'] ?? '') . ' ' . ($v['nurse_lname'] ?? 'Staff')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MAR Administration Log -->
<div class="ipd-card">
    <div class="ipd-card-header">
        <h2>
            <i class="fa fa-clipboard-check text-success"></i>
            Medication Administration Record (MAR) Administration Log
        </h2>
    </div>
    <div class="ipd-card-body" style="padding: 0;">
        <div style="overflow-x: auto;">
            <table class="ipd-table">
                <thead>
                    <tr>
                        <th>Administered Datetime</th>
                        <th>Patient / IPD</th>
                        <th>Medication & Dose</th>
                        <th>Route & Site</th>
                        <th>Status</th>
                        <th>Administered By</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMar)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--ipd-slate);">
                                No medication administrations logged yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentMar as $mar): 
                            $status = strtolower($mar['status'] ?? 'given');
                            $badge = match($status) {
                                'given' => 'green',
                                'held' => 'yellow',
                                'refused' => 'red',
                                'missed' => 'red',
                                default => 'blue'
                            };
                        ?>
                            <tr>
                                <td><strong><?php echo date('M d, H:i', strtotime($mar['administered_time'])); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(($mar['fname'] ?? '') . ' ' . ($mar['lname'] ?? '')); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--ipd-slate);">
                                        Bed: <?php echo htmlspecialchars($mar['bed_number'] ?? ''); ?> (<?php echo htmlspecialchars($mar['ipd_number'] ?? ''); ?>)
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($mar['medicine_name']); ?></strong>
                                    <span class="ipd-badge blue"><?php echo htmlspecialchars($mar['dosage_given'] ?: $mar['dosage']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($mar['route'] ?? 'Oral'); ?>
                                    <?php if (!empty($mar['site_of_injection'])): ?>
                                        <div style="font-size:0.75rem; color:var(--ipd-slate);">Site: <?php echo htmlspecialchars($mar['site_of_injection']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ipd-badge <?php echo $badge; ?>">
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(($mar['nurse_fname'] ?? '') . ' ' . ($mar['nurse_lname'] ?? 'Staff')); ?></td>
                                <td><small><?php echo htmlspecialchars($mar['remarks'] ?: '-'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
