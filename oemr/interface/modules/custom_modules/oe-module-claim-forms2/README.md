# Insurance Claim Forms (OpenEMR module)

Fills an insurer's claim form from the patient chart. The provider reviews and edits the
pre-filled values, then the module stamps them onto the insurer's original PDF, with the
provider's own signature, stamp and the date.

Ships with ten forms: NAGICO, Sagicor (GI40007), CG United (health, vision, dental and prescription drug), Guardian Life, Pan-American Life, Med-Access and GTM. Each is a folder under `forms/`, named after its PDF. More forms are added as folders, with no code changes (see below).

## Install

1. Copy this folder to `interface/modules/custom_modules/oe-module-claim-forms2/` on the OpenEMR server.
   Menu links are derived from the installed folder name.
2. In OpenEMR: **Admin > Modules > Manage Modules**. Click **Register**, then **Install**, then **Enable**.
   The tables are created on install, and again on first use if they are missing.
3. Make sure the web server user can write to `sites/<site>/documents/` (it already can on a normal install).
   The module creates `documents/claim_forms/` there for signatures and generated PDFs.
4. Each provider opens **Patient > Insurance Claim Forms > My signature and stamp** once and uploads their images.

Requires OpenEMR 8.0+, PHP 8.1+, and the PHP `gd` and `fileinfo` extensions (standard on OpenEMR installs).
mPDF is already bundled with OpenEMR; nothing to install with composer.

Repeat on each OpenEMR instance. Nothing is site-specific: facility name, address and phone come from
each install's own Facility record.

## Using it

1. Open a patient, then **Patient > Insurance Claim Forms**.
2. Choose the form and tick the encounters to claim. Start claim.
3. Review. Pick the ICD codes (the encounter's diagnoses are listed; the search box finds any ICD-10 code),
   the referring physician, and check the line items. Charges are editable.
4. **Save draft** any time. **Generate PDF** validates, stamps and opens the PDF.
5. Reopen a claim from the list to amend it. Regenerating keeps the earlier PDF and bumps the revision.

## What gets pre-filled

| Form field | From |
|---|---|
| Policy no., holder, ID | Primary plan in the patient's insurance (`insurance_data`) |
| Patient, DOB, address, phone | `patient_data` (subscriber's details when the insured is someone else) |
| Name and address of doctor/provider | The encounter's facility (`facility`), with its phone |
| ICD codes 1-4 | Diagnoses billed on the chosen encounters, then the issue list |
| Referring physician | The patient's referring provider if set; dropdown of Address Book contacts and providers |
| Line items | Billed services on the chosen encounters: date, place of service, description, diagnosis pointer, fee |
| Doctor's stamp, signature, date | The logged-in provider's saved images and today's date |

The insured's and patient's signatures in Section 2 are never stamped.

Page 2 (sections 5 to 9: hospital, laboratory/X-ray, dentist, optometrist, signature) has a switch for each section on the review screen. A section prints only when its switch is on. Dentist and optometrist totals are added up automatically.

## Adding another insurer's form

Create `forms/<new_id>/` containing:

- `background.pdf`: the insurer's blank form. Save it as **PDF 1.4 without object streams**, or FPDI cannot import it:
  `qpdf --object-streams=disable --force-version=1.4 original.pdf background.pdf`
- `map.json`: where each value goes (points, origin top-left, text y is the baseline). Every field has a `source`
  (a dotted path into the claim payload). Copy `nagico_health_claim/map.json` as a starting point.
- `form.json`: the review-screen layout. Controls: `text`, `date`, `textarea`, `radio`, `yesno`, `money`, `icd`, `referrer`, `lines`.

Then check it by stamping a sample claim and looking at the result (`tests/smoke.php` shows how to call the renderer).

## Security

- Every page checks the OpenEMR ACL (`patients/med`) and every write checks the CSRF token.
- All SQL uses bound parameters. All output is escaped. The browser's input is sanitized before it is stored.
- A claim can only be opened from the chart of the patient it belongs to.
- Signature and stamp files are named from the numeric user id only, re-encoded as PNG on upload, kept under the
  site's `documents/` folder (blocked from direct web access), and only ever served to their owner.
- Opens, edits, generations, downloads and signature changes are recorded in `claimforms_event`; generation is also
  written to OpenEMR's audit log.

## Tests

`php tests/smoke.php /path/to/vendor/autoload.php` runs the offline checks (payload helpers, sanitizer, validator,
PDF renderer on the real form). It does not need a database. See `docs/VERIFY.md` for what must be checked on a live install.

## Layout

```
openemr.bootstrap.php     entry point OpenEMR runs
src/                      Bootstrap (menu), DraftBuilder (chart -> claim), PdfRenderer, Validator, repositories
public/                   pages: index, claim (review screen), api, download, signature
forms/<id>/               form.json, map.json, background.pdf
sql/table.sql             module tables (claimforms_*)
tests/smoke.php           offline checks
```
