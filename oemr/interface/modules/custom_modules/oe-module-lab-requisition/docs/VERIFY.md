# Check on a live OpenEMR before first real use

The catalog, sanitizer and form rendering were tested offline (`tests/smoke.php`, and a browser render of the print
view). The parts that talk to OpenEMR itself could not be run without an OpenEMR database, so they were written to its
conventions and still need one pass on a test install, with test patients.

## 1. Module loads
- [ ] Register, Install, Enable under Admin > Modules > Manage Modules with no PHP error in the log.
- [ ] "Lab Requisition" appears under the Patient menu and as a tab on the patient dashboard. If the menu entry is
      missing, the top-menu id differs in this version: edit the id list in `src/Bootstrap.php` (`patimg`, `patmen`).
      The module falls back to a top-level item.
- [ ] Tables `labreq_order`, `labreq_order_item`, `labreq_event` exist.

## 2. Column names (`SHOW COLUMNS FROM <table>`)
`src/Chart.php` reads these. Adjust the query if a column is named differently on your version.
- [ ] `patient_data`: fname, lname, DOB, sex, pubpid, street, city, state, postal_code, phone_home, phone_cell, phone_contact
- [ ] `users`: id, fname, lname
- [ ] `form_encounter`: pid, encounter

## 3. Behaviour
- [ ] New requisition pre-fills name, D.O.B. (Day/Month/Year), sex, reg. no. (the patient's public ID), address, contact, doctor, date.
- [ ] Tick tests, add text on the "state" lines, Save draft, reopen from the list: everything is as left. Untick a box, save: it stays unticked.
- [ ] Finalize with nothing ticked is refused with a message, and the draft is kept.
- [ ] Finalize: the print view opens, "Signed" shows your name if it was blank, Edit is no longer offered, and a direct
      `order.php?id=` for that order redirects to the print view.
- [ ] POSTing to `save.php` for a finalized order does not change it.
- [ ] Cancel: the order is greyed, still listed, not editable.

## 4. Security
- [ ] A user without patient access cannot open any module page (403).
- [ ] Opening `print.php?id=<another patient's order>` from a different patient's chart gives "Requisition not found".
- [ ] A POST without a valid CSRF token is rejected.

## 5. Print test
- [ ] Print to A4 at 100% from Chrome and from Safari: one page, nothing clipped, the lab-use box at the bottom.
- [ ] Ticked boxes show a tick on paper (enable "Background graphics" if your browser hides them).
