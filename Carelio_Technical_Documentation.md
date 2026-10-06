# CarelioEMR — Comprehensive Technical Documentation
**Subject:** Site Admin Configuration & Subscription Management Modules  
**Author:** CarelioEMR Engineering Team  
**Date:** October 2026  
**Version:** 2.0  
**Repository Branch:** `subscription-model`  

---

## 1. Executive Summary & Architecture Overview

CarelioEMR is an enterprise multi-tenant Electronic Medical Records (EMR) platform based on OpenEMR. To ensure long-term maintainability, seamless upstream OpenEMR upgrades, and tenant isolation, all platform extensions are built strictly according to **Zero Core Modification Principles**.

### Key Architectural Guidelines:
1. **Zero Core Modifications:** No core OpenEMR engine scripts (in `/src/`, `/interface/main/`, `/library/`) are altered.
2. **Custom Module Encapsulation:** All capabilities are bundled as independent, self-contained OpenEMR modules in `/interface/modules/custom_modules/`:
   * **`site_admin_config`:** Tenant Administration, Caribbean Demographics, Branding Assets, Role-Based Access Control, and Temporary Password Security.
   * **`carelio_subscription`:** Subscription Lifecycle Monitoring, 2-Day Grace Period, Safe Deactivation, Site Admin Renewals/Extensions, Server-Side Access Enforcement, and Daily Automated Background Monitoring.
3. **Idempotency & Zero Data Loss:** All database scripts and migrations execute with `ON DUPLICATE KEY UPDATE` and idempotent checks. Subscription deactivation never deletes clinical, patient, encounters, or billing data.

---

## 2. Module 1: Site Admin Configuration (`site_admin_config`)

### 2.1 Caribbean Geographic Demographics Engine
* **Dataset Scope:** Embedded full geographic coverage for 29 Caribbean countries, all 10 Saint Lucia districts, and 367 distinct local communities.
* **Storage & Structure:** Self-contained within `table.sql` and `sql/table.sql` (90 KB+). Data is injected into OpenEMR's standard `list_options` schema using composite primary keys `(list_id, option_id)` with `ON DUPLICATE KEY UPDATE`.
* **Dynamic Cascading UI Script:** Embedded `location_cascading_script` into `layout_options`. When a user selects a Caribbean country (e.g., Saint Lucia `LC`), the state dropdown dynamically repopulates with the 10 administrative districts. Selecting a district filters communities accordingly.

### 2.2 Carelio Brand Assets Auto-Deployment
* Implemented in `SiteAdminInstaller::deployBrandAssets()`.
* Automatically provisions Carelio branding assets to tenant site directories (`oemr/sites/<tenant>/images/logos/`):
  * `core/login/primary` & `portal/login/primary` ➔ `logo.svg`, `logo.png`
  * `core/favicon` ➔ `favicon.ico`
  * `core/menu/primary` & `portal/menu/primary` ➔ `logo.svg`, `logo.png`
  * Legacy fallbacks ➔ `login_logo.gif`, `logo_1.png`, `logo_2.png`

### 2.3 Robust Multi-Runtime Database Connection Resolver
* **Issue Solved:** In OpenEMR web runtime, `$GLOBALS['adodb']['db']->_connectionID` is a `\mysqli` instance, while CLI/seeders use `\PDO`.
* **Implementation (`SiteAdminInstaller::resolvePdo()`):**
  1. Checks for existing PDO instances in `$GLOBALS['dbh']`.
  2. Resolves target tenant site directory via `$GLOBALS['OE_SITE_DIR']`, session, or folder inspection.
  3. Uses OpenEMR `DatabaseConnectionOptions::forSite()` and `DatabaseConnectionFactory::createDbal()`.
  4. Parses tenant `sqlconf.php` in an isolated scope and establishes a dedicated UTF-8 PDO connection (`SET NAMES 'utf8mb4', sql_mode = ''`).

### 2.4 Forced Temporary Password Security
* **Files:** `TemporaryPasswordService.php` and `change_temporary_password.php`.
* **Flow:** First-time tenant login triggers mandatory password reset. Validates current temporary password, verifies complexity, updates hash via OpenEMR `AuthUtils`, clears temporary flag, and immediately redirects to the OpenEMR login page (`login.php?site=<tenant>`) for a clean, secure session launch.

---

## 3. Module 2: Subscription Management (`carelio_subscription`)

### 3.1 Status Lifecycle State Machine

The subscription module enforces a 4-stage lifecycle state machine:

```
             ┌───────────────┐
             │    ACTIVE     │  (Normal Active Period)
             └───────┬───────┘
                     │
             ≤ 2 days remaining
                     │
                     ▼
             ┌───────────────┐
             │ EXPIRING SOON │  (Automated 2-Day Renewal Notification)
             └───────┬───────┘
                     │
                Expiration
                     │
                     ▼
          ┌─────────────────────┐
          │ EXPIRED / GRACE     │  (2-Day Grace Period Active)
          │     PERIOD          │  (Uninterrupted Clinic Operations)
          └──────────┬──────────┘
                     │
                2 days passed
                     │
                     ▼
             ┌───────────────┐
             │  DEACTIVATED  │  (Site Deactivated, Server-Side Locked)
             └───────┬───────┘  (100% Data Preserved - Clinical/Patient/Billing)
                     │
              Site Admin renews / reactivates
                     │
                     ▼
             ┌───────────────┐
             │    ACTIVE     │  (Restored +30 Days)
             └───────────────┘
```

#### Lifecycle Stage Details:
1. **`ACTIVE`:**
   * Subscription is active. Account has full access.
2. **`EXPIRING_SOON`:**
   * Remaining days $\le$ 2 days (48 hours).
   * Daily monitoring process flags subscription and dispatches renewal reminder notification to clinic administrator.
3. **`EXPIRED_GRACE_PERIOD`:**
   * Period end has passed, but time is within the 2-day (48-hour) grace window.
   * Clinic users can continue using the application without interruption.
   * Prominent grace period notice displayed on the dashboard.
4. **`DEACTIVATED`:**
   * 2-day grace period has elapsed without renewal.
   * Site is deactivated and locked server-side.
   * **Data Preservation Guarantee:** Zero data deletion. All clinical records, encounters, medical history, documents, and billing records remain 100% intact.
   * Account remains immediately available for Site Administrator reactivation.

---

### 3.2 Database Schema Architecture

The module utilizes 4 dedicated database tables inside each tenant database:

```sql
-- 1. Main Subscriptions Table
CREATE TABLE IF NOT EXISTS `mod_carelio_subscriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_subscription_id` VARCHAR(191) NULL UNIQUE,
  `site_id` VARCHAR(191) NULL,
  `site_name` VARCHAR(191) NULL,
  `tenant_slug` VARCHAR(191) NULL,
  `customer_name` VARCHAR(191) NULL,
  `customer_email` VARCHAR(191) NULL,
  `plan_name` VARCHAR(120) NULL,
  `plan_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `billing_cycle` VARCHAR(20) NOT NULL DEFAULT 'monthly',
  `status` VARCHAR(40) NOT NULL DEFAULT 'ACTIVE',
  `current_period_start` DATETIME NULL,
  `current_period_end` DATETIME NULL,
  `expiration_reminder_sent_at` DATETIME NULL,
  `grace_started_at` DATETIME NULL,
  `grace_ends_at` DATETIME NULL,
  `deactivated_at` DATETIME NULL,
  `renewed_at` DATETIME NULL,
  `reactivated_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_carelio_subscription_site` (`site_id`),
  KEY `idx_carelio_subscription_status` (`status`),
  KEY `idx_carelio_subscription_period_end` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Configuration Settings Table
CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(120) NOT NULL UNIQUE,
  `config_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Audit History & Event Log
CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_history` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `subscription_id` INT NULL,
  `action` VARCHAR(120) NOT NULL,
  `old_status` VARCHAR(40) NULL,
  `new_status` VARCHAR(40) NULL,
  `old_period_end` DATETIME NULL,
  `new_period_end` DATETIME NULL,
  `performed_by` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_carelio_sub_hist_sub` (`subscription_id`),
  KEY `idx_carelio_sub_hist_act` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. External Webhook Events Table
CREATE TABLE IF NOT EXISTS `mod_carelio_subscription_events` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `subscription_id` INT NULL,
  `event_type` VARCHAR(120) NOT NULL,
  `event_payload` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 3.3 Site Administrator Dashboard & Actions (`public/index.php`)

* **Navigation Path:** OpenEMR Navigation Menu ➔ **Administration ➔ Carelio Subscription**
* **Access Control:** Restricted via OpenEMR Core ACL `('admin', 'users')`. Regular clinicians and staff receive a read-only notification without modification controls.
* **Key Components:**
  1. **Status & Expiration Banner:** Real-time color-coded alerts (Green: Active, Yellow: Expiring Soon, Orange: Grace Period, Red: Deactivated).
  2. **Plan & Billing Metrics:** Plan Name, Monthly Fee, Start Date, Expiry Date, and Grace Window Countdown.
  3. **Site Admin Action Controls:**
     * **Renew Subscription (+1 Month):** Automatically calculates future date (+30 days/month), restores status to `ACTIVE`, and records audit history.
     * **Extend Expiration Date:** Interactive date picker allowing custom extension dates.
     * **Reactivate Subscription:** One-click instant reactivation for sites in `DEACTIVATED` state.
  4. **Interactive Simulator Toolbar:**
     * Dedicated testing toolbar allowing instant switching between all 4 statuses (`ACTIVE`, `EXPIRING_SOON`, `EXPIRED_GRACE_PERIOD`, `DEACTIVATED`) without waiting 30 days.
  5. **Subscription Audit History Table:**
     * Comprehensive immutable audit log tracking every action, status transition, timestamp, user, and notes.

---

### 3.4 Server-Side Access Enforcement (`SubscriptionEnforcementService.php`)

To prevent users from bypassing restrictions through direct URL bookmarks or REST API calls:
* Registered inside `openemr.bootstrap.php` on every authenticated web request.
* **Behavior when Status is `DEACTIVATED`:**
  * **Site Administrator:** Automatically redirected to `carelio_subscription/public/index.php` with reactivation controls enabled.
  * **Regular Staff / Clinicians:** HTTP 403 Forbidden page rendered with the Carelio "Account Deactivated" security template explaining that data is preserved and advising them to contact the Site Administrator.
  * Login, logout, assets, and subscription renewal paths are exempt from interception.

---

### 3.5 Automated Daily Monitoring Engine (`CarelioCheckSubscriptionsCommand.php`)

* **Command:** `php artisan carelio:check-subscriptions`
* **Execution Flow:**
  1. Scans all tenant databases across MySQL.
  2. Evaluates expiration timestamps against current system time.
  3. Detects subscriptions expiring within 2 days $\rightarrow$ updates status to `EXPIRING_SOON` and triggers renewal reminders.
  4. Detects expired subscriptions within 2-day window $\rightarrow$ updates status to `EXPIRED_GRACE_PERIOD`.
  5. Detects subscriptions expired past 2-day grace $\rightarrow$ sets status to `DEACTIVATED` and logs deactivation event.
  6. Renders live execution summary table in console/logs.

---

## 4. Live Server Deployment & Verification Guide

### Step 1: Push Local Branch & Pull on Live Server
On development workstation:
```bash
git add .
git commit -m "feat: complete subscription management module with monitoring, grace period, renewal, and background cron"
git push origin subscription-model
```

On live server terminal:
```bash
cd /path/to/carelioemr
git fetch origin
git checkout subscription-model
git pull origin subscription-model
```

### Step 2: Module Activation in OpenEMR UI
1. Sign in to OpenEMR as Super Administrator (`admin` / `pass`).
2. Navigate to: **Administration ➔ System ➔ Modules**.
3. Under the **Unregistered** tab: locate **Carelio Subscription** and click **Register**.
4. Under the **Registered** tab: click **Install SQL**, then click **Enable**.
*(Note: For newly provisioned tenants, `carelio_subscription` is pre-registered in the tenant baseline SQL).*

### Step 3: Configure Live Background Cron Job
Add the daily subscription monitoring process to the live server crontab:
```bash
crontab -e
```
Add the following entry:
```bash
0 0 * * * cd /path/to/carelioemr && php artisan carelio:check-subscriptions >> /var/log/carelio_sub_cron.log 2>&1
```

### Step 4: Live Verification Checklist

| Item | Test Action | Expected Result | Verified |
|---|---|---|:---:|
| 1 | Log in as `siteadmin` on live tenant | Menu contains **Administration ➔ Carelio Subscription** | [ ] |
| 2 | Open Subscription Dashboard | Displays Plan, Status Badge (`ACTIVE`), Expiry Date | [ ] |
| 3 | Click **"Test EXPIRING SOON"** | Banner turns Yellow, status becomes `EXPIRING_SOON` | [ ] |
| 4 | Click **"Test GRACE PERIOD"** | Banner turns Orange, grace window active | [ ] |
| 5 | Click **"Test DEACTIVATED"** | Banner turns Red, server-side guard engages | [ ] |
| 6 | Click **"Renew Subscription (+1 Month)"** | Status returns to `ACTIVE`, date advances +30 days | [ ] |
| 7 | Check Audit History Table | All test events logged with timestamps and actor | [ ] |
| 8 | Run CLI command | `php artisan carelio:check-subscriptions` outputs tenant table | [ ] |

---

## 5. Summary of Files Changed & Created

| File Path | Purpose |
|---|---|
| `oemr/interface/modules/custom_modules/carelio_subscription/ModuleManagerListener.php` | OpenEMR UI module lifecycle dispatcher (install, enable, upgrade, disable). |
| `oemr/interface/modules/custom_modules/carelio_subscription/openemr.bootstrap.php` | PSR-4 module autoloader, menu subscriber, and enforcement guard hook. |
| `oemr/interface/modules/custom_modules/carelio_subscription/public/index.php` | Full Site Administrator Subscription Dashboard, actions, simulator, and audit log UI. |
| `oemr/interface/modules/custom_modules/carelio_subscription/src/Services/SubscriptionManagerService.php` | Core business logic for renewals, extensions, status synchronization, and simulation. |
| `oemr/interface/modules/custom_modules/carelio_subscription/src/Security/SubscriptionEnforcementService.php` | Server-side request interceptor blocking access during deactivated status. |
| `oemr/interface/modules/custom_modules/carelio_subscription/src/Installer/SubscriptionInstaller.php` | Robust multi-context database installer and schema manager. |
| `oemr/interface/modules/custom_modules/carelio_subscription/table.sql` | Self-contained schema definition for the 4 subscription tables and defaults. |
| `app/Console/Commands/CarelioCheckSubscriptionsCommand.php` | Automated daily artisan command to check expirations, grace periods, and deactivations. |
| `oemr/interface/modules/custom_modules/site_admin_config/public/change_temporary_password.php` | Secure temporary password update with clean login redirection. |
| `oemr/interface/modules/custom_modules/site_admin_config/ModuleManagerListener.php` | Added upgrade and upgrade_sql lifecycle hooks. |

