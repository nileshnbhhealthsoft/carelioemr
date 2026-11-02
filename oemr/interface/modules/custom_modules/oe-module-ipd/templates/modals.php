<?php
/** @var array $wardsList */
/** @var array $doctorsList */
/** @var array $theatresList */
/** @var array $tariffsList */
?>

<!-- 1. Modal: New Inpatient Admission -->
<div id="modal-new-admission" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-user-plus text-primary"></i> Patient Admission & Bed Allocation</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-new-admission">&times;</button>
        </div>
        <form data-ipd-action="create_admission">
            <div class="ipd-modal-body">
                <!-- Patient Search & Selection -->
                <div class="ipd-form-group" style="position: relative;">
                    <label class="ipd-form-label">Search Existing OpenEMR Patient <span class="text-danger">*</span></label>
                    <input type="text" id="search-patient-input" class="ipd-form-control" placeholder="Search by Patient Name, PID, or Phone Number..." autocomplete="off">
                    <input type="hidden" name="pid" id="admission-pid" required>
                    <div id="patient-search-results" class="ipd-search-results"></div>
                    <div id="selected-patient-details"></div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Admission Type <span class="text-danger">*</span></label>
                        <select name="admission_type" class="ipd-form-control" required>
                            <option value="Elective">Elective</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Referral">Referral</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Admission Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="admission_date" class="ipd-form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Ward & Bed Allocation <span class="text-danger">*</span></label>
                        <select name="bed_id" id="admission-bed-selector" class="ipd-form-control" required>
                            <option value="">-- Select Available Bed --</option>
                            <?php 
                            $availBeds = (new \OpenEMR\Modules\Ipd\Controller\BedController())->getAvailableBeds();
                            foreach ($availBeds as $ab): ?>
                                <option value="<?php echo (int)$ab['id']; ?>" data-ward="<?php echo (int)$ab['ward_id']; ?>">
                                    <?php echo htmlspecialchars($ab['ward_name']); ?> - <?php echo htmlspecialchars($ab['bed_number']); ?> (<?php echo htmlspecialchars($ab['bed_category']); ?> - $<?php echo number_format((float)$ab['daily_charge'], 2); ?>/day)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Attending Doctor <span class="text-danger">*</span></label>
                        <select name="attending_doctor_id" class="ipd-form-control" required>
                            <option value="">-- Select Physician --</option>
                            <?php foreach ($doctorsList as $doc): ?>
                                <option value="<?php echo (int)$doc['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?> (<?php echo htmlspecialchars($doc['specialty'] ?? 'Physician'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Department</label>
                        <input type="text" name="department" class="ipd-form-control" value="General Medicine">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">ICD-10 Code (Optional)</label>
                        <input type="text" name="icd10_code" class="ipd-form-control" placeholder="e.g. A09, J18.9, I10">
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Provisional Diagnosis / Reason for Admission</label>
                    <textarea name="provisional_diagnosis" class="ipd-form-control" rows="2" placeholder="Provisional clinical diagnosis..."></textarea>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Chief Complaints & Clinical Presentation</label>
                    <textarea name="chief_complaints" class="ipd-form-control" rows="2" placeholder="Chief complaints at time of admission..."></textarea>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--ipd-border);">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 12px; color: var(--ipd-dark);">
                    <i class="fa fa-users text-primary"></i> Attendant & Emergency Contact Details
                </h4>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Attendant Name</label>
                        <input type="text" name="attendant_name" class="ipd-form-control" placeholder="Name of primary attendant">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Relationship to Patient</label>
                        <input type="text" name="attendant_relation" class="ipd-form-control" placeholder="Spouse, Parent, Child, Sibling">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Attendant Contact Phone</label>
                        <input type="text" name="attendant_phone" class="ipd-form-control" placeholder="Phone number">
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-new-admission">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-check"></i> Complete Admission</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal: Bed Transfer -->
<div id="modal-bed-transfer" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-exchange-alt text-warning"></i> Transfer Inpatient Bed</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-bed-transfer">&times;</button>
        </div>
        <form data-ipd-action="transfer_bed">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="transfer-admission-id">
                <p>Current Bed: <strong id="transfer-current-bed-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Target Bed (Available Only) <span class="text-danger">*</span></label>
                    <select name="new_bed_id" class="ipd-form-control" required>
                        <option value="">-- Select Target Bed --</option>
                        <?php foreach ($availBeds as $ab): ?>
                            <option value="<?php echo (int)$ab['id']; ?>">
                                <?php echo htmlspecialchars($ab['ward_name']); ?> - <?php echo htmlspecialchars($ab['bed_number']); ?> (<?php echo htmlspecialchars($ab['bed_category']); ?> - $<?php echo number_format((float)$ab['daily_charge'], 2); ?>/day)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Reason for Transfer</label>
                    <textarea name="transfer_reason" class="ipd-form-control" rows="3" placeholder="Condition improved/deteriorated, ICU step-down, patient request..."></textarea>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-bed-transfer">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-check"></i> Confirm Bed Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal: Add Vital Signs -->
<div id="modal-add-vitals" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-heartbeat text-danger"></i> Record Inpatient Vital Signs</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-add-vitals">&times;</button>
        </div>
        <form data-ipd-action="record_vitals">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="vitals-admission-id">
                <p>Patient: <strong id="vitals-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Pulse (bpm)</label>
                        <input type="number" name="pulse_rate" class="ipd-form-control" placeholder="60-100">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">BP Systolic (mmHg)</label>
                        <input type="number" name="bp_systolic" class="ipd-form-control" placeholder="120">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">BP Diastolic (mmHg)</label>
                        <input type="number" name="bp_diastolic" class="ipd-form-control" placeholder="80">
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Temperature (°F)</label>
                        <input type="number" step="0.1" name="temperature" class="ipd-form-control" placeholder="98.6">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Respiratory Rate (/min)</label>
                        <input type="number" name="resp_rate" class="ipd-form-control" placeholder="16">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">SpO2 (%)</label>
                        <input type="number" name="spo2" class="ipd-form-control" placeholder="98">
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Pain Scale (0-10)</label>
                        <input type="number" min="0" max="10" name="pain_score" class="ipd-form-control" placeholder="0">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Blood Sugar (mg/dL)</label>
                        <input type="number" step="0.1" name="blood_sugar" class="ipd-form-control" placeholder="Optional">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Level of Consciousness</label>
                        <select name="consciousness" class="ipd-form-control">
                            <option value="Alert">Alert (A)</option>
                            <option value="Voice">Voice (V)</option>
                            <option value="Pain">Pain (P)</option>
                            <option value="Unresponsive">Unresponsive (U)</option>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Oxygen Support</label>
                    <input type="text" name="oxygen_support" class="ipd-form-control" value="Room Air" placeholder="e.g. Room Air, Nasal Cannula 2L/min, Face Mask 6L">
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Clinical / Nursing Observations</label>
                    <textarea name="clinical_notes" class="ipd-form-control" rows="2" placeholder="Patient condition, complaints, or response to medication..."></textarea>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-add-vitals">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-save"></i> Save Vitals</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal: Doctor Clinical Round -->
<div id="modal-doctor-round" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-user-md text-primary"></i> Record Doctor Daily Inpatient Round (SOAP)</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-doctor-round">&times;</button>
        </div>
        <form data-ipd-action="add_doctor_round">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="round-admission-id">
                <p>Patient: <strong id="round-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Subjective Notes (Patient complaints, symptoms)</label>
                    <textarea name="subjective_notes" class="ipd-form-control" rows="2" placeholder="Patient expresses..."></textarea>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Objective Findings (Examination, systemic review)</label>
                    <textarea name="objective_findings" class="ipd-form-control" rows="2" placeholder="Chest clear, abdomen soft, no edema..."></textarea>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Clinical Assessment</label>
                    <textarea name="assessment" class="ipd-form-control" rows="2" placeholder="Current condition evaluation, disease progress..."></textarea>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Treatment Plan & Orders <span class="text-danger">*</span></label>
                    <textarea name="treatment_plan" class="ipd-form-control" rows="3" placeholder="Medication changes, diagnostic requests, step-down plan..." required></textarea>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Diet Orders</label>
                        <input type="text" name="diet_orders" class="ipd-form-control" placeholder="Normal, Diabetic, Low Salt, NPO, Liquid">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Activity Orders</label>
                        <input type="text" name="activity_orders" class="ipd-form-control" placeholder="Complete Bed Rest, Ambulatory, Chair">
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-doctor-round">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-save"></i> Save Round</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal: Prescribe Medication Order -->
<div id="modal-prescribe-med" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-pills text-primary"></i> Prescribe Inpatient Medication</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-prescribe-med">&times;</button>
        </div>
        <form data-ipd-action="add_medication_order">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="med-admission-id">
                <p>Patient: <strong id="med-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Medicine / Drug Name <span class="text-danger">*</span></label>
                    <input type="text" name="medicine_name" class="ipd-form-control" placeholder="e.g. Ceftriaxone Inj, Paracetamol Tab, Pantoprazole IV" required>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Dosage <span class="text-danger">*</span></label>
                        <input type="text" name="dosage" class="ipd-form-control" placeholder="e.g. 1g, 500mg, 40mg" required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Route <span class="text-danger">*</span></label>
                        <select name="route" class="ipd-form-control" required>
                            <option value="Oral">Oral</option>
                            <option value="IV">Intravenous (IV)</option>
                            <option value="IM">Intramuscular (IM)</option>
                            <option value="SC">Subcutaneous (SC)</option>
                            <option value="Topical">Topical</option>
                            <option value="Inhalation">Inhalation</option>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="ipd-form-control" required>
                            <option value="BD (Twice a day)">BD (Twice a day)</option>
                            <option value="TDS (Thrice a day)">TDS (Thrice a day)</option>
                            <option value="Once Daily (OD)">Once Daily (OD)</option>
                            <option value="QID (Four times a day)">QID (Four times a day)</option>
                            <option value="STAT (Immediately)">STAT (Immediately)</option>
                            <option value="PRN (As needed)">PRN (As needed)</option>
                            <option value="Q4H (Every 4 hours)">Q4H (Every 4 hours)</option>
                            <option value="Q6H (Every 6 hours)">Q6H (Every 6 hours)</option>
                            <option value="Q8H (Every 8 hours)">Q8H (Every 8 hours)</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Start Date</label>
                        <input type="date" name="start_date" class="ipd-form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Clinical Instructions</label>
                    <textarea name="instructions" class="ipd-form-control" rows="2" placeholder="Slow IV infusion in 100ml NS, with meals, monitor BP..."></textarea>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-prescribe-med">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-prescription"></i> Prescribe Order</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Modal: MAR Administer Medication -->
<div id="modal-administer-mar" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-check-square text-success"></i> Medication Administration Record (MAR)</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-administer-mar">&times;</button>
        </div>
        <form data-ipd-action="administer_medication">
            <div class="ipd-modal-body">
                <input type="hidden" name="order_id" id="mar-order-id">
                <input type="hidden" name="admission_id" id="mar-admission-id">
                <p>Medication: <strong id="mar-medicine-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Administration Status <span class="text-danger">*</span></label>
                    <select name="status" class="ipd-form-control" required>
                        <option value="given">Given / Administered</option>
                        <option value="held">Held (Physician Order / Vitals)</option>
                        <option value="refused">Refused by Patient</option>
                        <option value="missed">Missed / Delayed</option>
                    </select>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Dosage Administered</label>
                        <input type="text" name="dosage_given" id="mar-dosage-input" class="ipd-form-control">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Site of Injection (if injectable)</label>
                        <input type="text" name="site_of_injection" class="ipd-form-control" placeholder="Left Deltoid, Right Gluteal, IV Cannula">
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Nurse Remarks</label>
                    <input type="text" name="remarks" class="ipd-form-control" placeholder="Tolerated well, no allergic symptoms...">
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-administer-mar">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-success"><i class="fa fa-check"></i> Record in MAR</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Modal: Schedule Surgery -->
<div id="modal-schedule-surgery" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-calendar-plus text-primary"></i> Schedule Operation Theatre Surgery</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-schedule-surgery">&times;</button>
        </div>
        <form data-ipd-action="schedule_surgery">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="surgery-admission-id">
                <p>Patient / Inpatient: <strong id="surgery-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Surgical Procedure Name <span class="text-danger">*</span></label>
                    <input type="text" name="surgery_name" class="ipd-form-control" placeholder="e.g. Laparoscopic Appendectomy, Total Hip Replacement, LSCS" required>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Operation Theatre <span class="text-danger">*</span></label>
                        <select name="ot_id" class="ipd-form-control" required>
                            <option value="">-- Select OT Room --</option>
                            <?php foreach ($theatresList as $ot): ?>
                                <option value="<?php echo (int)$ot['id']; ?>">
                                    <?php echo htmlspecialchars($ot['theatre_name']); ?> (<?php echo htmlspecialchars($ot['theatre_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Lead Surgeon <span class="text-danger">*</span></label>
                        <select name="lead_surgeon_id" class="ipd-form-control" required>
                            <option value="">-- Select Lead Surgeon --</option>
                            <?php foreach ($doctorsList as $doc): ?>
                                <option value="<?php echo (int)$doc['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?> (<?php echo htmlspecialchars($doc['specialty'] ?? 'Surgeon'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Anesthetist</label>
                        <select name="anesthetist_id" class="ipd-form-control">
                            <option value="">-- Select Anesthetist --</option>
                            <?php foreach ($doctorsList as $doc): ?>
                                <option value="<?php echo (int)$doc['id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Anesthesia Type</label>
                        <select name="anesthesia_type" class="ipd-form-control">
                            <option value="General">General Anesthesia</option>
                            <option value="Spinal">Spinal Anesthesia</option>
                            <option value="Epidural">Epidural</option>
                            <option value="Local">Local Anesthesia</option>
                            <option value="Sedation">Conscious Sedation</option>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Scheduled Start Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_start" class="ipd-form-control" value="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>" required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Scheduled End Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="scheduled_end" class="ipd-form-control" value="<?php echo date('Y-m-d\TH:i', strtotime('+3 hours')); ?>" required>
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-schedule-surgery">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-calendar-check"></i> Book Surgery</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Modal: Complete Discharge Workflow -->
<div id="modal-complete-discharge" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-sign-out-alt text-success"></i> Patient Discharge Workflow & Summary</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-complete-discharge">&times;</button>
        </div>
        <form data-ipd-action="complete_discharge">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="discharge-admission-id">
                <p>Patient: <strong id="discharge-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Discharge Type <span class="text-danger">*</span></label>
                        <select name="discharge_type" class="ipd-form-control" required>
                            <option value="Normal">Normal Medical Discharge</option>
                            <option value="Transferred">Transferred to Another Facility</option>
                            <option value="DAMA">DAMA / LAMA (Against Medical Advice)</option>
                            <option value="Deceased">Deceased / Expired</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Discharge Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="discharge_date" class="ipd-form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Final Diagnosis <span class="text-danger">*</span></label>
                        <input type="text" name="final_diagnosis" class="ipd-form-control" placeholder="Final clinical diagnosis..." required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">ICD-10 Code</label>
                        <input type="text" name="icd10_code" class="ipd-form-control" placeholder="e.g. K35.80">
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Hospital Course & Summary of Treatment</label>
                    <textarea name="hospital_course" class="ipd-form-control" rows="2" placeholder="Course during hospitalization, clinical milestones, response to treatment..."></textarea>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Condition at Discharge</label>
                    <input type="text" name="condition_at_discharge" class="ipd-form-control" value="Clinically Stable, Afebrile, Ambulatory">
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Discharge Medications (with dosage and duration)</label>
                    <textarea name="discharge_medications" class="ipd-form-control" rows="3" placeholder="1. Tab Amoxicillin 500mg 1 TDS x 5 days&#10;2. Tab Paracetamol 650mg 1 SOS for fever"></textarea>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Follow-up Instructions & Restrictions</label>
                        <input type="text" name="followup_instructions" class="ipd-form-control" placeholder="Review in OPD, stitch removal, avoid lifting heavy objects">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Follow-up Appointment Date</label>
                        <input type="date" name="followup_date" class="ipd-form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #166534;">
                    <i class="fa fa-info-circle"></i> <strong>Bed Release:</strong> Submitting this discharge will automatically release the assigned hospital bed and mark its status as <strong>Cleaning/Sanitizing</strong>.
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-complete-discharge">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-success"><i class="fa fa-check"></i> Finalize Discharge & Release Bed</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Modal: Register Emergency Triage -->
<div id="modal-register-triage" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog lg">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-ambulance text-danger"></i> Register Emergency & Casualty Patient</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-register-triage">&times;</button>
        </div>
        <form data-ipd-action="register_triage">
            <div class="ipd-modal-body">
                <div class="ipd-form-group" style="position: relative;">
                    <label class="ipd-form-label">Patient Selection <span class="text-danger">*</span></label>
                    <input type="text" id="triage-patient-search" class="ipd-form-control" placeholder="Search OpenEMR patient by name or PID..." autocomplete="off">
                    <input type="hidden" name="pid" id="triage-pid" required>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Triage Priority Category <span class="text-danger">*</span></label>
                        <select name="triage_level" class="ipd-form-control" required>
                            <option value="Red" style="background:#fee2e2; color:#991b1b; font-weight:700;">Level 1 - Red (Immediate Resuscitation)</option>
                            <option value="Orange" style="background:#ffedd5; color:#9a3412; font-weight:700;">Level 2 - Orange (Very Urgent / Emergent)</option>
                            <option value="Yellow" selected style="background:#fef9c3; color:#854d0e; font-weight:700;">Level 3 - Yellow (Urgent)</option>
                            <option value="Green" style="background:#dcfce7; color:#166534; font-weight:700;">Level 4 - Green (Less Urgent / Standard)</option>
                            <option value="Blue" style="background:#dbeafe; color:#1e40af; font-weight:700;">Level 5 - Blue (Non-urgent)</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Emergency Bay / Bed</label>
                        <input type="text" name="bay_number" class="ipd-form-control" placeholder="Bay 1, Trauma Bed A">
                    </div>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Chief Complaint <span class="text-danger">*</span></label>
                    <textarea name="chief_complaints" class="ipd-form-control" rows="2" placeholder="Chest pain radiating to left arm, acute breathlessness..." required></textarea>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">BP Systolic</label>
                        <input type="number" name="bp_systolic" class="ipd-form-control" placeholder="120">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">BP Diastolic</label>
                        <input type="number" name="bp_diastolic" class="ipd-form-control" placeholder="80">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Pulse (bpm)</label>
                        <input type="number" name="pulse" class="ipd-form-control" placeholder="72">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">SpO2 (%)</label>
                        <input type="number" name="spo2" class="ipd-form-control" placeholder="98">
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-register-triage">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-danger"><i class="fa fa-ambulance"></i> Register & Triage</button>
            </div>
        </form>
    </div>
</div>

<!-- 10. Modal: Convert Emergency Triage to IPD Admission -->
<div id="modal-triage-convert" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-bed text-primary"></i> Admit Emergency Patient to IPD</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-triage-convert">&times;</button>
        </div>
        <form data-ipd-action="convert_triage_to_admission">
            <div class="ipd-modal-body">
                <input type="hidden" name="triage_id" id="convert-triage-id">
                <p>Emergency Case: <strong id="convert-triage-label" class="text-danger">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Select Inpatient Bed <span class="text-danger">*</span></label>
                    <select name="bed_id" class="ipd-form-control" required>
                        <option value="">-- Select Available Inpatient Bed --</option>
                        <?php foreach ($availBeds as $ab): ?>
                            <option value="<?php echo (int)$ab['id']; ?>">
                                <?php echo htmlspecialchars($ab['ward_name']); ?> - <?php echo htmlspecialchars($ab['bed_number']); ?> (<?php echo htmlspecialchars($ab['bed_category']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Assign Inpatient Attending Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_id" class="ipd-form-control" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctorsList as $doc): ?>
                            <option value="<?php echo (int)$doc['id']; ?>">
                                Dr. <?php echo htmlspecialchars($doc['fname'] . ' ' . $doc['lname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Admission Notes / Provisional Diagnosis</label>
                    <textarea name="provisional_diagnosis" class="ipd-form-control" rows="2" placeholder="Admitted from Emergency Triage..."></textarea>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-triage-convert">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-check"></i> Confirm IPD Admission</button>
            </div>
        </form>
    </div>
</div>

<!-- 11. Modal: Issue Attendant Pass -->
<div id="modal-issue-pass" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-id-badge text-primary"></i> Issue Inpatient Attendant / Visitor Pass</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-issue-pass">&times;</button>
        </div>
        <form data-ipd-action="issue_pass">
            <div class="ipd-modal-body">
                <input type="hidden" name="admission_id" id="pass-admission-id">
                <p>Patient: <strong id="pass-patient-label" class="text-primary">-</strong></p>

                <div class="ipd-form-group">
                    <label class="ipd-form-label">Attendant Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="attendant_name" class="ipd-form-control" placeholder="Visitor full name" required>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Relationship</label>
                        <input type="text" name="relationship" class="ipd-form-control" value="Family">
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Phone Contact</label>
                        <input type="text" name="phone" class="ipd-form-control">
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Dietary / Meal Plan</label>
                        <select name="meal_plan" class="ipd-form-control">
                            <option value="Normal">Normal Hospital Diet</option>
                            <option value="Diabetic">Diabetic Diet</option>
                            <option value="Cardiac">Cardiac / Low Sodium</option>
                            <option value="Renal">Renal Diet</option>
                            <option value="Liquid/Soft">Liquid / Soft Diet</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Pass Valid Until</label>
                        <input type="datetime-local" name="valid_to" class="ipd-form-control" value="<?php echo date('Y-m-d\TH:i', strtotime('+3 days')); ?>">
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-issue-pass">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-print"></i> Issue Badge Pass</button>
            </div>
        </form>
    </div>
</div>

<!-- 12. Modal: Add Ward -->
<div id="modal-add-ward" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-plus text-primary"></i> Add Hospital Ward</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-add-ward">&times;</button>
        </div>
        <form data-ipd-action="save_ward">
            <div class="ipd-modal-body">
                <div class="ipd-form-group">
                    <label class="ipd-form-label">Ward Name <span class="text-danger">*</span></label>
                    <input type="text" name="ward_name" class="ipd-form-control" placeholder="e.g. Pediatric Ward, Orthopedic Ward" required>
                </div>
                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Ward Code <span class="text-danger">*</span></label>
                        <input type="text" name="ward_code" class="ipd-form-control" placeholder="e.g. W-PED, W-ORTH" required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Ward Type</label>
                        <select name="ward_type" class="ipd-form-control">
                            <option value="General">General</option>
                            <option value="Semi-Private">Semi-Private</option>
                            <option value="Private">Private</option>
                            <option value="ICU">ICU</option>
                            <option value="NICU">NICU</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Isolation">Isolation</option>
                        </select>
                    </div>
                </div>
                <div class="ipd-form-group">
                    <label class="ipd-form-label">Department</label>
                    <input type="text" name="department" class="ipd-form-control" value="General Medicine">
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-add-ward">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-primary"><i class="fa fa-save"></i> Save Ward</button>
            </div>
        </form>
    </div>
</div>

<!-- 13. Modal: Add Bed -->
<div id="modal-add-bed" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-bed text-success"></i> Add New Hospital Bed</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-add-bed">&times;</button>
        </div>
        <form data-ipd-action="save_bed">
            <div class="ipd-modal-body">
                <div class="ipd-form-group">
                    <label class="ipd-form-label">Assign to Ward <span class="text-danger">*</span></label>
                    <select name="ward_id" class="ipd-form-control" required>
                        <option value="">-- Select Ward --</option>
                        <?php foreach ($wardsList as $w): ?>
                            <option value="<?php echo (int)$w['id']; ?>">
                                <?php echo htmlspecialchars($w['ward_name']); ?> (<?php echo htmlspecialchars($w['ward_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Bed Number / Code <span class="text-danger">*</span></label>
                        <input type="text" name="bed_number" class="ipd-form-control" placeholder="e.g. BED-101, ICU-05" required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Bed Category</label>
                        <select name="bed_category" class="ipd-form-control">
                            <option value="Standard">Standard</option>
                            <option value="Semi-Private">Semi-Private</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="ICU">ICU</option>
                            <option value="Ventilator">Ventilator</option>
                            <option value="Isolation">Isolation</option>
                        </select>
                    </div>
                </div>
                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Daily Tariff Rate ($)</label>
                        <input type="number" step="0.01" name="daily_charge" class="ipd-form-control" value="120.00" required>
                    </div>
                    <div class="ipd-form-group" style="display:flex; align-items:center; padding-top:28px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="is_isolation" value="1">
                            <span>Isolation Bed</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-add-bed">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-success"><i class="fa fa-save"></i> Save Bed</button>
            </div>
        </form>
    </div>
</div>

<!-- 14. Modal: Record Payment -->
<div id="modal-record-payment" class="ipd-modal-backdrop">
    <div class="ipd-modal-dialog">
        <div class="ipd-modal-header">
            <h3><i class="fa fa-hand-holding-usd text-success"></i> Record Inpatient Deposit / Payment</h3>
            <button type="button" class="ipd-btn ipd-btn-outline ipd-btn-sm" data-close-modal="modal-record-payment">&times;</button>
        </div>
        <form data-ipd-action="record_payment">
            <div class="ipd-modal-body">
                <div class="ipd-form-group">
                    <label class="ipd-form-label">Inpatient Admission <span class="text-danger">*</span></label>
                    <select name="admission_id" class="ipd-form-control" required>
                        <option value="">-- Select Inpatient --</option>
                        <?php 
                        $activeAdms = (new \OpenEMR\Modules\Ipd\Controller\AdmissionController())->listAdmissions('active');
                        foreach ($activeAdms as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>">
                                <?php echo htmlspecialchars($a['fname'] . ' ' . $a['lname']); ?> (<?php echo htmlspecialchars($a['ipd_number']); ?>) - Bed <?php echo htmlspecialchars($a['bed_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Payment Amount ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="ipd-form-control" placeholder="0.00" required>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Payment Type</label>
                        <select name="payment_type" class="ipd-form-control">
                            <option value="advance">Advance Deposit</option>
                            <option value="interim_payment">Interim Payment</option>
                            <option value="final_payment">Final Bill Settlement</option>
                        </select>
                    </div>
                </div>

                <div class="ipd-form-row">
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Payment Method</label>
                        <select name="payment_method" class="ipd-form-control">
                            <option value="cash">Cash</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="insurance_tpa">Insurance / TPA Cashless</option>
                            <option value="bank_transfer">Bank Wire / Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online Payment</option>
                        </select>
                    </div>
                    <div class="ipd-form-group">
                        <label class="ipd-form-label">Transaction / Cheque Reference</label>
                        <input type="text" name="transaction_reference" class="ipd-form-control" placeholder="Receipt / Approval code">
                    </div>
                </div>
            </div>
            <div class="ipd-modal-footer">
                <button type="button" class="ipd-btn ipd-btn-outline" data-close-modal="modal-record-payment">Cancel</button>
                <button type="submit" class="ipd-btn ipd-btn-success"><i class="fa fa-receipt"></i> Print & Save Receipt</button>
            </div>
        </form>
    </div>
</div>
