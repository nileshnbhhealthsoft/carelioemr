# Lab Requisition (OpenEMR module)

The Carelio paper laboratory requisition, as an OpenEMR module. The provider opens it from the patient chart,
the patient's details are pre-filled, they tick the tests, and the module saves the order against the patient and
prints it on A4 exactly as the paper form looks.

Converted from `lscl_lab_requisition.html`; the layout, wording and 121 tick-boxes are the same.

## Install

1. Copy this folder to `interface/modules/custom_modules/oe-module-lab-requisition/` on the OpenEMR server.
   The folder name matters: the menu links assume it.
2. In OpenEMR: **Admin > Modules > Manage Modules**. Click **Register**, then **Install**, then **Enable**.
   The tables are created on install, and again on first use if they are missing.

Requires OpenEMR 8.0+ and PHP 8.1+. Nothing to install with composer. Repeat on each OpenEMR instance.

## Using it

1. Open a patient, then **Patient > Lab Requisition** (or the **Lab Requisition** tab on the dashboard).
2. **New lab requisition**. Name, DOB, sex, registration number, address, contact, doctor (you) and date are pre-filled
   from the chart and can be edited.
3. Tick the specimen and tests. Where the paper form has a line (swab site, CMV Ab Ig, "Other (state)"...), type on it.
4. **Save draft** any time. **Finalize and print** locks the requisition, records you as the ordering user
   (and fills "Signed" with your name if it is blank), and opens the print view.
5. **Print / Save PDF** from the print view. Use A4, default margins, scale 100%.
   A finalized requisition cannot be edited; cancel it and make a new one.

## What it stores

| Table | Holds |
|---|---|
| `labreq_order` | one row per requisition: header fields, priority, clinical details, status, who finalized it and when |
| `labreq_order_item` | one row per ticked specimen or test, with the "state" text where there is one |
| `labreq_event` | created / edited / finalized / cancelled / viewed, with user and time |

The header values are a snapshot at the time of ordering (so a later change to the patient's address does not
rewrite an old requisition).

## Differences from the HTML form

- "Opiates" appeared twice under Drugs; it is kept once.
- Hematology "Other" now has a line to write on (the paper form had no room).
- URGENT and STAT stay as two independent boxes, as on paper.
- The logo is bundled as `public/assets/carelio-logo.svg`; replace the file to rebrand.
- Removed: the browser-only Clear button (use Back), and the `window.CarelioCompanyLogo` hooks (the logo is a file now).

## Security

- Every page checks the OpenEMR ACL (`patients/med`); every write checks the CSRF token.
- All SQL uses bound parameters; all output is escaped with OpenEMR's `text()`/`attr()`.
- Posted values are checked against the catalog: unknown test codes are discarded, free text is stripped of tags.
- A requisition can only be opened from the chart of the patient it belongs to.
- Finalized/cancelled requisitions are read-only on the server, not just hidden in the UI.

## Tests

`php tests/smoke.php` runs the offline checks (catalog, sanitizer, sheet rendering). It does not need OpenEMR.
See `docs/VERIFY.md` for what must be checked on a live install.

## Layout

```
openemr.bootstrap.php   entry point OpenEMR runs
src/                    Bootstrap (menus), Catalog (the tick-boxes), Chart (pre-fill), Sanitizer, OrderRepository, Sheet (renders the form)
public/                 pages: index, order (entry), save, print; assets/ (css, logo)
sql/table.sql           module tables (labreq_*)
tests/smoke.php         offline checks
```
