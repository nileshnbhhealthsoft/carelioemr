# Radiology Requisition (OpenEMR module)

The Carelio radiology (diagnostic imaging) requisition, as an OpenEMR module. One order can hold several studies
across any modality. Each study carries its own protocol and modality-specific safety screening. The order is
saved against the patient and prints as a clean A4 document.

Converted from `radiology_requisition.html`. Modalities: X-ray, CT, MRI, Ultrasound, Mammography, Fluoroscopy,
Nuclear Medicine, Other.

## Install

1. Copy this folder to `interface/modules/custom_modules/oe-module-radiology-requisition/` on the OpenEMR server.
   The folder name matters: the menu links assume it.
2. In OpenEMR: **Admin > Modules > Manage Modules**. Click **Register**, then **Install**, then **Enable**.
   The tables are created on install, and again on first use if they are missing.

Requires OpenEMR 8.0+ and PHP 8.1+. Nothing to install with composer. Repeat on each OpenEMR instance.

## Using it

1. Open a patient, then **Patient > Radiology Requisition** (or the dashboard tab).
2. **New radiology requisition**. Patient, MRN, DOB, sex, encounter, facility, contact, allergies and the ordering
   clinician are pre-filled from the chart and can be edited.
3. Fill in the clinical history and the clinical question. Add studies: pick the modality, then the examination.
   Safety questions appear for the modality and start as *Unknown / Not assessed*. CT, MRI and nuclear medicine also
   show the creatinine / eGFR block.
4. **Save draft** any time. **Finalize and print** checks the required items, locks the order, and records the
   logged-in user and time as the authorization. If safety answers are still Unknown, tick the confirmation box
   at the bottom of the form to finalize anyway.
5. **Print / Save as PDF** from the print view. A finalized order cannot be edited; cancel it and make a new one.

## What it stores

| Table | Holds |
|---|---|
| `radreq_order` | one row per requisition: patient and encounter snapshot, clinical details, priority, clinician, status, who finalized it and when |
| `radreq_study` | one row per study: modality, examination, laterality, contrast/protocol, renal results, safety answers (JSON, keyed by question text) |
| `radreq_event` | created / edited / finalized / cancelled / viewed, with user and time |

## Differences from the HTML form

- "Electronic signature status" (Unsigned / Pending) is replaced by real finalization: the authenticated OpenEMR user and
  the time are recorded, and the order locks. This is an audit record, not a cryptographic signature.
- "Export JSON draft" and the `CarelioRadiology.prefill()` hook are gone: the module reads the chart itself and saves server-side.
- The logo is a file, `public/assets/carelio-logo.svg`; replace it to rebrand.
- Up to 20 studies per requisition.

## Security

- Every page checks the OpenEMR ACL (`patients/med`); every write checks the CSRF token.
- All SQL uses bound parameters; all output is escaped. The study builder uses DOM APIs, never `innerHTML`.
- Posted choices (modality, examination, protocol, answers) are checked against `src/Catalog.php`; free text has tags stripped.
- A requisition can only be opened from the chart of the patient it belongs to.
- Finalized/cancelled requisitions are read-only on the server.

## Tests

`php tests/smoke.php` runs the offline checks (catalog and sanitizer). It does not need OpenEMR.
See `docs/VERIFY.md` for what must be checked on a live install.

## Layout

```
openemr.bootstrap.php   entry point OpenEMR runs
src/                    Bootstrap (menus), Catalog (modalities, exams, safety questions), Chart (pre-fill), Sanitizer, OrderRepository
public/                 pages: index, order (entry), save, print; assets/ (css, js, logo)
sql/table.sql           module tables (radreq_*)
tests/smoke.php         offline checks
```
