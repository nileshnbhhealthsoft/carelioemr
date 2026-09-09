# CarelioEMR Cloud Healthcare SaaS Platform & Multi-Tenant Provisioner

Welcome to the **CarelioEMR** source codebase. This repository contains the complete single-port Laravel SaaS application, Stripe checkout integration, admin portal, dual email receipt system, and automated multi-tenant site provisioner.

---

## 🌟 Platform Highlights

* **Single-Port Architecture**: Runs entirely on a single Laravel port (`http://localhost:8000/` or production domain). Both frontend landing pages and backend APIs are handled directly by Laravel Blade templates and controllers.
* **Automated Multi-Tenancy**: Automatically provisions isolated site folders, databases, and database configuration upon checkout completion.
* **Restricted Role Access Control**: Seeds primary tenant users as **Clinic Admin / Practice Manager** with Super-Admin rights explicitly restricted via phpGACL rules.
* **Stripe Payment Gateway**: Handles **$80/month** billing with automatic draft cleanup and secure checkout.
* **Dual Gmail SMTP Dispatch**: Sends responsive HTML confirmation receipts to **Customers** and live notification alerts to **Admin**.
* **Admin Management Console**: Dedicated portal (`/admin/login` & `/admin/dashboard`) displaying subscriber tables, MRR metrics, search/filters, and Stripe logs.

---

## 🚀 Server Deployment & Setup Steps

### Prerequisites
* **PHP**: `>= 8.2` (Extensions required: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON)
* **Web Server**: Apache / Nginx or PHP Built-in Server
* **Database**: MySQL 8.0+ / MariaDB 10.5+
* **Composer**: Latest Composer 2.x

---

### Step 1: Database Setup (phpMyAdmin / MySQL)
1. Open phpMyAdmin or MySQL CLI on your server: `http://localhost/phpmyadmin/`
2. Create a new database named **`auraemr`**:
   ```sql
   CREATE DATABASE IF NOT EXISTS `auraemr` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the provided SQL dump file **`auraemr_database_dump.sql`** into the `auraemr` database.

---

### Step 2: Install Dependencies & Setup Environment
1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Copy the environment file and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Ensure your `.env` file contains your database credentials:
   ```env
   APP_NAME="CarelioEMR API"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   # MySQL Database Configuration
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=auraemr
   DB_USERNAME=root
   DB_PASSWORD=

   # Session Driver
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   ```

---

### Step 3: Clear Cache & Launch Server
Run the following Artisan commands:

```bash
php artisan config:clear
php artisan route:clear
php artisan serve --host=127.0.0.1 --port=8000
```

The application will now be fully live on: **`http://localhost:8000/`**

---

### Step 4: One-Click Startup (Windows)
For quick local development on Windows, double-click:
```cmd
start-servers.bat
```

---

## 🔑 Default Credentials & Live URLs

* **SaaS Landing Page**: `http://localhost:8000/`
* **Admin Login Portal**: `http://localhost:8000/admin/login`
  * **Email**: `admin@carelioemr.com`
  * **Password**: `admin123`
* **Admin Dashboard**: `http://localhost:8000/admin/dashboard`
* **Tenant Workstation Portal**: `http://localhost:8000/tenant/{tenant_slug}`

---

## 🛠️ Multi-Tenant Provisioning Mechanism

When a customer completes a subscription on the landing page:
1. Stripe PaymentIntent is confirmed (`payment_status = 'succeeded'`).
2. Laravel dispatches `ProvisionOpenEmrTenantJob` asynchronously.
3. Automatically creates isolated tenant site directories and databases.
4. Auto-generates configuration with database login credentials.
5. Imports baseline schema and seeds Practice Manager credentials.
6. Dispatches confirmation emails to **Customer** and **Admin**.

---

&copy; 2026 CarelioEMR Cloud Healthcare Platform. All rights reserved.
