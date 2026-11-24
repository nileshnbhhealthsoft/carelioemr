# CarelioEMR — Operations & Setup Summary (Plain English Guide)
**Product:** CarelioEMR Cloud Healthcare Platform  
**Target Audience:** Clinic Owners, Business Administrators, and Operations Teams  
**Date:** November 2026  
**Status:** Fully Configured & Operational  

---

## 1. What is CarelioEMR in Plain Words?

Think of **CarelioEMR** like **Shopify, but for Medical Clinics**:

Normally, installing medical software for a doctor takes days of manual server setup. With CarelioEMR:
1. A doctor visits your website, enters their clinic name, and pays $80/month.
2. The system **automatically builds a private, secure medical clinic** for that doctor in seconds.
3. You (the business owner) can approve accounts and track subscription revenue in your admin portal.
4. The doctor logs in, changes their password, and immediately starts seeing patients and booking appointments.

---

## 2. The 3 Portals at a Glance

| # | Portal Name | Who Uses It? | Direct Link | Credentials |
|---|---|---|---|---|
| **1** | **The Storefront** *(Public Website)* | Prospective Doctors & Clinic Owners | [http://localhost/carelioemr/](http://localhost/carelioemr/) | Public access |
| **2** | **The Management Office** *(Platform Admin)* | You (The Platform Owner) | [http://localhost/carelioemr/public/admin/login](http://localhost/carelioemr/public/admin/login) | **Email:** `admin@carelioemr.com`<br>**Password:** `admin123` |
| **3** | **The Clinic Portal** *(OpenEMR Workplace)* | The Doctor & their Clinic Staff | [http://localhost/carelioemr/oemr/](http://localhost/carelioemr/oemr/) | **Username:** `siteadmin`<br>**Password:** `Carelio@123` |

---

## 3. What Has Been Done (Full Story of Setup & Fixes)

Here is everything that was completed to make the entire system run smoothly from start to finish:

### 1. Fixed Missing Composer Packages
* **The Problem:** The website showed a fatal error: `vendor/autoload.php: Failed to open stream: No such file or directory`.
* **What Was Done:**
  - Installed all **112 Composer packages** for the Laravel application layer.
  - Installed all **187 Composer packages** for the OpenEMR clinical engine layer.

### 2. Initialized Platform Configuration & Database
* **The Problem:** The application had no environment configuration (`.env`) or central database (`auraemr`).
* **What Was Done:**
  - Created `.env` and generated the unique application encryption key (`php artisan key:generate`).
  - Created the MySQL database `auraemr` and ran migrations for user sessions, background jobs, and admin roles.
  - Set up the default Super-Admin user (`admin@carelioemr.com` / `admin123`).

### 3. Configured Database Users & Permissions
* **The Problem:** OpenEMR was unable to connect to the MySQL database because of mismatched database passwords.
* **What Was Done:**
  - Configured MySQL user permissions for `openemr` with the exact credentials expected by the system (`Root@12345`).
  - Disabled inactive legacy module references so pages load instantly in seconds.

### 4. Deployed Official CarelioEMR Branding
* **The Problem:** The clinic system was showing default generic open-source graphics.
* **What Was Done:**
  - Automatically deployed the official **CarelioEMR logos** and **browser favicons** into the clinic templates (`oemr/sites/default/images/logos/`).
  - The login screen, main menu, and browser tab now feature official Carelio branding.

### 5. Activated Module 1 (Security & Caribbean Localization)
* **What Was Done:**
  - Enabled **Module 1 (`site_admin_config`)** in the clinic system.
  - **First-Time Password Security:** Activated the mandatory first-time password reset system (`mod_site_admin_temp_passwords`), ensuring healthcare compliance by requiring doctors to change temporary passwords upon first sign-in.
  - **Safe Clinic Permissions:** Configured the `Site Administrator` role so doctors can manage patients, charts, and billing, while preventing them from touching global server files.

### 6. Imported Caribbean Demographics & Fixed Dropdowns
* **The Problem:** 
  1. The Community dropdown was empty when selecting Castries.
  2. The system hit a 512MB memory error (`Allowed memory size exhausted in LineFormatter.php`) because 34,000 unmapped raw global locations were bloated into one form script.
* **What Was Done:**
  - Increased MySQL `max_allowed_packet` to `64MB` to comfortably handle geographic datasets.
  - Optimized the demographic engine to focus on **Saint Lucia (all 368 communities for all 10 districts)** and priority Caribbean countries, slashing the payload size from **2.5 MB** down to **294 KB** (an **88% reduction**).
  - Updated the dropdown refresh code to be fully compatible with **Select2 4.x**, so picking **Castries** instantly displays all **108 local communities** with zero errors.

---

## 4. Step-by-Step Visual Testing Guide

Follow these 3 simple checks to see everything working with your own eyes:

### Check 1: The Public Landing Page
1. Open [http://localhost/carelioemr/](http://localhost/carelioemr/) in your browser.
2. **What you will see:** The full commercial landing page displaying CarelioEMR healthcare features, pricing ($80/month), and practice sign-up forms.

### Check 2: The SaaS Platform Admin Console
1. Open [http://localhost/carelioemr/public/admin/login](http://localhost/carelioemr/public/admin/login).
2. Log in with:
   - **Email:** `admin@carelioemr.com`
   - **Password:** `admin123`
3. **What you will see:** The platform dashboard displaying active subscribers, MRR financial metrics, doctor review/approval buttons, and Stripe logs.

### Check 3: The Clinic Portal & Caribbean Dropdowns
1. Open [http://localhost/car elioemr/oemr/](http://localhost/carelioemr/oemr/).
2. Look at the login screen: You will see the **CarelioEMR logo** and custom browser favicon.
3. Log in with:
   - **Username:** `siteadmin`
   - **Password:** `Carelio@123`
4. In the top navigation bar, click: **Patient / Client ➔ New / Search**.
5. Scroll down to the **Address** section:
   - Select **Country:** `Saint Lucia (LC)`
   - Select **Parish / District:** `Castries`
   - Click the **Community** dropdown: It will display all **108 local Castries communities** (*Active Hill, Babonneau Proper, Bisee, Ciceron, Marchand*, etc.) smoothly and with zero memory errors.

---

### Check 4: Hospital Bed & Ward Hierarchy (HMS Core Console)
1. Open the HMS Core Console directly or through the menu:
   - [http://localhost/carelioemr/oemr/interface/modules/custom_modules/oe-module-carelio-core/index.php?site=default](http://localhost/carelioemr/oemr/interface/modules/custom_modules/oe-module-carelio-core/index.php?site=default)
2. **What you will see:**
   - **Bed & Ward Hierarchy:** The complete physical structure of Saint Lucia's OKEU Hospital:
     - *Inpatient Clinical Tower* &rarr; *Emergency Bay & Beds*
     - *First Floor* &rarr; *Maternity & Postnatal Ward*
     - *Second Floor* &rarr; *General Medicine & Surgical Ward*
     - *Third Floor* &rarr; *Intensive Care Unit (ICU) Suites & Ventilator Beds*
   - **Interactive Management:** Click **"Add Location Node"** to add custom wards, rooms, or beds.
   - **HMS Module Catalog Tab:** Review all 23 hospital modules as defined in the official Carelio Specification. Test clicking "Activate" on Phase 3 modules to see the dependency blocker popup in action.

---

### Check 5: The 9-Step Hospital Install Wizard & Self-Check Diagnostics
1. In the Core Console, click the **"9-Step Install Wizard"** tab (`?page=wizard`).
2. **What you will see:**
   - **Steps 1–8:** Step-by-step hospital parameter configuration (Saint Lucia OKEU profile, East Caribbean Dollar currency, Section 6 ward catalog, clinical services, 40-node bed baseline, 5-tier sensitivity model, and diagnostic integration paths).
   - **Step 9:** Click **"Apply Hospital Configuration & Run Self-Check"**.
   - **Result:** The system runs a 7-point live health diagnostic and marks the installation **"INSTALLED & VERIFIED"** with 7 green checkmarks.

---

### Check 6: Clinical Governance & Medical Director Approvals
1. In the Core Console, click the **"Content Approvals"** tab (`?page=approvals`).
2. **What you will see:**
   - The Medical Director clinical sign-off queue (`hms_content_approval`).
   - Clinical order sets (*Adult Sepsis Resuscitation Bundle*, *DKA Protocol*) and panic alerts.
   - The Medical Director can review the clinical payload and click **"Sign & Approve"** or **"Reject"** with mandatory clinical comments. Unapproved forms stay safely locked in DRAFT.

---

### Check 7: 5-Tier Privacy & 24-Hour Emergency "Break-Glass"
1. In the Core Console, click the **"Privacy & Break-Glass"** tab (`?page=privacy`).
2. **What you will see:**
   - **5-Tier Sensitivity Management:** Protects sensitive charts (Psychiatry, HIV/STIs, Staff-as-Patient, Protected Identity).
   - **Emergency Override:** Click **"Test Break-Glass"** to simulate an emergency clinical override.
   - **24-Hour Accountability Clock:** The access is immediately logged in `hms_breakglass_log` with an exact 24-hour review deadline for the Hospital Privacy Officer. Unreviewed events past 24 hours trigger red **OVERDUE ESCALATED** alerts.

---

### Check 8: Emergency Registration, Duplicate Detection, Reversible Unmerge & Deposits (Module 2: REG)
1. In the Core Console, click the **"Registration (REG)"** tab (`?page=registration`).
2. **What you will see:**
   - **Sub-Tab 1: Rapid ED Patients:** Generates immediate `TEMP-YYYYMMDD-001` identities in < 5 seconds with RED/ORANGE/YELLOW trauma triage acuity, opening an immediate emergency inpatient encounter (`EMER`) so doctors can order labs, blood, and imaging before formal paperwork arrives.
   - **Sub-Tab 2: Duplicate Check & Comparison:** Enter First Name, Last Name, DOB, Phone, or National ID. The system runs real-time soundex and exact matching, returning a match confidence percentage badge (e.g., 90% Confidence) and match reasons. Click **"Compare"** to view side-by-side demographic and clinical metrics (encounters, vitals, orders, rx).
   - **Sub-Tab 3: Merge & Unmerge Audit Trail:** View all historical patient merges. Active merges provide a red **"Unmerge Chart"** button that safely restores all clinical notes, orders, and encounters back to the original chart while preserving the audit record with status `UNMERGED`.
   - **Sub-Tab 4: Reconciled ED History:** Full audit log of emergency temporary identities merged into verified permanent patient charts.
   - **Sub-Tab 5: Insurance & Consents:** Look up active insurance coverage and copayment eligibility, and capture signed inpatient treatment and financial guarantee consent agreements.
   - **Sub-Tab 6: Deposits Ledger:** Collects cash, card, or check deposits in East Caribbean Dollars (XCD), issues official receipts (`RCPT-YYYYMMDD-001`), and synchronizes directly with OpenEMR's standard `ar_session` financial ledger.
   - **Sub-Tab 7: REG Reports:** Real-time analytics showing registrations by day over the last 7 days and active suspected duplicate pairs in the Master Patient Index.
