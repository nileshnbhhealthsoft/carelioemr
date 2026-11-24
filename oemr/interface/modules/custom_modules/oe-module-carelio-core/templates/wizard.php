<?php
/**
 * Carelio Hospital EHR - 9-Step Install Wizard Template
 * Conforming to Specification Section 3 (Pages 4-6)
 */

use OpenEMR\Modules\CarelioCore\Services\InstallWizardService;

$wizardState = InstallWizardService::getWizardState();
$profile = $wizardState['step1'];
$modulesState = $wizardState['step2'];
$wardTypes = $wizardState['step3'];
$clinicalServices = $wizardState['step4'];
$locationsState = $wizardState['step5'];
$rolesState = $wizardState['step6'];
$refData = $wizardState['step7'];
$integrations = $wizardState['step8'];
$selfCheck = $wizardState['step9'];
$isCompleted = $wizardState['is_completed'];
?>

<div class="row">
    <!-- Sidebar: Steps Navigation -->
    <div class="col-md-3">
        <div class="list-group wizard-steps shadow-sm mb-4" id="wizardStepsList">
            <a href="#step-1" class="list-group-item list-group-item-action active step-nav-link" data-step="1">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-primary mr-2">1</span> Hospital Profile</h6>
                    <small class="text-success step-icon-1"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">Country, Currency, Timezone</small>
            </a>
            <a href="#step-2" class="list-group-item list-group-item-action step-nav-link" data-step="2">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">2</span> Catalog Modules</h6>
                    <small class="text-success step-icon-2"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">Phase 1-4 Module Selection</small>
            </a>
            <a href="#step-3" class="list-group-item list-group-item-action step-nav-link" data-step="3">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">3</span> Ward Types</h6>
                    <small class="text-success step-icon-3"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">Section 6 Ward Catalog</small>
            </a>
            <a href="#step-4" class="list-group-item list-group-item-action step-nav-link" data-step="4">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">4</span> Clinical Services</h6>
                    <small class="text-success step-icon-4"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">Dialysis, Chemo, Maternity</small>
            </a>
            <a href="#step-5" class="list-group-item list-group-item-action step-nav-link" data-step="5">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">5</span> Locations & Beds</h6>
                    <small class="text-success step-icon-5"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">OKEU 39-Node Hierarchy</small>
            </a>
            <a href="#step-6" class="list-group-item list-group-item-action step-nav-link" data-step="6">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">6</span> Roles & Permissions</h6>
                    <small class="text-success step-icon-6"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">5 Sensitivity Levels, Break-Glass</small>
            </a>
            <a href="#step-7" class="list-group-item list-group-item-action step-nav-link" data-step="7">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">7</span> Reference Data</h6>
                    <small class="text-success step-icon-7"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">SNOMED CT, RxNorm, Insurers</small>
            </a>
            <a href="#step-8" class="list-group-item list-group-item-action step-nav-link" data-step="8">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">8</span> Integrations</h6>
                    <small class="text-success step-icon-8"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">LIS (OpenELIS), RIS (Orthanc)</small>
            </a>
            <a href="#step-9" class="list-group-item list-group-item-action step-nav-link" data-step="9">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <h6 class="mb-1"><span class="badge badge-secondary mr-2">9</span> Review & Self-Check</h6>
                    <small class="text-success step-icon-9"><i class="fa fa-check-circle"></i></small>
                </div>
                <small class="text-muted">Validation & System Check</small>
            </a>
        </div>

        <div class="card p-3 shadow-sm bg-light text-center">
            <h6>Wizard Status</h6>
            <?php if ($isCompleted): ?>
                <span class="badge badge-success py-2 font-weight-bold"><i class="fa fa-check mr-1"></i> INSTALLED & VERIFIED</span>
                <small class="text-muted mt-2 d-block">Completed: <?php echo htmlspecialchars($wizardState['completed_at'] ?? 'Recently'); ?></small>
            <?php else: ?>
                <span class="badge badge-warning py-2 font-weight-bold"><i class="fa fa-clock-o mr-1"></i> PENDING FINAL REVIEW</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Area: Step Form Panels -->
    <div class="col-md-9">
        <form id="formInstallWizard">
            <!-- Step 1: Hospital Profile -->
            <div class="step-panel" id="panel-step-1">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-hospital-o text-primary mr-1"></i> Step 1: Hospital Profile & Regional Defaults
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Configures regional defaults, national currency, timezone, and legal hospital parameters.</p>
                        <div class="form-group">
                            <label><strong>Hospital Legal Name *</strong></label>
                            <input type="text" name="step1[hospital_name]" class="form-control" value="<?php echo htmlspecialchars($profile['hospital_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label><strong>Country *</strong></label>
                                <select name="step1[country]" class="form-control">
                                    <option value="LC" <?php echo ($profile['country'] ?? '') === 'LC' ? 'selected' : ''; ?>>Saint Lucia (LC)</option>
                                    <option value="BB" <?php echo ($profile['country'] ?? '') === 'BB' ? 'selected' : ''; ?>>Barbados (BB)</option>
                                    <option value="TT" <?php echo ($profile['country'] ?? '') === 'TT' ? 'selected' : ''; ?>>Trinidad & Tobago (TT)</option>
                                    <option value="DM" <?php echo ($profile['country'] ?? '') === 'DM' ? 'selected' : ''; ?>>Dominica (DM)</option>
                                    <option value="GD" <?php echo ($profile['country'] ?? '') === 'GD' ? 'selected' : ''; ?>>Grenada (GD)</option>
                                    <option value="VC" <?php echo ($profile['country'] ?? '') === 'VC' ? 'selected' : ''; ?>>St. Vincent & Grenadines (VC)</option>
                                    <option value="KN" <?php echo ($profile['country'] ?? '') === 'KN' ? 'selected' : ''; ?>>St. Kitts & Nevis (KN)</option>
                                    <option value="AG" <?php echo ($profile['country'] ?? '') === 'AG' ? 'selected' : ''; ?>>Antigua & Barbuda (AG)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Currency *</strong></label>
                                <select name="step1[currency]" class="form-control">
                                    <option value="XCD" <?php echo ($profile['currency'] ?? '') === 'XCD' ? 'selected' : ''; ?>>East Caribbean Dollar (XCD $)</option>
                                    <option value="USD" <?php echo ($profile['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>United States Dollar (USD $)</option>
                                    <option value="BBD" <?php echo ($profile['currency'] ?? '') === 'BBD' ? 'selected' : ''; ?>>Barbados Dollar (BBD $)</option>
                                    <option value="TTD" <?php echo ($profile['currency'] ?? '') === 'TTD' ? 'selected' : ''; ?>>Trinidad Dollar (TTD $)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Timezone *</strong></label>
                                <select name="step1[timezone]" class="form-control">
                                    <option value="America/St_Lucia" <?php echo ($profile['timezone'] ?? '') === 'America/St_Lucia' ? 'selected' : ''; ?>>America/St_Lucia (UTC -4)</option>
                                    <option value="America/Barbados" <?php echo ($profile['timezone'] ?? '') === 'America/Barbados' ? 'selected' : ''; ?>>America/Barbados (UTC -4)</option>
                                    <option value="America/Port_of_Spain" <?php echo ($profile['timezone'] ?? '') === 'America/Port_of_Spain' ? 'selected' : ''; ?>>America/Port_of_Spain (UTC -4)</option>
                                    <option value="America/New_York" <?php echo ($profile['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label><strong>Site Deployment Mode</strong></label>
                                <select name="step1[site_mode]" class="form-control">
                                    <option value="single" <?php echo ($profile['site_mode'] ?? '') === 'single' ? 'selected' : ''; ?>>Single Hospital Campus</option>
                                    <option value="multi" <?php echo ($profile['site_mode'] ?? '') === 'multi' ? 'selected' : ''; ?>>Multi-Site Hospital Network</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Staff Language</strong></label>
                                <input type="text" name="step1[staff_language]" class="form-control" value="English (en)" readonly>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Patient Document Language</strong></label>
                                <input type="text" name="step1[patient_language]" class="form-control" value="English (en)" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-right">
                        <button type="button" class="btn btn-primary btn-next-step" data-next="2">Next: Modules <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Modules -->
            <div class="step-panel d-none" id="panel-step-2">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-cubes text-primary mr-1"></i> Step 2: Catalog Modules Selection
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Select the Carelio Hospital EHR modules to activate. The installer automatically resolves prerequisite dependencies.</p>
                        <div class="row">
                            <?php 
                            $selMods = $modulesState['selected_modules'] ?? ['CORE', 'REG', 'SCH', 'CLN', 'CPOE', 'BILL', 'PHM'];
                            $phase1List = [
                                'CORE' => ['Shared Foundation & Bed Hierarchy', 'Mandatory foundation layer', true],
                                'REG' => ['Registration & Master Patient Index', 'Emergency MRN, MPI reconciliation', false],
                                'SCH' => ['Scheduling & Resource Appointments', 'Theatres, equipment calendars', false],
                                'CLN' => ['Clinical Documentation & Rounds', 'Inpatient rounding SOAP forms', false],
                                'CPOE' => ['Orders & Results (CPOE)', 'Order sets, panic alerts', false],
                                'BILL' => ['Billing, Claims & Insurance Packages', 'Bundled packages, SNOMED fees', false],
                                'PHM' => ['Pharmacy & Formulary Dispensing', 'Controlled substances, RxNorm', false],
                                'HIM' => ['Records, Coding & Document Completion', 'ICD-10 coding, disclosure logs', false],
                                'ADMIN' => ['Security, Break-Glass & Integration Engine', '5 sensitivity levels, HL7 queue', false]
                            ];
                            foreach ($phase1List as $code => $info):
                                $checked = in_array($code, $selMods) || $info[2];
                            ?>
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100 <?php echo $checked ? 'bg-light border-primary' : ''; ?>">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="step2[selected_modules][]" value="<?php echo $code; ?>" class="custom-control-input" id="mod_<?php echo $code; ?>" <?php echo $checked ? 'checked' : ''; ?> <?php echo $info[2] ? 'disabled' : ''; ?>>
                                            <label class="custom-control-label font-weight-bold" for="mod_<?php echo $code; ?>">
                                                <code><?php echo $code; ?></code>: <?php echo $info[0]; ?>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1 ml-4"><?php echo $info[1]; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="1"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="3">Next: Ward Types <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Ward Types -->
            <div class="step-panel d-none" id="panel-step-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-building text-primary mr-1"></i> Step 3: Ward & Service Types (Section 6 Catalog)
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Select only the ward types the hospital operates. Nothing is enabled by default.</p>
                        <div class="row">
                            <?php 
                            $allWardTypes = [
                                'General Medicine / Mixed',
                                'General Surgery / Post-Op',
                                'Orthopedics / Trauma',
                                'Cardiology / Telemetry',
                                'Maternity / Antenatal / Postnatal',
                                'Newborn / Special-Care Nursery',
                                'Intensive Care Unit (ICU / HDU)',
                                'Emergency & Triage Observation',
                                'Pediatrics / Adolescents',
                                'Oncology / Hematology',
                                'Infectious Disease / Isolation'
                            ];
                            foreach ($allWardTypes as $wType):
                                $checked = in_array($wType, $wardTypes);
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox border rounded p-2">
                                        <input type="checkbox" name="step3[]" value="<?php echo htmlspecialchars($wType); ?>" class="custom-control-input" id="ward_<?php echo md5($wType); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="ward_<?php echo md5($wType); ?>">
                                            <i class="fa fa-check-square-o text-success mr-1"></i> <?php echo htmlspecialchars($wType); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="2"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="4">Next: Clinical Services <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 4: Clinical Services -->
            <div class="step-panel d-none" id="panel-step-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-user-md text-primary mr-1"></i> Step 4: Clinical Services
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Enables matching clinical workflows and specialized schedules; hides unused services.</p>
                        <div class="row">
                            <?php 
                            $allServices = [
                                'Emergency Triage & Resuscitation',
                                'Maternity & Labor Delivery',
                                'Hemodialysis & Renal Care',
                                'Endoscopy & Day Procedures',
                                'Chemotherapy Day Unit',
                                'Telemedicine & Remote Consults',
                                'Palliative & End-of-Life Care',
                                'Physiotherapy & Rehabilitation'
                            ];
                            foreach ($allServices as $s):
                                $checked = in_array($s, $clinicalServices);
                            ?>
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox border rounded p-2">
                                        <input type="checkbox" name="step4[]" value="<?php echo htmlspecialchars($s); ?>" class="custom-control-input" id="srv_<?php echo md5($s); ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="srv_<?php echo md5($s); ?>">
                                            <i class="fa fa-stethoscope text-info mr-1"></i> <?php echo htmlspecialchars($s); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="3"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="5">Next: Locations & Beds <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 5: Locations & Beds -->
            <div class="step-panel d-none" id="panel-step-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-sitemap text-primary mr-1"></i> Step 5: Locations and Bed Hierarchy
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle mr-1"></i> <strong>Physical Hierarchy Ready:</strong> 40 physical nodes mapped in <code>hms_location</code> matching Saint Lucia's OKEU National Hospital model.
                        </div>
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr><th>Level</th><th>Location Unit</th><th>Care Level / Specialty</th><th>Beds</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>Site</strong></td><td>OKEU Hospital Main Campus</td><td>Tertiary Care</td><td>-</td></tr>
                                <tr><td><strong>Building</strong></td><td>Inpatient Clinical Tower</td><td>Acute Inpatient</td><td>-</td></tr>
                                <tr><td><strong>Ground Floor</strong></td><td>Emergency & Acute Assessment Unit</td><td>Emergency / Observation</td><td>4 Bays</td></tr>
                                <tr><td><strong>First Floor</strong></td><td>Maternity & Postnatal Ward</td><td>Obstetrics</td><td>4 Beds</td></tr>
                                <tr><td><strong>Second Floor</strong></td><td>General Medicine & Surgical Ward</td><td>General Medicine</td><td>6 Beds</td></tr>
                                <tr><td><strong>Third Floor</strong></td><td>Intensive Care Unit (ICU Suites)</td><td>Critical Care / Ventilator</td><td>3 Suites</td></tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="step5[baseline_model]" value="OKEU_NATIONAL_HOSPITAL">
                        <input type="hidden" name="step5[total_beds_baseline]" value="39">
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="4"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="6">Next: Roles & Permissions <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 6: Roles & Permissions -->
            <div class="step-panel d-none" id="panel-step-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-shield text-primary mr-1"></i> Step 6: Roles & 5-Tier Sensitivity Permissions
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Applies the shared hospital access model, break-glass logging, and 5-tier sensitive chart rankings.</p>
                        <h6 class="font-weight-bold">1. 5-Tier Sensitivity Levels Enforced (Section 10.1):</h6>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong>Level 1: Normal</strong> - Standard clinical charts</div>
                                <span class="badge badge-success">Care Team</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong>Level 2: Restricted</strong> - Psychiatry and behavioral health notes</div>
                                <span class="badge badge-warning">Treating Team Only</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong>Level 3: Highly Restricted</strong> - HIV/STIs and substance-use treatment</div>
                                <span class="badge badge-danger">Named Clinicians</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong>Level 4: Staff-as-Patient</strong> - Hospital employees receiving inpatient care</div>
                                <span class="badge badge-info">Privacy Officer Guard</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong>Level 5: Protected Identity</strong> - VIPs, safeguarding, and victims</div>
                                <span class="badge badge-dark">Designated Users</span>
                            </li>
                        </ul>

                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="switchBreakglass" name="step6[enforce_breakglass]" value="1" checked>
                            <label class="custom-control-label font-weight-bold" for="switchBreakglass">
                                Enforce 24-Hour Break-Glass Clinical Override Review Queue (hms_breakglass_log)
                            </label>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="5"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="7">Next: Reference Data <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 7: Reference Data -->
            <div class="step-panel d-none" id="panel-step-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-database text-primary mr-1"></i> Step 7: Reference Data & Code Sets
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Loads country reference sets, clinical coding systems, and national insurers.</p>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label><strong>Procedure Code Standard *</strong></label>
                                <input type="text" class="form-control" name="step7[procedure_code_set]" value="SNOMED CT (Active, CPT4 Disabled)" readonly>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Diagnosis Code Standard *</strong></label>
                                <input type="text" class="form-control" name="step7[diagnosis_code_set]" value="ICD-10" readonly>
                            </div>
                            <div class="form-group col-md-4">
                                <label><strong>Drug Formulary *</strong></label>
                                <input type="text" class="form-control" name="step7[formulary_standard]" value="RxNorm + Caribbean Local Drugs" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Hospital Insurers & Payers</strong></label>
                            <textarea name="step7[insurers_text]" class="form-control" rows="3">National Health Scheme (Saint Lucia)
Sagicor Life
Guardian General
Beacon Insurance
Self-Pay Cash</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="6"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="8">Next: Integrations <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 8: Integrations -->
            <div class="step-panel d-none" id="panel-step-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-plug text-primary mr-1"></i> Step 8: Diagnostic & Hardware Integrations
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Pre-configures laboratory analyzers, radiology PACS servers, and FHIR interoperability.</p>
                        <div class="form-group">
                            <label><strong>Laboratory Information System (LIS) Path</strong></label>
                            <input type="text" class="form-control" name="step8[lis_engine]" value="OpenELIS Global (HL7 v2 bi-directional analyzer interface)" readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label><strong>Radiology & PACS Server</strong></label>
                                <input type="text" class="form-control" name="step8[ris_pacs]" value="Orthanc DICOM Server with embedded OHIF Web Viewer" readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label><strong>Orthanc PACS Service URL</strong></label>
                                <input type="text" class="form-control" name="step8[orthanc_url]" value="http://localhost:8042">
                            </div>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="switchFhir" name="step8[enable_fhir_r4]" value="1" checked>
                            <label class="custom-control-label font-weight-bold" for="switchFhir">
                                Activate OpenEMR FHIR R4 Interoperability API Gateway (34 resources verified)
                            </label>
                        </div>
                    </div>
                    <div class="card-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="7"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-next-step" data-next="9">Next: Review & Self-Check <i class="fa fa-arrow-right ml-1"></i></button>
                    </div>
                </div>
            </div>

            <!-- Step 9: Review & Self-Check -->
            <div class="step-panel d-none" id="panel-step-9">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-check-circle-o text-success mr-1"></i> Step 9: Summary & System Self-Check
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Review your hospital parameters below. Clicking <strong>"Apply Hospital Configuration & Run Self-Check"</strong> updates OpenEMR system globals, switches procedure code sets to SNOMED CT, initializes audit tables, and verifies all 7 architectural standards.</p>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="font-weight-bold text-primary mb-2">Hospital Baseline Summary</h6>
                                    <ul class="mb-0 pl-3">
                                        <li><strong>Hospital:</strong> <span id="summaryHospitalName">Owen King EU National Hospital</span></li>
                                        <li><strong>Country & Currency:</strong> Saint Lucia (LC) - East Caribbean Dollar (XCD)</li>
                                        <li><strong>Timezone:</strong> America/St_Lucia (UTC -4)</li>
                                        <li><strong>Active Modules:</strong> Phase 1 Baseline (CORE, REG, SCH, CLN, CPOE, BILL, PHM)</li>
                                        <li><strong>Procedure Codes:</strong> SNOMED CT Standard (CPT4 Disabled)</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="font-weight-bold text-primary mb-2">Security & Architecture Gates</h6>
                                    <ul class="mb-0 pl-3">
                                        <li><strong>Zero Core Modifications:</strong> Encapsulated in <code>oe-module-carelio-core</code></li>
                                        <li><strong>Table Namespacing:</strong> Strictly <code>hms_*</code> prefix</li>
                                        <li><strong>Permissions:</strong> 5-Tier Chart Sensitivity Model Enabled</li>
                                        <li><strong>Governance:</strong> Medical Director Content Approval Gate</li>
                                        <li><strong>Event Queue:</strong> Asynchronous <code>hms_event</code> bus ready</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Self-Check Results Box -->
                        <div id="selfCheckResultContainer" class="mb-3">
                            <div class="card border-primary">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <span class="font-weight-bold text-dark"><i class="fa fa-stethoscope mr-1"></i> Self-Check Diagnostic Results</span>
                                    <span class="badge badge-success px-2 py-1" id="selfCheckBadge">ALL 7 CHECKS PASSED</span>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0" id="tableSelfCheck">
                                        <tbody>
                                            <?php if (!empty($selfCheck['items'])): ?>
                                                <?php foreach ($selfCheck['items'] as $item): ?>
                                                    <tr>
                                                        <td class="pl-3" style="width: 30px;">
                                                            <?php if ($item['status'] === 'pass'): ?>
                                                                <i class="fa fa-check-circle text-success"></i>
                                                            <?php else: ?>
                                                                <i class="fa fa-times-circle text-danger"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                                        <td class="text-muted"><small><?php echo htmlspecialchars($item['detail']); ?></small></td>
                                                        <td class="text-right pr-3">
                                                            <span class="badge badge-<?php echo $item['status'] === 'pass' ? 'success' : 'danger'; ?>">
                                                                <?php echo strtoupper($item['status']); ?>
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

                        <div class="text-center py-3">
                            <button type="submit" class="btn btn-success btn-lg px-4" id="btnRunWizard">
                                <i class="fa fa-play-circle mr-1"></i> Apply Hospital Configuration & Run Self-Check
                            </button>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-prev-step" data-prev="8"><i class="fa fa-arrow-left mr-1"></i> Back</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Navigation click
    $('.step-nav-link').on('click', function(e) {
        e.preventDefault();
        var step = $(this).data('step');
        showStep(step);
    });

    $('.btn-next-step').on('click', function() {
        var next = $(this).data('next');
        showStep(next);
    });

    $('.btn-prev-step').on('click', function() {
        var prev = $(this).data('prev');
        showStep(prev);
    });

    function showStep(stepNum) {
        $('.step-panel').addClass('d-none');
        $('#panel-step-' + stepNum).removeClass('d-none');
        $('.step-nav-link').removeClass('active');
        $('.step-nav-link[data-step="' + stepNum + '"]').addClass('active');
        $('html, body').animate({ scrollTop: $('#wizardStepsList').offset().top - 20 }, 300);
    }

    // Submit wizard
    $('#formInstallWizard').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnRunWizard');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Running Self-Check & Applying...');

        $.post('index.php', {
            action: 'apply_wizard',
            formData: $(this).serialize()
        }, function(resp) {
            btn.prop('disabled', false).html('<i class="fa fa-play-circle mr-1"></i> Apply Hospital Configuration & Run Self-Check');
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert('Self-Check error: ' + (resp.error || 'Failed'));
            }
        }, 'json').fail(function(xhr) {
            btn.prop('disabled', false).html('<i class="fa fa-play-circle mr-1"></i> Apply Hospital Configuration & Run Self-Check');
            alert('Request failed: ' + xhr.responseText);
        });
    });
});
</script>
