# CarelioEMR (AuraEMR) Cloud Healthcare Platform & Provisioner

Welcome to the **CarelioEMR (AuraEMR)** Cloud Healthcare Platform codebase. This repository contains the complete React frontend application, Laravel SaaS backend, Stripe checkout integration, admin management portal, dual email receipt dispatch, and automated multi-tenant site provisioner.

---

## 🌟 Platform Overview

* **Modern React Frontend**: Interactive EHR SaaS landing page, region-specific pricing, Stripe Elements checkout modal, and admin portal.
* **Laravel Backend API**: RESTful API endpoints for payment intent generation, subscriber management, authentication, and background job queuing.
* **Automated Multi-Tenancy**: Automatically provisions isolated tenant sites, databases, and database configuration upon checkout completion.
* **Stripe Payment Gateway**: Handles monthly billing and payment confirmation.
* **Admin Management Console**: Dedicated portal (`/admin/login` & `/admin/dashboard`) displaying subscriber tables, MRR metrics, search/filters, and Stripe logs.

---

## 🚀 Quick Setup & Installation

### Prerequisites
* **Node.js**: `>= 18.x` / `20.x`
* **PHP**: `>= 8.2` (with PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON extensions enabled)
* **MySQL / MariaDB**: `8.0+` / `10.5+`
* **Composer**: `2.x`

---

### Step 1: Database Setup
1. Open phpMyAdmin or MySQL CLI:
   ```sql
   CREATE DATABASE IF NOT EXISTS `auraemr` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the provided SQL dump file **`auraemr_database_dump.sql`** into the `auraemr` database.

---

### Step 2: Backend Setup (Laravel)
1. Navigate to the backend directory:
   ```bash
   cd backend
   composer install
   ```
2. Configure environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Update `backend/.env` with your database credentials (`DB_DATABASE=auraemr`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT`).
4. Clear cache and start the Laravel server:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan serve --host=127.0.0.1 --port=8000
   ```

---

### Step 3: Frontend Setup (React + Vite)
1. From the project root directory:
   ```bash
   npm install
   ```
2. Copy environment file:
   ```bash
   cp .env.example .env
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
   The React frontend will be accessible at: `http://localhost:3000`

---

### Step 4: One-Click Startup (Windows)
You can also start both the Laravel backend and React frontend simultaneously by running:
```cmd
start-servers.bat
```

---

## 🔑 Default Credentials & Live URLs

* **React Frontend (Main App)**: `http://localhost:3000`
* **Laravel API & Landing**: `http://localhost:8000`
* **Admin Login Portal**: `http://localhost:8000/admin/login`
  * **Email**: `admin@auraemr.com`
  * **Password**: `admin123`
* **Admin Dashboard**: `http://localhost:8000/admin/dashboard`

---

&copy; 2026 CarelioEMR / AuraEMR Cloud Healthcare Platform. All rights reserved.
