<?php
/** @var array $summaryData */
$adm = $summaryData['admission'] ?? [];
$dis = $summaryData['discharge'] ?? [];
$surgeries = $summaryData['surgeries'] ?? [];
$meds = $summaryData['medications'] ?? [];
$vitals = $summaryData['latest_vitals'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inpatient Discharge Summary - <?php echo htmlspecialchars($adm['ipd_number'] ?? ''); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 30px;
            background: #fff;
            line-height: 1.5;
            font-size: 13px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #334155;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }
        .summary-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 4px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e1b4b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin: 16px 0 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #f1f5f9;
        }
        .footer-signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block {
            text-align: center;
            width: 200px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
            Print Discharge Summary
        </button>
    </div>

    <div class="header">
        <h1>CARELIO HOSPITAL & MEDICAL CENTER</h1>
        <p>Inpatient Department (IPD) &bull; Comprehensive Clinical Discharge Summary</p>
    </div>

    <div class="summary-title">OFFICIAL INPATIENT DISCHARGE SUMMARY</div>

    <div class="info-grid">
        <div>
            <div><strong>Patient Name:</strong> <?php echo htmlspecialchars(($adm['fname'] ?? '') . ' ' . ($adm['lname'] ?? '')); ?></div>
            <div><strong>Patient PID:</strong> <?php echo (int)($adm['pid'] ?? 0); ?> &bull; <strong>Gender:</strong> <?php echo htmlspecialchars($adm['sex'] ?? ''); ?></div>
            <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($adm['DOB'] ?? ''); ?></div>
            <div><strong>Contact:</strong> <?php echo htmlspecialchars($adm['phone_cell'] ?? 'N/A'); ?></div>
        </div>
        <div>
            <div><strong>IPD Number:</strong> <span style="font-weight: bold; color: #4338ca;"><?php echo htmlspecialchars($adm['ipd_number'] ?? ''); ?></span></div>
            <div><strong>Admission:</strong> <?php echo date('M d, Y H:i', strtotime($adm['admission_date'])); ?></div>
            <div><strong>Discharge:</strong> <?php echo !empty($dis['discharge_date']) ? date('M d, Y H:i', strtotime($dis['discharge_date'])) : date('M d, Y H:i'); ?></div>
            <div><strong>Length of Stay:</strong> <?php echo (int)($adm['length_of_stay_days'] ?? 0); ?> Days &bull; <strong>Bed:</strong> <?php echo htmlspecialchars($adm['bed_number'] ?? ''); ?> (<?php echo htmlspecialchars($adm['ward_name'] ?? ''); ?>)</div>
        </div>
    </div>

    <div><strong>Attending Consultant:</strong> Dr. <?php echo htmlspecialchars(($adm['doc_fname'] ?? '') . ' ' . ($adm['doc_lname'] ?? '')); ?> (<?php echo htmlspecialchars($adm['doc_specialty'] ?? 'Physician'); ?>)</div>

    <div class="section-title">Clinical Diagnoses</div>
    <div><strong>Provisional Diagnosis at Admission:</strong> <?php echo htmlspecialchars($adm['provisional_diagnosis'] ?: 'N/A'); ?></div>
    <div style="margin-top: 4px;">
        <strong>Final Discharge Diagnosis:</strong> 
        <span style="font-weight: bold; color: #1e1b4b;"><?php echo htmlspecialchars($dis['final_diagnosis'] ?? 'Not specified'); ?></span>
        <?php if (!empty($dis['icd10_code'])): ?>
            (ICD-10: <?php echo htmlspecialchars($dis['icd10_code']); ?>)
        <?php endif; ?>
    </div>
    <div style="margin-top: 4px;"><strong>Discharge Outcome:</strong> <?php echo htmlspecialchars($dis['discharge_type'] ?? 'Normal Medical Discharge'); ?></div>

    <?php if (!empty($surgeries)): ?>
        <div class="section-title">Surgical / Interventional Procedures Performed</div>
        <table>
            <thead>
                <tr>
                    <th>Procedure</th>
                    <th>Date & Time</th>
                    <th>Operating Theatre</th>
                    <th>Lead Surgeon</th>
                    <th>Anesthesia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($surgeries as $s): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['surgery_name']); ?></strong></td>
                        <td><?php echo date('M d, Y H:i', strtotime($s['scheduled_start'])); ?></td>
                        <td><?php echo htmlspecialchars($s['theatre_name'] ?? 'Main OT'); ?></td>
                        <td>Dr. <?php echo htmlspecialchars(($s['surgeon_fname'] ?? '') . ' ' . ($s['surgeon_lname'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($s['anesthesia_type'] ?? 'General'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="section-title">Hospital Course & Treatment Administered</div>
    <div style="white-space: pre-line;"><?php echo htmlspecialchars($dis['hospital_course'] ?: 'Patient was admitted for medical management. Responded favorably to intravenous antibiotics, analgesics, supportive care, and routine inpatient monitoring.'); ?></div>

    <div class="section-title">Physical Condition & Discharge Vitals</div>
    <div><strong>Clinical Condition:</strong> <?php echo htmlspecialchars($dis['condition_at_discharge'] ?: 'Afebrile, hemodynamically stable, ambulatory, vitals within normal parameters.'); ?></div>
    <?php if (!empty($vitals)): ?>
        <div style="margin-top: 4px;">
            <small>
                Pulse: <strong><?php echo (int)($vitals['pulse_rate'] ?? 72); ?> bpm</strong> &bull; 
                BP: <strong><?php echo (int)($vitals['bp_systolic'] ?? 120); ?>/<?php echo (int)($vitals['bp_diastolic'] ?? 80); ?> mmHg</strong> &bull; 
                Temp: <strong><?php echo number_format((float)($vitals['temperature'] ?? 98.6), 1); ?> °F</strong> &bull; 
                SpO2: <strong><?php echo (int)($vitals['spo2'] ?? 98); ?>%</strong>
            </small>
        </div>
    <?php endif; ?>

    <div class="section-title">Discharge Medication Regimen</div>
    <div style="white-space: pre-line; background: #fdfdfd; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 4px;">
        <?php echo htmlspecialchars($dis['discharge_medications'] ?: 'Take medications strictly as per prescription provided at the hospital pharmacy.'); ?>
    </div>

    <div class="section-title">Follow-up Instructions, Diet & Activity</div>
    <div><strong>Diet & Activity:</strong> Regular balanced diet, adequate oral hydration, avoid heavy exertion for 1 week.</div>
    <div><strong>Follow-up Appointment:</strong> <?php echo !empty($dis['followup_date']) ? date('M d, Y', strtotime($dis['followup_date'])) : 'In 7 days or as instructed'; ?></div>
    <?php if (!empty($dis['followup_instructions'])): ?>
        <div><strong>Instructions:</strong> <?php echo htmlspecialchars($dis['followup_instructions']); ?></div>
    <?php endif; ?>
    <div style="margin-top: 6px; color: #b91c1c; font-size: 11px;">
        <strong>RED FLAGS / EMERGENCY NOTICE:</strong> In case of high fever, persistent vomiting, severe pain, breathing difficulty, or wound bleeding, report immediately to the 24/7 Emergency Department.
    </div>

    <div class="footer-signatures">
        <div class="sig-block">
            Attending Physician Signature
        </div>
        <div class="sig-block">
            Nurse In-charge Signature
        </div>
        <div class="sig-block">
            Medical Superintendent
        </div>
    </div>
</body>
</html>
