# Check on a live OpenEMR before first real use

The renderer, validator and sanitizer were tested offline against the real NAGICO PDF. The parts that talk to
OpenEMR itself could not be run without an OpenEMR database, so they were written to its conventions and still
need one pass on a test install. Do this on a test site first, with test patients.

## 1. Module loads
- [ ] Register, Install, Enable under Admin > Modules > Manage Modules with no PHP error in the log.
- [ ] A menu entry "Insurance Claim Forms" appears under the Patient menu. If it does not, the menu id differs in
      this version: edit the id list in `src/Bootstrap.php` (`patimg`, `patmen`). The module falls back to a top-level item.
- [ ] Tables `claimforms_claim`, `claimforms_claim_encounter`, `claimforms_claim_line`, `claimforms_event` exist.

## 2. Column names (run `SHOW COLUMNS FROM <table>`)
`src/DraftBuilder.php` reads these. Adjust the query if a column is named differently on your version.
- [ ] `patient_data`: fname, lname, DOB, street, city, state, postal_code, phone_home, phone_cell, phone_contact, ref_providerID
- [ ] `insurance_data`: type, provider, policy_number, group_number, date, subscriber_fname, subscriber_lname,
      subscriber_relationship, subscriber_employer, subscriber_street, subscriber_city, subscriber_state,
      subscriber_postal_code, subscriber_phone
- [ ] `insurance_companies`: id, name
- [ ] `form_encounter`: encounter, date, pos_code, onset_date, provider_id, facility_id, reason
- [ ] `billing`: pid, encounter, activity, code_type, code, code_text, modifier, fee, justify, date
- [ ] `lists`: pid, type, activity, diagnosis, title, begdate
- [ ] `users`: id, fname, lname, organization, specialty, username, authorized, active
- [ ] `facility`: id, name, street, city, state, postal_code, phone
- [ ] `icd10_dx_order_code` (formatted_dx_code, short_desc, valid_for_coding, active) if ICD-10 is loaded; otherwise `codes` + `code_types`

## 3. Data behaviour
- [ ] Pre-fill matches the chart for three test patients (self-insured, dependent, no insurance).
- [ ] `billing.fee` is the full charge for the line (not a per-unit price). If it is per unit, multiply by `units` in `lineItems()`.
- [ ] Diagnosis pointer on each line matches the billing line's `justify` codes.
- [ ] Place of service maps correctly (11 Office, 12 Home, 21/22/23 Hospital). Change `POS_MAP` for other codes.
- [ ] The referring-physician list shows the people you expect. Adjust the filter in `DraftBuilder::referrers()` if the
      Address Book uses a different `abook_type` convention.

## 4. Documents
- [ ] After Generate, the PDF opens from the claim list. It is also filed under the patient's Documents in the
      "Insurance" category. If that category does not exist, it is filed under the root category. If filing
      fails, the claim still works from the module's own storage.

## 5. Security
- [ ] A user without patient access cannot open any module page (403).
- [ ] Opening `claim.php?id=<another patient's claim>` from a different patient's chart gives "Claim not found".
- [ ] Provider A's claim carries only Provider A's signature.
- [ ] `sites/<site>/documents/claim_forms/` is not reachable by URL.

## 6. Print test
- [ ] Print a generated claim at 100% (no "fit to page") on the real stationery or plain paper over the original;
      every value sits inside its box.
- [ ] Confirm with NAGICO where the further-services charge and the surgical charge go. The form has one charge
      column for both blocks. Current placement: further services on the first row of the lower block, surgical
      on the Type of Operation row (`further_services_*` and `surgical_*` in `map.json`).
