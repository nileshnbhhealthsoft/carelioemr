# AuraEMR Cloud & Multi-Tenant OpenEMR Platform

Welcome to the **AuraEMR Cloud Multi-Tenant Platform**. This project is a complete single-port Laravel 12 multi-tenant EHR management platform integrated with genuine **OpenEMR 8.3.0** multi-tenancy.

---

## 🚀 Deployment & Installation Steps

### 1. Prerequisites
- **PHP**: 8.2+ with `pdo_mysql`, `openssl`, `curl`, `mbstring`, `gd` extensions enabled.
- **MySQL Server**: Running on `127.0.0.1:3306` (Username: `root`, Password: ``).
- **Composer**: PHP dependency manager.

### 2. Database Setup
1. Import the main platform database:
   ```bash
   mysql -u root -p < auraemr_database_dump.sql
   ```
2. Make sure `.env` contains:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=auraemr
   DB_USERNAME=root
   DB_PASSWORD=
   SESSION_DRIVER=database
   ```

### 3. Create Public OpenEMR Directory Junction (Windows)
```cmd
mklink /J "public\openemr" "openemr"
```

### 4. Start Server
Run the single-port Laravel application server:
```bash
php artisan serve --port=8000
```

---

## 📌 Main Portals & Access URLs

- **Public Landing Page**: `http://localhost:8000/`
- **Admin Login**: `http://localhost:8000/admin/login`
  - **Email**: `admin@auraemr.com`
  - **Password**: `password`
- **Admin Dashboard**: `http://localhost:8000/admin/dashboard`
- **Confirmed Tenant Workstation (Dr. Radha)**:
  - **Tenant URL**: `http://localhost:8000/openemr/sites/site_dr_radha_7`
  - **Direct Login**: `http://localhost:8000/openemr/interface/login/login.php?site=site_dr_radha_7`
  - **Username**: `dr_radha`
  - **Password**: `ClinicPass123!`
  - **Tenant Database**: `openemr_site_dr_radha_7`

---

## ⚙️ Multi-Tenant OpenEMR Provisioning Architecture

1. **Automatic Tenant Directory**: Clones `openemr/sites/default` into `openemr/sites/{tenant_slug}`.
2. **Automatic Isolation**: Creates dedicated MySQL database `openemr_{tenant_slug}` and auto-binds `sqlconf.php`.
3. **Full Schema Import**: Automatically imports complete OpenEMR 8.3.0 baseline schema (`database.sql`).
4. **phpGACL ACL Restrictive Seeding**: Dynamically seeds Clinic Admin / Practice Manager credentials with restricted rights (No Super-Admin).
5. **Zero Core Modification**: Core OpenEMR files remain untouched.
