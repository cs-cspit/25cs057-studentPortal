================================================================================
STUDENTHUB — PRACTICAL 14: ADMIN DASHBOARD WITH RBAC, DYNAMIC MENU & AUDIT LOG
Course: ITUE203 — Web Development Frameworks (WDF)
Institution: CSPIT — CSE, Charotar University of Science and Technology (CHARUSAT)
Student ID: 25CS057 | Academic Year: 2026-27 | Semester: 3rd
================================================================================

1. PRACTICAL OVERVIEW
---------------------
Practical 14 develops a secure, institutional Administrator Dashboard with server-side
Role-Based Access Control (RBAC), a role-aware dynamic navigation menu system, relational
JOIN queries for live campus event registrations, an authoritative audit trail system,
an interactive user account status toggle (Active/Inactive), and a multi-month
registration analytics chart.

2. CORE CAPABILITIES & IMPLEMENTATION
-------------------------------------
1. Server-Side Role-Based Access Control (RBAC):
   - Only users with role 'Admin' are authorized to access administrative interfaces
     (admin_dashboard.php, audit_logs.php, user_management.php, user_status_toggle.php).
   - Authenticated student users attempting direct access are strictly halted with
     an authoritative HTTP 403 Forbidden status code and institutional denial template.
   - Unauthenticated visitors are automatically routed to login.php.

2. Dynamic Navigation Menu System:
   - Dynamic sidebar menu dynamically renders authorized links according to the
     authenticated user's server session role.
   - ADMIN Role Menu: Dashboard, Student Management (CRUD), Event Management,
     Registrations, User Management, Audit Logs, Logout.
   - STUDENT Role Menu: Student Dashboard, My Profile, Campus Events, My Registrations, Logout.
   - Administrative links and controls are never exposed to student accounts.

3. Authoritative Audit Trail System (audit_logs):
   - Records crucial security and administrative operations with timestamp, actor ID,
     username, action type, detailed description, client IP address, and status.
   - Covered events: LOGIN, LOGOUT, CREATE_STUDENT, UPDATE_STUDENT, DELETE_STUDENT,
     CREATE_EVENT, UPDATE_EVENT, DELETE_EVENT, USER_STATUS_TOGGLE, UNAUTHORIZED_ACCESS_ATTEMPT.

4. Dynamic Database Statistics:
   - Total registered users, total students, total campus events, and total registrations
     are dynamically calculated using SQL aggregate queries (COUNT, SUM) from MySQL.

5. Relational Registrations Feed:
   - Uses SQL INNER JOINs linking registrations, students, and events tables to present
     real-time enrollment records, student names, enrollment codes, and event titles.

6. Intermediate Extension — User Status Management:
   - Adds an ENUM('Active', 'Inactive') status column to the users table.
   - Administrators can toggle user status asynchronously with 1-click controls.
   - Deactivated users are blocked from logging in with an explicit security alert.
   - Every status change is recorded in audit_logs.

7. Advanced Extension — Monthly Registration Analytics:
   - Queries registrations grouped by month and displays a trend chart
     using real database records.

3. TEST CREDENTIALS
-------------------
• Administrator Account:
  - Username: admin_user (or admin@studenthub.edu)
  - Password: Admin@123
  - Role: Admin
  - Access: Full Administrative Portal & System Console

• Student Account:
  - Username: student_user (or student@studenthub.edu)
  - Password: Student@123
  - Role: Student
  - Access: Student Portal & Read-Only Academic Views

4. DATABASE SETUP & MIGRATION
-----------------------------
1. Start Apache and MySQL services in the XAMPP Control Panel.
2. Open phpMyAdmin (http://localhost/phpmyadmin) or MySQL CLI:
   mysql -u root
3. Verify or import the baseline schema (schema.sql).
4. Apply the Practical 14 migration script:
   mysql -u root studenthub_db < database/practical14_migration.sql

5. ACCESSING THE APPLICATION
----------------------------
• Application URL: http://localhost/p14/ (or http://localhost/p15/)
• Login Portal: http://localhost/p14/login.php
• Admin Dashboard: http://localhost/p14/admin_dashboard.php
• Security Audit Logs: http://localhost/p14/audit_logs.php
• User Management Console: http://localhost/p14/user_management.php
• Student Portal: http://localhost/p14/student_dashboard.php

6. VERIFICATION & AUTOMATED TESTING
-----------------------------------
Run the complete automated Puppeteer test suite:
  node test_and_capture.js

Screenshots generated in screenshots/:
  • figure1_admin_login_dashboard.png
  • figure2_dashboard_statistics_cards.png
  • figure3_latest_registrations_table.png
  • figure4_dynamic_admin_sidebar_menu.png
  • figure5_student_dashboard_dynamic_menu.png
  • figure6_student_access_denied_403.png
  • figure7_audit_logs_management_page.png
  • figure8_student_event_operation_audit_entry.png
  • figure9_user_status_toggle_action.png
  • figure10_monthly_analytics_chart.png

7. PRACTICAL REPORT DOCUMENT
----------------------------
The official Microsoft Word practical submission report is generated at:
Practical_14_Admin_Dashboard_RBAC_Report.docx
================================================================================
