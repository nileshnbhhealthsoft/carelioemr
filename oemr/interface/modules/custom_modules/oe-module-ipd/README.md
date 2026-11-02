# OpenEMR Custom Module: Inpatient & Hospital Management (`oe-module-ipd`)

## 1. Overview
The **Inpatient (IPD) & Hospital Management Module** (`oe-module-ipd`) extends OpenEMR into a comprehensive enterprise Hospital Information System (HIS/HMS). It manages the full inpatient lifecycle from emergency triage and admission, bed matrix and ward allocation, daily doctor progress rounds (SOAP), nursing care and Medication Administration Records (MAR), Operation Theatre (OT) surgery scheduling, inpatient billing and predefined packages, to multi-disciplinary discharge clearances and bed release.

Built following OpenEMR's **Zero Core Modification Principles** as an independent custom module modeled after `oe-module-faxsms`.

---

## 2. Comprehensive Feature Matrix

### 1. Inpatient Management (IPD)
- **Patient Admission & Registration:** Reuses existing OpenEMR patient records (`patient_data`) with instant autocomplete lookup by name, PID, or phone.
- **Unique Sequence Generator:** Auto-generates standard IPD numbers (e.g., `IPD-2026-00001`).
- **Admission Categorization:** Elective, Emergency, Referral, or Transfer.
- **Doctor Assignment:** Attending consultant and admitting doctor assigned from OpenEMR's authorized practitioners (`users`).
- **Attendant & Emergency Contact:** Full recording of attendant name, relationship, and contact numbers.
- **Inpatient Lifecycle Tracking:** Statuses for `admitted`, `transferred`, `discharge_requested`, `discharged`, `dama`, and `deceased`.

### 2. Bed and Ward Management
- **Hierarchy:** Hospital Buildings &rarr; Floors &rarr; Wards &rarr; Rooms &rarr; Beds.
- **Ward Categories:** General Ward, Semi-Private, Private, ICU, NICU, Emergency Bay, Post-Op, and Isolation units.
- **Interactive Visual Bed Matrix:** Real-time color-coded bed status cards:
  - `Available` (Emerald / Green)
  - `Occupied` (Blue / Indigo, showing patient name, IPD number, days admitted, doctor)
  - `Cleaning` (Cyan / Light Blue)
  - `Maintenance` (Slate / Gray)
  - `Reserved` (Amber / Yellow)
  - `Blocked` (Crimson / Red)
- **Bed Transfers:** Automated transfer workflow with allocation history and audit logging.

### 3. Doctor and Clinical Management
- **Daily Inpatient Rounds:** Structured SOAP clinical progress notes (Subjective, Objective, Assessment, Plan).
- **Inpatient Medication Orders:** Prescription of inpatient medications with dosage, route (Oral, IV, IM, SC), frequency (BD, TDS, QID, STAT, PRN), start/end dates, and clinical instructions.
- **Fluid Intake & Output:** 24-hour fluid chart tracking oral/IV fluids vs urine/drain output with net fluid balance calculation.
- **Diet & Activity Orders:** Embedded directly into physician rounds.

### 4. Nursing Management
- **Vital Signs Monitoring Sheet:** Pulse, Blood Pressure (Sys/Dia), Temperature, Respiratory Rate, SpO2 (%), Pain Score (0-10), Blood Sugar, Consciousness Level (AVPU), and Oxygen support.
- **Medication Administration Records (MAR):** Scheduled vs actual administration times, status (`given`, `held`, `refused`, `missed`), dosage, injection site, and nurse remarks.
- **Nursing Care Plans & Shift Handover:** Daily nursing diagnosis, interventions, patient response, and shift handover notes.
- **Clinical Risk Assessments:** Fall-Risk (Morse scale) and Pressure Ulcer Risk (Braden scale).
- **Shift Duty Roster:** Nurse shift scheduling by ward and date.

### 5. Operation Theatre (OT) Management
- **Operating Theatres:** Master list of OR suites and real-time occupancy.
- **Surgery Scheduling:** Procedure name, scheduled start/end times, lead surgeon, assistant surgeon, anesthetist, and anesthesia type.
- **WHO Surgical Safety Checklist:** Sign-In, Time-Out, and Sign-Out documentation.
- **Post-Op Monitoring:** Recovery room documentation and operating notes.

### 6. Hospital Billing and Payments
- **Automated Bed Tariff Calculation:** Auto-calculates bed day charges based on actual length of stay across bed transfers.
- **Tariff Master Catalog:** Configurable rates for bed stay, nursing care, doctor visits, surgery, and procedures.
- **Predefined Hospital Packages:** Fixed-cost packages (e.g. Normal Delivery, Caesarean LSCS, Laparoscopy, Medical Fever Care) with itemized inclusions.
- **Inpatient Invoicing:** Interim and Final invoice generation with itemized line items, discounts, tax, and insurance coverage.
- **Deposits & Payments:** Advance payment recording, interim settlements, receipt generation (`RCPT-2026-00001`), and payment methods (Cash, Card, Insurance/TPA, Bank Wire, Cheque).

### 7. Discharge Management Workflow
- **Multi-disciplinary Clearances:** 4-stage clearance tracking:
  - Doctor Clearance
  - Nursing Clearance
  - Pharmacy Clearance
  - Billing & Cashier Clearance
- **Automated Bed Release:** Finalizing discharge automatically clears bed occupancy and sets the bed status to `cleaning`.
- **Printable Discharge Summary:** Official, hospital-branded document with diagnosis, hospital course, discharge medications, condition, follow-up instructions, and signature blocks.

### 8. Emergency & Casualty Triage
- **Emergency Triage Queue:** Triage category prioritization:
  - Level 1 - Red (Immediate Resuscitation)
  - Level 2 - Orange (Emergent)
  - Level 3 - Yellow (Urgent)
  - Level 4 - Green (Less Urgent)
  - Level 5 - Blue (Non-urgent)
- **1-Click IPD Admission:** Direct conversion of emergency patients into inpatient admissions with automatic bed allocation and triage vitals transfer.

### 9. Patient & Attendant Services
- **Visitor Passes:** Unique pass generation (`PASS-2026-00001`) with validity dates.
- **Dietary Meal Plans:** Regular, Diabetic, Cardiac, Renal, and Liquid/Soft diet tracking.

### 10. Reports & Hospital KPIs
- Real-time bed occupancy rate (%).
- Current inpatients, daily admissions, daily discharges.
- Average Length of Stay (ALOS).
- Ward and department utilization matrix.
- IPD gross billed, collections, and outstanding balance summary.
- Doctor inpatient workload distribution.

---

## 3. Directory & File Structure
```
oemr/interface/modules/custom_modules/oe-module-ipd/
├── info.txt                           # Module Manager description
├── version.php                        # Version metadata (1.0.0)
├── table.sql                          # Full schema DDL & seed definitions
├── ModuleManagerListener.php          # OpenEMR module lifecycle hooks
├── openemr.bootstrap.php              # OpenEMR event listener & menu injector
├── moduleConfig.php                   # Module Manager settings page
├── index.php                          # Inpatient portal application shell
├── sql/
│   ├── install.sql                    # SQL installer script
│   └── upgrade.sql                    # Upgrade SQL script
├── src/
│   ├── Database.php                   # Database PDO abstraction & auto-schema
│   ├── BootstrapService.php           # Sequence generator, ACL & audit logging
│   └── Controller/
│       ├── IpdDispatch.php            # Main API request router
│       ├── AdmissionController.php    # IPD Admissions & bed transfers
│       ├── BedController.php          # Bed board matrix & ward setup
│       ├── ClinicalController.php     # Doctor progress notes & MAR orders
│       ├── NursingController.php      # Vitals, MAR administration & care notes
│       ├── OtController.php           # OT suites & surgery scheduling
│       ├── BillingController.php      # Inpatient billing, tariffs & packages
│       ├── DischargeController.php    # Clearances & discharge summaries
│       ├── EmergencyController.php    # Casualty triage & 1-click IPD conversion
│       ├── AttendantController.php    # Visitor badges & meal plans
│       └── ReportController.php       # Hospital operational KPIs & reports
├── public/
│   ├── css/
│   │   └── ipd.css                    # Modern UI styles & bed status colors
│   └── js/
│       └── ipd.js                     # Tab switching, autocomplete & modals
└── templates/
    ├── dashboard_bed_matrix.php       # Live Bed Matrix tab
    ├── admissions_view.php            # Inpatient Admissions tab
    ├── ward_bed_mgmt.php              # Bed & Ward setup tab
    ├── clinical_rounds.php            # Doctor rounds & MAR orders tab
    ├── nursing_vitals_mar.php         # Nursing vitals & MAR sheet tab
    ├── operation_theatre.php          # OT management tab
    ├── billing_packages.php           # IPD billing & packages tab
    ├── emergency_triage.php           # Emergency triage queue tab
    ├── discharge_mgmt.php             # Discharge workflow tab
    ├── attendant_services.php         # Attendant passes tab
    ├── reports_analytics.php          # Reports & analytics tab
    ├── modals.php                     # Consolidated interactive dialogs
    └── print_discharge_summary.php    # Printable clinical discharge summary
```
