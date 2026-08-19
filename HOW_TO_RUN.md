# FurEscue — How to Run the System

Step-by-step guide to install, configure, and run the **FurEscue** rescue-management system on a fresh Windows machine.

---

## 1. What you need to install (with download links)

| # | Software | Version | Why | Download |
|---|----------|---------|-----|----------|
| 1 | **PHP** | 8.1+ (recommended 8.2/8.3) | Runs the REST API backend | https://windows.php.net/download/ → "VS16 x64 Non Thread Safe" ZIP |
| 2 | **MySQL** | 8.0.13+ (any 8.x) | Database (schema uses `UUID()` defaults) | https://dev.mysql.com/downloads/installer/ → MySQL Community Server |
| 3 | **Composer** | Latest (2.x) | Installs backend PHP dependencies | https://getcomposer.org/download/ |
| 4 | **Node.js + npm** | 18+ (LTS) | Compiles the frontend Tailwind CSS | https://nodejs.org/ |
| 5 | **Git** *(optional)* | Latest | Clone the repository | https://git-scm.com/ |

---

## 2. Step-by-step setup

### Step 1 — Install PHP and add to PATH
1. Download the **Non Thread Safe** ZIP from https://windows.php.net/download/.
2. Extract to a folder, e.g. `C:\php`.
3. In the folder, copy `php.ini-development` → `php.ini`.
4. Edit `C:\php\php.ini` and uncomment these lines (remove the leading `;`):
   ```ini
   extension=mysqli
   extension=pdo_mysql
   extension=openssl
   extension=mbstring
   extension=curl
   ```
   Also set:
   ```ini
   extension_dir = "ext"
   ```
5. Add `C:\php` to the system **PATH**:
   - `Win + R` → `sysdm.cpl` → **Advanced** → **Environment Variables** → under **Path** → **Edit** → **New** → `C:\php` → OK.
6. Open a **new** terminal and verify:
   ```bat
   php -v
   ```

### Step 2 — Install MySQL
1. Run the MySQL Installer and choose **MySQL Server** (8.x) + **MySQL Workbench** (optional GUI).
2. Set a **root password** you will remember.
3. During/after install, note the port (default **3306**).

### Step 3 — Install Composer
1. Run the Composer setup installer.
2. After install, verify in a new terminal:
   ```bat
   composer --version
   ```

### Step 4 — Install Node.js
1. Run the Node.js installer (defaults are fine).
2. Verify in a new terminal:
   ```bat
   node -v
   npm -v
   ```

### Step 5 — Get the project
```bat
git clone <your-repo-url> Furescue
cd Furescue
```
*(If you already have the project folder, just open it.)*

### Step 6 — Create the database and user
Open **MySQL** (Command Line Client or Workbench) and run:

```sql
CREATE DATABASE furescue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'furescue'@'localhost' IDENTIFIED BY 'furescue_pass';
GRANT ALL PRIVILEGES ON furescue.* TO 'furescue'@'localhost';
FLUSH PRIVILEGES;
```

### Step 7 — Configure the backend environment
1. Copy the example config:
   ```bat
   cd backend
   copy .env.example .env
   ```
2. Edit `backend\.env` and set the database credentials:
   ```ini
   DB_DRIVER=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=furescue
   DB_USER=furescue
   DB_PASS=furescue_pass

   APP_SECRET=change_me_app_secret
   JWT_SECRET=change_me_jwt_secret
   JWT_REFRESH_SECRET=change_me_refresh_secret
   ```
   *The Mati City geovalidation bounds and Google client ID are pre-filled and fine to keep.*

### Step 8 — Install backend dependencies
```bat
composer install
```
*(Requires internet. Installs `phpdotenv` + `firebase/php-jwt` + PHPUnit.)*

### Step 9 — Run database migrations
```bat
php bin\migrate.php
```
You should see each SQL file reported as `apply ...`. This creates all 16 tables.

### Step 10 — Seed the demo data (optional but recommended)
```bat
php seeders\seed.php
```
This creates the admin/rescuer/resident accounts and a full demo dataset (reports, cases, animals, adoptions, e-learning, notifications). It is **idempotent** — safe to re-run at any time.

### Step 11 — Start the backend API
```bat
php -S 127.0.0.1:8000 -t public public\index.php
```
Keep this terminal open. Verify the API is up by hitting the login endpoint:
```bat
curl -X POST http://localhost:8000/api/v1/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@furescue.local\",\"password\":\"Password123!\"}"
```
A successful response returns `{"success":true,"data":{...,"token":...}}`.

### Step 12 — Build the frontend
In a **second** terminal:
```bat
cd frontend
npm install
npm run build
```
`npm run build` compiles `css/input.css` → `css/style.css`. Use `npm run watch` if you will edit styles.

### Step 13 — Open the system
The frontend is static HTML that calls `http://localhost:8000/api/v1`. Open in your browser:

| Page | URL |
|------|-----|
| Landing page | `frontend\landing\index.html` |
| Login / Register | `frontend\auth\login.html` |
| **Admin dashboard** | `frontend\admin\index.html` |

*(If you prefer URLs, serve the `frontend` folder, e.g. `php -S 127.0.0.1:8080 -t frontend` and open `http://localhost:8080/admin/index.html`.)*

---

## 3. Demo accounts

All seeded accounts use the password **`Password123!`**:

| Role | Email | Notes |
|------|-------|-------|
| Admin | `admin@furescue.local` | Full dashboard access |
| Rescuer (active) | `rescuer@furescue.local` … `rescuer5@furescue.local` | On-duty |
| Rescuer (pending) | `rescuer6@furescue.local`, `rescuer7@furescue.local` | Pending approval |
| Resident | `juan@furescue.local`, `maria@furescue.local`, `ana@furescue.local`, `pedro@furescue.local`, `rosa@furescue.local`, `miguel@furescue.local` | |

---

## 4. Common problems & fixes

| Problem | Fix |
|---------|-----|
| `Cannot connect to the database` on migrate/seed | Check `.env` DB_* values, that MySQL is running, and the DB/user were created in Step 6. |
| `PHP Warning: Module "mysqli" is already loaded` | Harmless — appears on some setups, ignore it. |
| API returns `500 SERVER_ERROR` | Check `.env` secret values are set; run `php bin\migrate.php` again. |
| Map tiles blank | Requires internet access (Leaflet + OSM tiles load from CDN). |
| Styling looks unstyled | Run `npm run build` in `frontend` so `style.css` exists. |
| `composer` not recognized | Reinstall Composer and open a new terminal. |
| Port 8000 already in use | Change the port in Step 11 and update `frontend\js\lib\api.js` base URL to match. |

---

## 5. Useful commands (quick reference)

```bat
:: Backend
cd backend
composer install
php bin\migrate.php
php seeders\seed.php
php -S 127.0.0.1:8000 -t public public\index.php

:: Frontend
cd frontend
npm install
npm run build      :: one-time compile
npm run watch      :: auto recompile while editing
```
