# Check on a live OpenEMR before first real use

The catalog and sanitizer were tested offline (`tests/smoke.php`), and the study builder was rendered in a browser.
The parts that talk to OpenEMR itself could not be run without an OpenEMR database, so they were written to its
conventions and still need one pass on a test install, with test patients.

## 1. Module loads
- [ ] Register, Install, Enable under Admin > Modules > Manage Modules with no PHP error in the log.
- [ ] "Radiology Requisition" appears under the Patient menu and as a dashboard tab. If the menu entry is missing,
      edit the id list in `src/Bootstrap.php` (`patimg`, `patmen`); the module falls back to a top-level item.
- [ ] Tables `radreq_order`, `radreq_study`, `radreq_event` exist.

## 2. Column names (`SHOW COLUMNS FROM <table>`)
`src/Chart.php` reads these. Adjust the query if a column is named differently on your version.
- [ ] `patient_data`: fname, lname, DOB, sex, pubpid, phone_home, phone_cell, phone_contact
- [ ] `users`: id, fname, lname, npi, phonew1, facility_id
- [ ] `facility`: id, name
- [ ] `form_encounter`: pid, encounter, facility_id
- [ ] `lists`: pid, type ('allergy'), title, enddate

## 3. Behaviour
- [ ] Pre-fill is right for three test patients (with allergies, without, no recorded sex).
- [ ] Changing a study's modality swaps the examination list and the safety questions, and sets the contrast default
      (CT/MRI/Fluoroscopy/Nuclear Medicine: Radiologist to determine; others: Not applicable).
- [ ] Add and remove studies; save; reopen: studies and safety answers are as left.
- [ ] Finalize with a required field missing, or a study with no examination, is refused with reasons and the draft is kept.
- [ ] Finalize with Unknown safety answers is refused until the confirmation box is ticked; the print view then shows
      a warning with the count.
- [ ] After Finalize the order is read-only, shows the finalizing user and time, and `order.php?id=` redirects to the print view.

## 4. Security
- [ ] A user without patient access cannot open any module page (403).
- [ ] Opening `print.php?id=<another patient's order>` from a different patient's chart gives "Requisition not found".
- [ ] A POST without a valid CSRF token is rejected.
- [ ] A study's instructions containing `<script>` is shown as text, never run.

## 5. Print test
- [ ] Print to A4 from Chrome and Safari: studies do not split across pages awkwardly, the notice and buttons are hidden.
