<?php
/**
 * StudentHub — About Institutional Portal & Project Architecture
 * Practical 15: Final Integration of Practical 1–14, Complete Website & Local Deployment
 * 
 * Student: Krupal Parmar | Student ID: 25CS057
 * Institute: Chandubhai S. Patel Institute of Technology (CSPIT)
 * University: Charotar University of Science and Technology (CHARUSAT)
 * Subject: Web Development Frameworks (ITUE203)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$userRole    = $currentUser['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About StudentHub — Institutional System Specifications | CHARUSAT CSPIT</title>
    
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 70%, #2563eb 100%);
            color: #ffffff;
            padding: 56px 24px 64px;
            text-align: center;
        }
        .page-header-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .page-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #93c5fd;
            font-size: 12.5px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 9999px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .page-title {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 16px;
        }
        .page-subtitle {
            font-size: 16px;
            color: #cbd5e1;
            line-height: 1.6;
        }
        
        .about-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 48px 24px 80px;
        }

        .about-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .about-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-profile-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-top: 16px;
        }
        .profile-field-label {
            font-size: 11.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .profile-field-value {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Integration Table */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 13.5px;
        }
        .matrix-table th {
            background: #1e3a8a;
            color: #ffffff;
            text-align: left;
            padding: 12px 16px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .matrix-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: top;
        }
        .matrix-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .badge-verified {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
        }

        /* Nav styling */
        .site-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .site-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .site-brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }
        .logo-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-group img {
            height: 38px;
            border-radius: 4px;
            object-fit: contain;
        }
        .brand-name {
            font-size: 19px;
            font-weight: 800;
            color: #1e3a8a;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-links a {
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-links a:hover {
            color: #2563eb;
        }
        .btn-nav-login {
            background: #f1f5f9;
            color: #1e293b;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-nav-register {
            background: #2563eb;
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body style="margin:0;font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;">

    <!-- Top Navigation -->
    <header class="site-nav">
        <div class="site-nav-inner">
            <a href="index.php" class="site-brand-block">
                <div class="logo-group">
                    <?php if (file_exists(__DIR__ . '/logos/charusat_logo.jpeg')): ?>
                        <img src="logos/charusat_logo.jpeg" alt="CHARUSAT Logo">
                    <?php endif; ?>
                    <?php if (file_exists(__DIR__ . '/logos/cspit_logo.jpeg')): ?>
                        <img src="logos/cspit_logo.jpeg" alt="CSPIT Logo">
                    <?php endif; ?>
                </div>
                <div class="brand-text-block">
                    <span class="brand-name">StudentHub</span>
                    <span class="brand-sub">CHARUSAT &bull; CSPIT</span>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php" style="color:#2563eb;font-weight:600;">About Us</a></li>
                    <li><a href="index.php#features">Features</a></li>
                    <li><a href="index.php#events">Campus Events</a></li>
                    <li><a href="help.php">Helpdesk &amp; FAQs</a></li>
                </ul>
            </nav>

            <div style="display:flex;gap:10px;">
                <?php if ($currentUser): ?>
                    <a href="<?php echo ($userRole === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn-nav-register" style="background:#1e3a8a;">
                        Dashboard (<?php echo htmlspecialchars($userRole); ?>)
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-nav-login">Sign In</a>
                    <a href="register.php" class="btn-nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-container">
            <span class="page-badge">Academic Project Specifications &bull; Practical 15</span>
            <h1 class="page-title">About the StudentHub Portal</h1>
            <p class="page-subtitle">
                An integrated university portal designed and engineered as part of the Web Development Frameworks (ITUE203) course at CSPIT, CHARUSAT.
            </p>
        </div>
    </section>

    <!-- Main Content Area -->
    <main class="about-wrap">

        <!-- Student Details Card -->
        <div class="about-card">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Developer &amp; Academic Credentials
            </h3>
            <p style="color:#64748b;line-height:1.6;font-size:14px;">
                The StudentHub Portal represents the comprehensive capstone integration of Practical 1 through 14, fulfilling all curriculum requirements for Web Development Frameworks.
            </p>

            <div class="student-profile-strip">
                <div>
                    <div class="profile-field-label">Student Name</div>
                    <div class="profile-field-value">Krupal Parmar</div>
                </div>
                <div>
                    <div class="profile-field-label">Student ID</div>
                    <div class="profile-field-value" style="font-family:'JetBrains Mono',monospace;color:#2563eb;">25CS057</div>
                </div>
                <div>
                    <div class="profile-field-label">Department</div>
                    <div class="profile-field-value">Computer Science &amp; Engineering</div>
                </div>
                <div>
                    <div class="profile-field-label">Institute &amp; University</div>
                    <div class="profile-field-value">CSPIT &bull; CHARUSAT</div>
                </div>
                <div>
                    <div class="profile-field-label">Subject &amp; Code</div>
                    <div class="profile-field-value">Web Development Frameworks (ITUE203)</div>
                </div>
                <div>
                    <div class="profile-field-label">Semester &amp; Term</div>
                    <div class="profile-field-value">3rd Semester &bull; 2026-27 (Odd)</div>
                </div>
                <div>
                    <div class="profile-field-label">Practical Number</div>
                    <div class="profile-field-value">Practical 15 (Final Integration)</div>
                </div>
                <div>
                    <div class="profile-field-label">Official GitHub Repo</div>
                    <div class="profile-field-value" style="font-size:13px;word-break:break-all;">
                        <a href="https://github.com/cs-cspit/25cs057-studentPortal.git" target="_blank" style="color:#2563eb;text-decoration:none;">
                            cs-cspit/25cs057-studentPortal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Objectives Card -->
        <div class="about-card">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                Project Purpose &amp; Objectives
            </h3>
            <p style="color:#475569;line-height:1.7;font-size:14.5px;">
                The primary purpose of the <strong>StudentHub</strong> platform is to replace disparate academic utility scripts with a unified, enterprise-grade university portal. 
                Higher educational institutions require seamless coordination among administrative staff, faculty, and students. StudentHub fulfills these needs through:
            </p>
            <ul style="color:#475569;line-height:1.8;font-size:14px;margin-top:12px;padding-left:22px;">
                <li><strong>Unified Student Lifecycle Management:</strong> Administering student records with live search, filters, instant modal editing, soft deletions, and Excel CSV exports.</li>
                <li><strong>Campus Event Management:</strong> Publishing technical symposia and workshops with multi-format poster uploads, MIME type validation, and student registrations.</li>
                <li><strong>Enterprise-Grade Security:</strong> Cryptographic password hashing (Bcrypt), CSRF token defense, session timeout (15 mins), and persistent Remember-Me tokens.</li>
                <li><strong>Asynchronous RESTful JSON API:</strong> Providing standard HTTP verbs (GET, POST, PUT, DELETE) with Fetch API DOM updates to eliminate full page reloads.</li>
                <li><strong>Institutional Role-Based Access Control (RBAC):</strong> Strict separation between Student and Administrator capabilities with automatic HTTP 403 Forbidden logging.</li>
            </ul>
        </div>

        <!-- Comprehensive Integration Matrix Table -->
        <div class="about-card">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Practical 1 to 14 Integration Mapping
            </h3>
            <p style="color:#64748b;font-size:13.5px;margin-bottom:16px;">
                The table below outlines how each individual laboratory practical was integrated into the final StudentHub portal:
            </p>

            <div style="overflow-x:auto;">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th style="width:14%;">Practical</th>
                            <th style="width:34%;">Topic &amp; Implemented Functionality</th>
                            <th style="width:34%;">Associated Project Files</th>
                            <th style="width:18%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Practical 1</strong></td>
                            <td>Project Initiation, Sitemap, Low-Fidelity Wireframes, and GitHub Setup</td>
                            <td><code>README.md</code>, GitHub repository initial commit</td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 2</strong></td>
                            <td>Semantic HTML5 Layout, Accessibility ARIA Landmarks, Timetable, Attendance</td>
                            <td><code>attendance.php</code>, <code>results.php</code>, <code>timetable.php</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 3</strong></td>
                            <td>Responsive CSS Grid &amp; Flexbox, Breakpoint Media Queries, Cards Layout</td>
                            <td><code>style.css</code>, <code>assets/css/admin_dashboard.css</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 4</strong></td>
                            <td>JavaScript DOM Manipulation, UI Interactivity, Dynamic Event Listeners</td>
                            <td><code>assets/js/admin_dashboard.js</code>, modal controllers</td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 5</strong></td>
                            <td>Registration Form with Client-Side Validation and Real-Time Error Hints</td>
                            <td><code>register.php</code>, client validation regex scripts</td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 6</strong></td>
                            <td>External JSON Data Rendering with Fetch API, Category Search and Filtering</td>
                            <td><code>help.php</code>, <code>data/faqs.json</code>, <code>data/students.json</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 7</strong></td>
                            <td>PHP Form Processing, Server-Side Sanitization, and CSV/JSON File Storage</td>
                            <td><code>export.php</code>, <code>data/events_backup.json</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 8</strong></td>
                            <td>MySQL Schema Design, Relational ER Modeling, and Database Connectivity</td>
                            <td><code>db.php</code>, <code>database/studenthub_final_database.sql</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 9</strong></td>
                            <td>Secure Registration with Duplicate Checks, Bcrypt Hashing, and Audit Insert</td>
                            <td><code>register.php</code>, <code>password_hash()</code>, <code>users</code> table</td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 10</strong></td>
                            <td>Secure Login/Logout, Sessions, RBAC Routing, 15-Min Timeout, Remember-Me</td>
                            <td><code>login.php</code>, <code>authenticate.php</code>, <code>auth.php</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 11</strong></td>
                            <td>Student Management CRUD, MySQLi Prepared Queries, and Excel CSV Export</td>
                            <td><code>api_crud.php</code>, <code>export.php</code>, <code>students</code> table</td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 12</strong></td>
                            <td>Campus Event Management, Multi-Format Poster Upload, and File Security</td>
                            <td><code>event_management.php</code>, <code>uploads/event_posters/</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 13</strong></td>
                            <td>PHP RESTful JSON API and Fetch API Asynchronous CRUD Without Page Reload</td>
                            <td><code>api/records.php</code>, <code>api/record.php</code>, <code>api-crud.js</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 14</strong></td>
                            <td>Admin Dashboard with Live Metrics, Dynamic Menu, RBAC, and Audit Logging</td>
                            <td><code>admin_dashboard.php</code>, <code>audit_logs.php</code>, <code>admin_menu.php</code></td>
                            <td><span class="badge-verified">Integrated</span></td>
                        </tr>
                        <tr>
                            <td><strong>Practical 15</strong></td>
                            <td>Final Integration of Practicals 1–14, End-to-End Testing, and GitHub Submission</td>
                            <td>Unified Portal, <code>studenthub_final_database.sql</code>, Word Report</td>
                            <td><span class="badge-verified" style="background:#dbeafe;color:#1e40af;">Completed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Technologies Used Card -->
        <div class="about-card">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                Technology Stack &amp; Architecture
            </h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;margin-top:16px;">
                <div style="background:#f8fafc;padding:18px;border-radius:10px;border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 8px;color:#1e3a8a;font-size:15px;">Frontend Tier</h4>
                    <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
                        Semantic HTML5, CSS3 Grid &amp; Flexbox, Modern Inter typography, Vanilla JavaScript DOM manipulation, and asynchronous Fetch API.
                    </p>
                </div>
                <div style="background:#f8fafc;padding:18px;border-radius:10px;border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 8px;color:#1e3a8a;font-size:15px;">Backend Tier</h4>
                    <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
                        PHP 8.2+ Object-Oriented MySQLi architecture, RESTful JSON API endpoints, parameterized prepared statements, and session security controllers.
                    </p>
                </div>
                <div style="background:#f8fafc;padding:18px;border-radius:10px;border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 8px;color:#1e3a8a;font-size:15px;">Database Tier</h4>
                    <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
                        MariaDB / MySQL InnoDB storage engine, relational foreign keys, utf8mb4 multi-language collation, and automated audit trails.
                    </p>
                </div>
                <div style="background:#f8fafc;padding:18px;border-radius:10px;border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 8px;color:#1e3a8a;font-size:15px;">Testing &amp; Deployment</h4>
                    <p style="font-size:13px;color:#64748b;margin:0;line-height:1.6;">
                        Local XAMPP deployment (Apache 2.4, MySQL), Puppeteer with Microsoft Edge headless automated verification, and Git version control.
                    </p>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer style="background:#0f172a;color:#94a3b8;padding:32px 24px;text-align:center;font-size:13px;border-top:1px solid #1e293b;">
        <p style="margin:0 0 8px;">
            &copy; <?php echo date('Y'); ?> StudentHub Portal &bull; CHARUSAT / CSPIT Computer Science &amp; Engineering.
        </p>
        <p style="margin:0;color:#64748b;">
            Academic Project by <strong>Krupal Parmar (25CS057)</strong> &bull; Subject: Web Development Frameworks (ITUE203)
        </p>
    </footer>

</body>
</html>
