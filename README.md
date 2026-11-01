# StudentHub — Student Management Portal
### Practical 15: Final Integration of Practical 1–14, Testing, Documentation, and Local Deployment

---

## 1. Student & Institutional Details

- **Student Name:** Krupal Parmar
- **Student ID / Roll No:** 25CS057
- **University:** Charotar University of Science and Technology (CHARUSAT)
- **Faculty:** Faculty of Technology and Engineering (FTE)
- **Institute:** Chandubhai S. Patel Institute of Technology (CSPIT)
- **Department:** Department of Computer Science & Engineering (CSE)
- **Subject:** Web Development Frameworks (ITUE203)
- **Semester:** 3rd Semester (Odd Academic Term 2026-27)
- **Practical Number:** Practical 15 (Final Integration)
- **GitHub Repository:** [cs-cspit/25cs057-studentPortal](https://github.com/cs-cspit/25cs057-studentPortal.git)
- **Branch:** `main`

---

## 2. Project Introduction

**StudentHub** is an enterprise-grade academic information and student lifecycle management portal engineered specifically for CSPIT, CHARUSAT. The platform unifies core higher-education workflows into one coherent, responsive, and secure web application.

Practical 15 represents the semester-long capstone integration milestone for Web Development Frameworks (ITUE203). Over the course of the semester, individual technical competencies were developed incrementally across Practical 1 through Practical 14. Practical 15 unifies all front-end interfaces, server-side business logic, asynchronous JSON API endpoints, MySQL relational databases, and enterprise security controls into a single production-ready portal.

---

## 3. Project Objectives

1. **Systematic Inspection & Preservation:** Audit all source code, assets, and SQL scripts from Practical 1 through Practical 14 to preserve existing functionality.
2. **Unified Presentation Tier:** Standardize all interfaces with modern Inter and JetBrains Mono typography, responsive CSS Grid/Flexbox layouts, and institutional branding.
3. **Enterprise Authentication & RBAC:** Enforce server-side Role-Based Access Control distinguishing Student and Administrator capabilities, with session fixation protection, 15-minute inactivity timeouts, and Remember-Me tokens.
4. **Asynchronous RESTful JSON API:** Implement zero-page-reload CRUD operations via `/api/records.php` and `/api/record.php` using the modern JavaScript Fetch API.
5. **Campus Event Management:** Provide full event publishing workflows with secure multi-format poster uploads (JPEG, PNG, WEBP), MIME verification, size limits, and capacity monitoring.
6. **Relational Database Integrity:** Maintain strict relational integrity across MySQL InnoDB tables (`users`, `students`, `events`, `registrations`, `audit_logs`, `remember_tokens`).
7. **Comprehensive Verification:** Execute an automated 20-point test suite on local XAMPP to verify all functionalities with 100% pass rate.
8. **Academic Documentation & GitHub Submission:** Generate professional Word documentation (`Practical_15_25CS057_Final_Integration_Report.docx`) with authentic screenshots, and upload the final code to the official GitHub repository.

---

## 4. Technologies Used

| Tier / Category | Technology / Library | Version / Specifications |
|---|---|---|
| **Operating System** | Microsoft Windows 11 / Windows 10 | 64-bit Architecture |
| **Web Server** | Apache HTTP Server (via XAMPP) | Version 2.4.58 (Win64) OpenSSL/3.1.3 |
| **Server-Side Runtime** | PHP (Object-Oriented MySQLi) | Version 8.2.12 |
| **Database Management** | MariaDB / MySQL (InnoDB Engine) | Version 10.4.32 (Collation: `utf8mb4_unicode_ci`) |
| **Client Frontend** | Semantic HTML5 & CSS3 | CSS Grid, Flexbox, Media Queries, Inter Fonts |
| **Client Scripting** | Vanilla JavaScript (ES6+) | Fetch API, DOM Manipulation, Async/Await |
| **Security & Cryptography** | PHP OpenSSL & Password Hashing | `PASSWORD_BCRYPT` (Cost: 10), CSRF Tokens, SHA-256 |
| **Test Automation** | Puppeteer Core with Microsoft Edge | Edge Headless (Blink Rendering Engine) |
| **Version Control** | Git | Version 2.52.0.windows.1 |

---

## 5. Practical 1–14 Integration Overview

Every practical completed throughout the semester has been systematically integrated into the StudentHub platform:

| Practical | Topic & Core Concept | Key Files & Artifacts | Integration Status |
|---|---|---|---|
| **Practical 1** | Project Initiation, Sitemap, Wireframes, and GitHub Setup | `README.md`, Git commit `2b5e789` | **Integrated** |
| **Practical 2** | Semantic HTML5 Elements, ARIA Landmarks, Academic Timetable, Attendance, Results | `attendance.php`, `results.php`, `timetable.php` | **Integrated** |
| **Practical 3** | Responsive CSS Grid & Flexbox, Media Queries, Institutional Design System | `style.css`, `assets/css/admin_dashboard.css` | **Integrated** |
| **Practical 4** | JavaScript DOM Manipulation, UI Interactivity, Dynamic Modal Controllers | `assets/js/admin_dashboard.js`, DOM controllers | **Integrated** |
| **Practical 5** | Registration Form with Client-Side Regex Validation & Error Messaging | `register.php`, client regex validation logic | **Integrated** |
| **Practical 6** | External JSON Data Rendering with Fetch API, Search & Category Filters | `help.php`, `data/faqs.json`, dynamic filtering | **Integrated** |
| **Practical 7** | PHP Form Processing, Server-Side Sanitization, and CSV/JSON Storage | `export.php`, `data/events_backup.json` | **Integrated** |
| **Practical 8** | MySQL Relational Schema Design, ER Modeling, Constraints, and DB Connection | `db.php`, `database/studenthub_final_database.sql` | **Integrated** |
| **Practical 9** | Secure Registration with Duplicate Checks, Bcrypt Hashing, and Audit Log | `register.php`, `password_hash()`, `users` insert | **Integrated** |
| **Practical 10** | Secure Login/Logout, Sessions, 15-Minute Timeout, Remember-Me Tokens | `login.php`, `authenticate.php`, `auth.php`, `timeout.php` | **Integrated** |
| **Practical 11** | Student Management CRUD, MySQLi Prepared Queries, and Excel CSV Export | `api_crud.php`, `export.php`, `students` table | **Integrated** |
| **Practical 12** | Campus Event Management, Multi-Format Poster Upload, and MIME Validation | `event_management.php`, `event_create.php`, `uploads/` | **Integrated** |
| **Practical 13** | PHP RESTful JSON API (`/api/records.php`) and Asynchronous Fetch CRUD | `api/records.php`, `api/record.php`, `api-crud.js` | **Integrated** |
| **Practical 14** | Admin Dashboard with Real-Time KPIs, Dynamic Menu, RBAC, and Audit Logs | `admin_dashboard.php`, `audit_logs.php`, `admin_menu.php` | **Integrated** |
| **Practical 15** | Final Integration, 20-Point Testing, Documentation, and GitHub Submission | Complete Portal, Word Report, Database SQL | **Completed** |

---

## 6. Features & Core Modules

### 6.1 Public Institutional Modules
- **Portal Landing Page (`index.php`):** Professional institutional hero section, dynamic MySQL metrics counter, upcoming campus events showcase, module breakdown, and responsive navigation.
- **About Us Page (`about.php`):** Complete system specifications, academic details for Krupal Parmar (25CS057), project objectives, and Practical 1–14 syllabus matrix.
- **Student Registration Portal (`register.php`):** Form capturing Full Name, Student ID, Department, Year, Username, Email, and Password with real-time regex validation, duplicate checks, Bcrypt hashing, and dual insert into `users` and `students` tables.
- **Institutional Login Portal (`login.php`):** Clean split-screen authentication portal with quick demo credential chips, remember-me token handling, inactivity timeout alerts, and role-based redirection.
- **Helpdesk & Knowledge Base (`help.php`):** Dynamic FAQ interface loading external JSON (`data/faqs.json`) via the Fetch API with instant keyword search and category filtering.

### 6.2 Student Academic Portal (`student_dashboard.php`)
- **Student Profile Banner:** Displays student name, enrollment ID, registered email, and department credentials.
- **Attendance Progress Tracker:** Subject-wise percentage progress bars (>92% overall) covering WDF, DSA, DBMS, and OS.
- **Semester Examination Results:** Course grades (AA, AB), credits, status, and current SGPA (9.15).
- **Weekly Class Timetable:** Day-by-day lecture and laboratory schedule for Room 304.
- **Event Registrations:** Status of campus event participations with dates, categories, and venues.
- **RBAC Security Inspector:** Live testing triggers verifying HTTP 403 Forbidden denial when attempting unauthorized admin access.

### 6.3 Administrator Management Modules
- **Executive Dashboard (`admin_dashboard.php`):** Authoritative database KPIs (Total Users, Students, Events, Registrations), monthly analytics chart, and latest registrations table.
- **Student Management Module (`api_crud.php` / `students.php`):** Asynchronous Fetch API CRUD engine with live search, department/status/year filtering, Add/Edit modals, and Excel CSV export.
- **Campus Event Console (`event_management.php`):** Publishing events, editing details, capacity tracking, and multi-format poster upload validation.
- **User Account Administration (`user_management.php`):** Role tracking and Active/Inactive user status toggling.
- **Security Audit Logs (`audit_logs.php`):** Comprehensive system audit trail with action filtering, status badges, IP tracking, and timestamp metadata.

---

## 7. Project Folder Structure

```
studentPortal/
│
├── index.php                 # Unified StudentHub Institutional Landing Portal
├── about.php                 # Project Specifications, Objectives & Academic Matrix
├── register.php              # Secure Student Registration (Bcrypt + Dual Insert)
├── login.php                 # Secure Institutional Authentication Portal
├── logout.php                # Session Termination & Cookie Revocation Handler
├── dashboard.php             # Role-Based Routing Gateway (Admin vs Student)
├── student_dashboard.php     # Student Academic Portal (Profile, Attendance, Timetable)
├── admin_dashboard.php       # Administrator Executive Dashboard (KPIs & Metrics)
├── students.php              # Student Management Router
├── api_crud.php              # Fetch API Asynchronous Student CRUD Console
├── event_management.php       # Campus Event Management Console
├── event_create.php          # Event Creation with Validated Poster Upload
├── event_edit.php            # Event Editing Interface
├── event_delete.php          # Event Deletion Controller
├── event_helpers.php         # Event File Validation & Utility Helpers
├── user_management.php       # User Account Administration Console
├── user_status_toggle.php    # Active / Inactive Status Toggle Controller
├── audit_logs.php            # System Audit Trail & Security Log Viewer
├── help.php                  # Interactive Helpdesk & FAQ Portal (Fetch API)
├── export.php                # RFC-4180 Excel CSV Export Engine
├── db.php                    # Centralized MySQLi Database Singleton Handler
├── auth.php                  # Authentication Verification & RBAC Guards
├── authenticate.php          # Authentication Processing & Session Initialization
├── timeout.php               # 15-Minute Session Inactivity Invalidation Engine
├── style.css                 # Primary Institutional Portal Stylesheet
│
├── api/                      # RESTful PHP JSON API Endpoints
│   ├── records.php           # Collection Endpoint (GET list & POST create)
│   ├── record.php            # Resource Endpoint (GET, PUT, DELETE single record)
│   └── db.php                # API Connection Handler & JSON Response Helper
│
├── includes/                 # Reusable Server Components
│   ├── admin_menu.php        # Dynamic Role-Gated Navigation Menu System
│   ├── audit_logger.php      # Centralized Security Audit Logging Helper
│   ├── auth.php              # Role Checking & Authorization Utilities
│   └── db.php                # Shared Database Utilities
│
├── assets/                   # Static Assets
│   ├── css/
│   │   ├── admin_dashboard.css  # Executive Portal & Dashboard Layout Styles
│   │   └── api-crud.css         # Fetch API CRUD Table & Modal Dialog Styles
│   └── js/
│       ├── admin_dashboard.js   # Sidebar Toggle & Dashboard Event Listeners
│       └── api-crud.js          # Fetch API CRUD Engine, Filters & Modal Handlers
│
├── data/                     # External JSON Resources (Practicals 6 & 7)
│   ├── faqs.json             # Knowledge Base FAQs (Question, Answer, Category)
│   ├── students.json         # Static Student Seed Dataset
│   └── events_backup.json    # Static Events Seed Dataset
│
├── logos/                    # University & Institutional Branding
│   ├── charusat_logo.jpeg    # Charotar University Logo
│   └── cspit_logo.jpeg       # CSPIT Institute Logo
│
├── uploads/                  # User Upload Storage
│   └── event_posters/        # Verified & Validated Campus Event Poster Images
│
├── database/                 # Database Schema & Data Dumps
│   └── studenthub_final_database.sql # Complete MariaDB / MySQL Export Dump
│
├── screenshots/              # 19 Authentic Verification Screenshots
│   ├── 01_home_page.png
│   ├── 02_about_page.png
│   ├── 03_registration_form.png
│   ├── 04_registration_success.png
│   ├── 05_duplicate_registration_rejected.png
│   ├── 06_student_dashboard.png
│   ├── 07_admin_dashboard.png
│   ├── 08_invalid_login_error.png
│   ├── 09_student_access_denied_403.png
│   ├── 10_student_management_table.png
│   ├── 11_event_management_console.png
│   ├── 12_poster_upload_form.png
│   ├── 13_api_records_json.png
│   ├── 14_live_search_filter.png
│   ├── 15_session_timeout_alert.png
│   ├── 16_logout_success.png
│   ├── 17_audit_logs_table.png
│   ├── 18_mobile_responsive_home.png
│   └── 19_protected_url_redirect.png
│
├── reports/                  # Official Academic Submissions
│   └── Practical_15_25CS057_Final_Integration_Report.docx # Word Report
│
├── test_and_capture.js       # Puppeteer Headless Automated Test Suite
├── generate_report.ps1       # Word Document COM Automation Generator
├── test_results.json         # Automated Test Verification Results Output
├── .gitignore                # Git Exclusion Configuration
└── README.md                 # Complete System Documentation
```

---

## 8. Database Schema Overview

The relational database `studenthub_db` comprises six core tables with primary keys, indexes, and foreign key constraints:

```
  ┌─────────────────────────────────────────────────────────────┐
  │                           users                             │
  ├─────────────────────────────────────────────────────────────┤
  │ id (PK)                                                     │
  │ username (UNIQUE)                                           │
  │ email (UNIQUE)                                              │
  │ password_hash (BCRYPT)                                      │
  │ role ('Admin', 'Student')                                   │
  │ status ('Active', 'Inactive')                               │
  │ full_name                                                   │
  │ department                                                  │
  │ student_id_code (FK/Ref to students.student_id)             │
  │ last_login                                                  │
  │ created_at, updated_at                                      │
  └──────────────┬──────────────────────────────┬───────────────┘
                 │ 1:N                          │ 1:N
                 ▼                              ▼
  ┌──────────────────────────────┐ ┌────────────────────────────┐
  │       remember_tokens        │ │         audit_logs         │
  ├──────────────────────────────┤ ├────────────────────────────┤
  │ id (PK)                      │ │ id (PK)                    │
  │ user_id (FK -> users.id)     │ │ user_id (FK -> users.id)   │
  │ selector (UNIQUE)            │ │ action (LOGIN, REGISTER..) │
  │ token_hash (SHA-256)         │ │ description                │
  │ expires_at                   │ │ username, email, status    │
  │ user_agent                   │ │ ip_address, user_agent     │
  │ created_at                   │ │ created_at                 │
  └──────────────────────────────┘ └────────────────────────────┘

  ┌──────────────────────────────┐ ┌────────────────────────────┐
  │           students           │ │           events           │
  ├──────────────────────────────┤ ├────────────────────────────┤
  │ id (PK)                      │ │ id (PK)                    │
  │ student_id (UNIQUE)          │ │ title, category, status    │
  │ name, email (UNIQUE)         │ │ description, poster_path   │
  │ department, course, year     │ │ event_date, venue          │
  │ status ('Active', 'Inactive')│ │ capacity                   │
  │ created_at, updated_at       │ │ created_at, updated_at     │
  └──────────────┬───────────────┘ └─────────────┬──────────────┘
                 │ 1:N                           │ 1:N
                 └──────────────┐ ┌──────────────┘
                                ▼ ▼
                 ┌──────────────────────────────┐
                 │        registrations         │
                 ├──────────────────────────────┤
                 │ registration_id (PK)         │
                 │ student_id (FK -> students)  │
                 │ event_id (FK -> events.id)   │
                 │ registration_date            │
                 │ status, notes                │
                 └──────────────────────────────┘
```

---

## 9. Local Deployment via XAMPP

Follow these steps to deploy and execute StudentHub on your local machine:

### Step 1: Install XAMPP
Ensure [XAMPP for Windows](https://www.apachefriends.org/) with **PHP 8.2+** and **MySQL / MariaDB** is installed (default path: `C:\xampp`).

### Step 2: Clone or Mount Project Directory
Place the repository into your preferred workspace directory (e.g., `D:\WDF\final project`).
To serve the project directly through Apache, open **PowerShell as Administrator** and create a directory junction:
```powershell
cmd /c mklink /J "C:\xampp\htdocs\p15" "D:\WDF\final project"
```
*(Alternatively, copy the contents of `final project` into `C:\xampp\htdocs\p15`)*.

### Step 3: Start Apache & MySQL Services
Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.
*(Or run `C:\xampp\apache_start.bat` and `C:\xampp\mysql_start.bat`)*.

### Step 4: Import Database Schema & Data
1. Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Create a new database named `studenthub_db` with collation `utf8mb4_unicode_ci`.
3. Click the **Import** tab, select `database/studenthub_final_database.sql`, and click **Import**.
*(Alternatively via Command Prompt)*:
```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS studenthub_db;"
& 'C:\xampp\mysql\bin\mysql.exe' -u root studenthub_db < "D:\WDF\final project\database\studenthub_final_database.sql"
```

### Step 5: Verify Connection Configuration
Verify `db.php` settings. Default XAMPP credentials work out of the box:
```php
$dbConfig = [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'studenthub_db',
    'user'     => 'root',
    'password' => '', // Default XAMPP blank password
    'charset'  => 'utf8mb4'
];
```

### Step 6: Access the Website
Open your browser and navigate to:
**[http://localhost/p15/index.php](http://localhost/p15/index.php)**

---

## 10. Login & Role Credentials

The database contains pre-seeded baseline institutional accounts for evaluation:

| User Role | Username / Identifier | Registered Email | Password | Assigned Permissions |
|---|---|---|---|---|
| **Administrator** | `admin_user` | `admin@studenthub.edu` | `Admin@123` | Executive Dashboard, Full Student CRUD, Event Management, Poster Uploads, User Status Toggle, Security Audit Logs |
| **Student** | `student_user` | `student@studenthub.edu` | `Student@123` | Student Academic Dashboard, Attendance Progress, Results & Grades, Timetable, Event Registrations, Helpdesk |

*Tip: Click the quick-fill credential chips on the login page (`login.php`) to automatically populate test credentials.*

---

## 11. Automated Testing & Verification Results

All 20 test checklist cases were verified using an automated headless test suite (`test_and_capture.js`):

| No. | Test Case | Expected Result | Actual Result | Status |
|---|---|---|---|---|
| **1** | Open Home Page | Website loads with HTTP 200 & portal branding | Status 200, StudentHub title & DB metrics verified | **PASS** |
| **2** | Navigation | All primary navigation links are present & valid | 5 primary routes verified (Home, About, Features, Events, Help) | **PASS** |
| **3** | About Page | Displays project specifications & student details | Status 200, Krupal Parmar (25CS057) credentials verified | **PASS** |
| **4** | Student Registration | Valid registration is saved to MySQL database | Success banner rendered, record inserted into users & students | **PASS** |
| **5** | Duplicate Registration | Duplicate entry is rejected by server | Duplicate username/email error safely returned | **PASS** |
| **6** | Student Login | Student dashboard opens upon successful login | Redirected to `student_dashboard.php` | **PASS** |
| **7** | Admin Login | Admin dashboard opens upon admin authentication | Redirected to `admin_dashboard.php` | **PASS** |
| **8** | Invalid Login | Proper error message appears for wrong credentials | "Authentication Failed" notification displayed | **PASS** |
| **9** | Student Access to Admin Page | Access is denied with HTTP 403 Forbidden | HTTP 403 Forbidden returned and unauthorized attempt logged | **PASS** |
| **10** | Admin Student Management | Authorized operations work (Table + Add Student) | Student directory loaded with full administrative controls | **PASS** |
| **11** | Event Management | Event operations console displays active events | Event management console rendered with capacity & status | **PASS** |
| **12** | Poster Upload | File input with validation controls is functional | Poster upload enforces MIME check (JPEG/PNG/WEBP) & 2MB limit | **PASS** |
| **13** | Fetch API | Correct JSON response structure is returned | `/api/records.php` returned standard JSON with 20 records | **PASS** |
| **14** | AJAX CRUD | Supported search/filter works without page reload | Table rows updated dynamically in DOM via Fetch API | **PASS** |
| **15** | Session Timeout | Inactive session triggers timeout invalidation | 15-minute inactivity threshold enforced with timeout alert | **PASS** |
| **16** | Logout | Session is destroyed and user redirected to login | Session cleared, cookie revoked, and logout alert confirmed | **PASS** |
| **17** | Audit Logs | Relevant administrative activities are recorded | Logins, registrations, and RBAC denials logged without passwords | **PASS** |
| **18** | Database | Data is stored and retrieved correctly from MySQL | Relational integrity verified across all 6 database tables | **PASS** |
| **19** | Responsive Layout | Website layout adapts seamlessly to mobile screens | Viewport 375x812 verified with mobile-friendly reflow | **PASS** |
| **20** | Protected URLs | Unauthorized guest access is prevented and gated | Unauthenticated access redirected to `login.php?error=login_required` | **PASS** |

**Summary: 20 Tests Executed | 20 Passed (100% Pass Rate) | 0 Failed**

---

## 12. Screenshots & Artifacts

All authentic full-resolution screenshots are located in the `screenshots/` directory:
- `screenshots/01_home_page.png` — Portal Home Page with Dynamic MySQL Metrics
- `screenshots/02_about_page.png` — About Us Page with Student Credentials & Practical Matrix
- `screenshots/03_registration_form.png` — Student Registration Portal Form
- `screenshots/04_registration_success.png` — Registration Success Confirmation
- `screenshots/05_duplicate_registration_rejected.png` — Duplicate Registration Rejection Notice
- `screenshots/06_student_dashboard.png` — Student Academic Dashboard (Attendance, Results, Timetable)
- `screenshots/07_admin_dashboard.png` — Admin Executive Dashboard with Dynamic KPIs
- `screenshots/08_invalid_login_error.png` — Invalid Authentication Defense Alert
- `screenshots/09_student_access_denied_403.png` — Role-Based Access Control HTTP 403 Forbidden Denial
- `screenshots/10_student_management_table.png` — Live Fetch API Student Management Console
- `screenshots/11_event_management_console.png` — Campus Events Management Console
- `screenshots/12_poster_upload_form.png` — Event Poster Upload & Validation Interface
- `screenshots/13_api_records_json.png` — RESTful PHP JSON API Endpoint Output
- `screenshots/14_live_search_filter.png` — AJAX Live Search & Filter Demonstration
- `screenshots/15_session_timeout_alert.png` — 15-Minute Session Inactivity Timeout Alert
- `screenshots/16_logout_success.png` — Secure Logout Confirmation
- `screenshots/17_audit_logs_table.png` — System Security Audit Trail Console
- `screenshots/18_mobile_responsive_home.png` — Mobile Responsive Layout (375x812 Viewport)
- `screenshots/19_protected_url_redirect.png` — Protected URL Unauthenticated Redirection

---

## 13. Academic Report

The official practical report has been compiled in Microsoft Word format matching university guidelines:
- **Location:** `reports/Practical_15_25CS057_Final_Integration_Report.docx` (and root `Practical_15_25CS057_Final_Integration_Report.docx`)
- **Features:** Institutional cover header, syllabus alignment, practical matrix, database schema description, security architecture, 20-point testing checklist, and all 19 embedded verification screenshots.

---

## 14. Conclusion

Practical 15 successfully marks the culmination of the **Web Development Frameworks (ITUE203)** laboratory curriculum. By synthesizing modern client-side design (Semantic HTML5, CSS Grid/Flexbox, JavaScript Fetch API) with enterprise PHP server architectures, relational MySQL modeling, and strict security protocols (Bcrypt hashing, CSRF protection, session timeouts, server-side RBAC, and audit trails), StudentHub demonstrates a robust, production-grade university management portal ready for institutional deployment.
