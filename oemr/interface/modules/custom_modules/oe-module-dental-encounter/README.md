# Carelio Dental Encounter

An installable OpenEMR custom module converted from the supplied Carelio dental encounter prototype. Version **1.0.2** targets **OpenEMR 7.0.3 and later**, with PHP 8.1+. It uses OpenEMR's encounter form loader, ACL, CSRF, database, session, and e-sign APIs. No core-file edits or separate application server are required.

## Install

1. Extract `oe-module-dental-encounter-1.0.2.zip` into your OpenEMR installation's `interface/modules/custom_modules/` directory. The recommended result is:

   ```
   openemr/interface/modules/custom_modules/oe-module-dental-encounter/info.txt
   openemr/interface/modules/custom_modules/oe-module-dental-encounter/openemr.bootstrap.php
   ```

   Version 1.0.2 also supports a different installation folder name. OpenEMR's Module Manager must register and enable that same folder.

2. Give the web server read access to the module files. PHP does not need write access to this module directory.
3. Sign in to OpenEMR as an administrator. Open **Modules → Manage Modules**; register, install, and enable **Carelio Dental Encounter**. Installation executes `table.sql` and registers **Dental Encounter** in the Clinical encounter-form category. Menu wording may vary by OpenEMR configuration.
4. Open a patient and an existing encounter. Select **Clinical → Dental Encounter** from the encounter's form menu.
5. Enter a finding, choose **Save to encounter** / **Save encounter**, return to the encounter, and reopen the saved form. Verify this workflow in your site's test environment before clinical use.

If a site's module manager lists installation and enabling separately, complete both. Do not install this folder into `interface/forms/`: the enabled module routes its own form files through OpenEMR's form-loading event.

The packaged files are ready to use. Composer, Node.js, and Python are not required on the OpenEMR server for a manual installation.

### Upgrade from 1.0.0 or 1.0.1

1. In **Modules → Manage Modules**, note the folder/directory registered for Carelio Dental Encounter.
2. Back up the existing module folder and database using your normal OpenEMR backup process.
3. Extract version 1.0.2 somewhere temporary, then copy the *contents* of its `oe-module-dental-encounter/` folder over the existing registered module folder. Preserve the existing folder name and avoid creating a nested `oe-module-dental-encounter/oe-module-dental-encounter/` directory.
4. Confirm that `src/Module.php` now exists in the registered module folder. Enable the module if it is disabled, then sign out and back in before reopening the encounter. Restart PHP/Apache if the server uses an opcode cache that does not promptly revalidate changed PHP files.

There is no schema migration from 1.0.0 or 1.0.1 to 1.0.2. Existing dental records remain in place.

## What is included

- The original 19-section layout, local Carelio branding, and responsive/print styles.
- Patient name, date of birth, chart number, and encounter context from OpenEMR. Chart and encounter identifiers are read-only and enforced again on save.
- Permanent and primary tooth charts, Universal / FDI / Palmer notation, tooth findings, surfaces, and notes. The prototype's primary lower-arch FDI sequence is corrected to match its canonical Universal K–T identifiers.
- All 117 form fields and 20 checkbox groups, plus diagnosis, treatment, medication, and billing rows.
- Authenticated server-side save and reopen, signature-image storage, a revision history, and stale-edit rejection.
- Native encounter and medical-record reports, including both dentitions and escaped clinical text.
- Native e-sign locking: when OpenEMR's individual or encounter locking is configured, locked forms open read-only and reject saves.
- Patient squad and encounter sensitivity checks, clinical form ACL checks, CSRF protection, and active-patient/encounter checks on every save.

The first visit to this form in an encounter opens its existing active dental record, if present. Competing attempts to create a second record receive a conflict instead of duplicating the record. A record deleted through OpenEMR's normal form workflow is excluded from reopening.

Version 1.0.1 fixed the misleading “module is unavailable” error caused when the installed folder name differed from `oe-module-dental-encounter`. Version 1.0.2 reads authentication and encounter context through OpenEMR's session API, fixing the false “session has expired” message on OpenEMR releases that no longer expose session state through PHP's raw `$_SESSION` array.

## Clinical workflow boundaries

**Imaging:** use **Open patient documents** to upload images, radiographs, and attachments through OpenEMR's native document workflow. The original filename-only upload placeholders have been removed. This module does not add a DICOM viewer.

**Medication documentation:** medication rows and printed instructions are retained in the encounter note. They do not create prescription orders or transmit electronic prescriptions. Use OpenEMR's prescribing workflow for orders.

**Billing:** codes, estimates, discounts, payments, and balances are encounter documentation in XCD. They do not post to the fee sheet, claims, or payment ledger. The prototype's code descriptions are preserved as documentation choices, including entries representing code ranges; use your configured OpenEMR code catalog for billable charges.

**Medical history:** allergy and medication review fields are encounter documentation. They do not synchronize with the patient's longitudinal allergy, medication, or problem lists.

**Sign-off:** the drawn signature and declaration are saved with the note. Formal signing and locking use OpenEMR's native e-sign controls on the encounter page. Saving alone does not e-sign a record.

**Reset:** discards unsaved edits and restores the most recently saved record. It does not delete a saved record.

The module does not save patient information to browser local storage or download JSON when saving. Fonts use local system fallbacks; there are no third-party font requests.

## Permissions and storage

Viewing requires access to patient demographics, the registered `encounters|notes` form ACL, the patient's squad where configured, and the encounter's sensitivity level. Creating a record allows `write` or `addonly`; changing an existing record requires `write`. Existing OpenEMR e-sign lock settings are honored.

`form_dental_encounter` contains the current versioned JSON document and the standard fields used by OpenEMR forms/e-sign. `forms` links it to the patient and encounter. `mod_dental_encounter_history` retains every successful revision, save time, and saving user ID. Clinical updates, native form linkage, and revision history are committed in one transaction. OpenEMR's normal database audit infrastructure is used.

Include both module tables in site backup/retention procedures. History is stored for administrator retrieval; this version has no history-browsing or revision-restore screen. Limit: 1 MB per save, 200 rows per table, and a validated PNG signature up to 700 KB encoded.

Disabling the module prevents access to its routes and removes its form-loader hook. Re-enable it to view existing records. Before disabling it permanently, hide **Dental Encounter** in Forms Administration and retain the module code and database tables with your records. No automatic uninstall or patient-data deletion is provided.

## Verification

Tested on **2026-10-20** against the official **OpenEMR 7.0.3 and 8.2.0** Docker images with real MariaDB databases. The same **31 backend checks and 8 authenticated HTTP checks passed on each OpenEMR version**, and 5 client tests passed. Automated checks cover:

- Repeatable installation and native form loading/report rendering.
- Patient/encounter binding, clinical ACL rejection, CSRF, unauthenticated requests, disabled modules, unregistered folders, and renamed installation folders.
- Create, update, reopen, revision history, duplicate creation, stale revisions, and transaction rollback.
- Individual-form and encounter e-sign locks.
- Form state round trips, both tooth charts, dynamic tables, notation, signature field restoration, failed-save retention, and edits made during a pending save.

PHP and JavaScript syntax checks also pass. Connected-browser visual verification was unavailable in the development session. OpenEMR releases between 7.0.3 and 8.2.0 use the compatibility paths covered by these two endpoint versions; every intermediate release was not separately runtime-tested.

## Development references

- [OpenEMR Forms API](https://www.open-emr.org/wiki/index.php/The_Forms_API)
- [OpenEMR custom module skeleton](https://github.com/openemr/oe-module-custom-skeleton)
- [OpenEMR 7.0.3 form-loading event](https://github.com/openemr/openemr/blob/v7_0_3/src/Events/Encounter/LoadEncounterFormFilterEvent.php)

The original HTML and SVG source files are preserved in the parent project. `tools/convert_prototype.py` rebuilds the static form, CSS, JavaScript, and field schema. Module-specific persistence lives in `public/assets/integration.js`; backend logic lives in `src/`.
