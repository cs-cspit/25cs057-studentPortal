<?php
/**
 * StudentHub — Institutional Academic Information Portal
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

// Safe Database Connection & Dynamic Metrics
$metrics = [
    'students'      => 0,
    'events'        => 0,
    'registrations' => 0,
    'audits'        => 0
];
$upcomingEvents = [];

try {
    $conn = getDBConnection();
    
    // Total Students
    $res = $conn->query("SELECT COUNT(*) AS total FROM students");
    if ($res && $row = $res->fetch_assoc()) {
        $metrics['students'] = (int)$row['total'];
    }
    
    // Total Events
    $res = $conn->query("SELECT COUNT(*) AS total FROM events");
    if ($res && $row = $res->fetch_assoc()) {
        $metrics['events'] = (int)$row['total'];
    }
    
    // Total Registrations
    $res = $conn->query("SELECT COUNT(*) AS total FROM registrations");
    if ($res && $row = $res->fetch_assoc()) {
        $metrics['registrations'] = (int)$row['total'];
    }
    
    // Total Audits
    $res = $conn->query("SELECT COUNT(*) AS total FROM audit_logs");
    if ($res && $row = $res->fetch_assoc()) {
        $metrics['audits'] = (int)$row['total'];
    }

    // Latest 3 Featured Campus Events
    $eRes = $conn->query("SELECT id, title, category, status, description, poster_path, event_date, venue, capacity FROM events ORDER BY event_date ASC LIMIT 3");
    if ($eRes) {
        while ($e = $eRes->fetch_assoc()) {
            $upcomingEvents[] = $e;
        }
    }
} catch (Exception $ex) {
    // Graceful fallback if database connection is pending configuration
}

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$userRole    = $currentUser['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub — Integrated Student Management Portal | CHARUSAT CSPIT</title>
    
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Homepage Hero & Integrated Layout Specifics */
        .hero-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%);
            color: #ffffff;
            padding: 70px 24px 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(30, 58, 138, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .hero-badge-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }
        .hero-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 10px #22c55e;
        }
        .hero-title {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 20px;
            max-width: 850px;
        }
        .hero-title span {
            background: linear-gradient(90deg, #60a5fa 0%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc {
            font-size: 17px;
            line-height: 1.6;
            color: #cbd5e1;
            max-width: 760px;
            margin-bottom: 36px;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 48px;
        }
        .btn-hero-primary {
            background: #2563eb;
            color: #ffffff;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }
        .btn-hero-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            backdrop-filter: blur(8px);
        }
        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        /* Dynamic Metrics Counter Strip */
        .metrics-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding-top: 36px;
        }
        .metric-item {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .metric-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            flex-shrink: 0;
        }
        .metric-data .metric-val {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
            font-family: 'JetBrains Mono', monospace;
        }
        .metric-data .metric-lbl {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
            font-weight: 500;
        }

        /* Top Institutional Header Bar */
        .site-nav {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
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
        .brand-text-block {
            display: flex;
            flex-direction: column;
        }
        .brand-name {
            font-size: 19px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: -0.01em;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.04em;
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
            transition: color 0.15s ease;
        }
        .nav-links a:hover {
            color: #2563eb;
        }
        .nav-auth-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-nav-login {
            background: #f1f5f9;
            color: #1e293b;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-nav-login:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .btn-nav-register {
            background: #2563eb;
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-nav-register:hover {
            background: #1d4ed8;
        }

        /* Section Layout Containers */
        .section-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 64px 24px;
        }
        .section-head {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 48px;
        }
        .section-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
            margin-bottom: 12px;
        }
        .section-desc {
            font-size: 16px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Practical Integration Matrix Grid */
        .integration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .integration-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .integration-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
            transform: translateY(-3px);
        }
        .card-pra-tag {
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            background: #eff6ff;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 12px;
            font-family: 'JetBrains Mono', monospace;
        }
        .card-pra-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .card-pra-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .card-pra-tech {
            font-size: 11.5px;
            color: #475569;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Campus Events Showcase Cards */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }
        .event-showcase-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
        }
        .event-showcase-card:hover {
            box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.1);
            transform: translateY(-3px);
        }
        .event-poster-thumb {
            width: 100%;
            height: 180px;
            background: #1e293b;
            object-fit: cover;
            border-bottom: 1px solid #e2e8f0;
        }
        .event-poster-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            padding: 20px;
        }
        .event-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .event-category-pill {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-bottom: 8px;
            align-self: flex-start;
        }
        .event-card-title {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .event-card-meta {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .event-card-desc {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 16px;
            flex-grow: 1;
        }
        .event-card-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }

        /* Institutional Footer */
        .site-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 60px 24px 30px;
            border-top: 1px solid #1e293b;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-col h4 {
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: -0.01em;
        }
        .footer-col p {
            font-size: 13.5px;
            line-height: 1.6;
            margin-bottom: 14px;
        }
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13.5px;
            transition: color 0.15s ease;
        }
        .footer-links a:hover {
            color: #60a5fa;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            border-top: 1px solid #1e293b;
            padding-top: 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .hero-title { font-size: 32px; }
            .footer-inner { grid-template-columns: 1fr 1fr; }
            .nav-links { display: none; }
        }
        @media (max-width: 600px) {
            .footer-inner { grid-template-columns: 1fr; }
            .hero-actions { flex-direction: column; }
        }
    </style>
</head>
<body style="margin:0;font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;">

    <!-- Top Site Navigation -->
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
                    <li><a href="index.php" style="color:#2563eb;font-weight:600;">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#events">Campus Events</a></li>
                    <li><a href="#integration">Practicals 1–14</a></li>
                    <li><a href="help.php">Helpdesk &amp; FAQs</a></li>
                </ul>
            </nav>

            <div class="nav-auth-group">
                <?php if ($currentUser): ?>
                    <a href="<?php echo ($userRole === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn-nav-register" style="background:#1e3a8a;">
                        Dashboard (<?php echo htmlspecialchars($userRole); ?>)
                    </a>
                    <a href="logout.php" class="btn-nav-login" style="color:#ef4444;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-nav-login">Sign In</a>
                    <a href="register.php" class="btn-nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-badge-wrap">
                <span class="hero-badge-dot"></span>
                <span>CHARUSAT &bull; CSPIT &bull; CSE Department &bull; Practical 15 Final Integration</span>
            </div>
            
            <h1 class="hero-title">
                Next-Generation Integrated <span>StudentHub Portal</span> for Higher Education
            </h1>
            
            <p class="hero-desc">
                A unified, secure, enterprise-grade academic web application synthesizing all modules from Practical 1 to 14. 
                Featuring asynchronous Fetch API CRUD operations, validated multi-format poster uploads, strict role-based access control, cryptographic password hashing, and real-time security audit trails.
            </p>

            <div class="hero-actions">
                <?php if ($currentUser): ?>
                    <a href="<?php echo ($userRole === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn-hero-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span>Open Your Dashboard (<?php echo htmlspecialchars($currentUser['username']); ?>)</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-hero-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        <span>Sign In to StudentHub</span>
                    </a>
                    <a href="register.php" class="btn-hero-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        <span>Create Student Account</span>
                    </a>
                <?php endif; ?>
                <a href="about.php" class="btn-hero-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <span>System Specifications &bull; About Us</span>
                </a>
            </div>

            <!-- Authoritative MySQL Metrics Counter -->
            <div class="metrics-strip">
                <div class="metric-item">
                    <div class="metric-icon-box">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-val"><?php echo $metrics['students']; ?></div>
                        <div class="metric-lbl">Enrolled Students (DB)</div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon-box">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-val"><?php echo $metrics['events']; ?></div>
                        <div class="metric-lbl">Campus Events Active</div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon-box">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-val"><?php echo $metrics['registrations']; ?></div>
                        <div class="metric-lbl">Event Registrations</div>
                    </div>
                </div>

                <div class="metric-item">
                    <div class="metric-icon-box">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <div class="metric-data">
                        <div class="metric-val"><?php echo $metrics['audits']; ?></div>
                        <div class="metric-lbl">Security Audit Logs</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Core Modules Section -->
    <section class="section-wrap" id="features">
        <div class="section-head">
            <span class="section-badge">Enterprise Architecture</span>
            <h2 class="section-title">Unified Core Portal Modules</h2>
            <p class="section-desc">Each module represents an academic milestone seamlessly connected to the shared MySQL database and secured via role-based access control.</p>
        </div>

        <div class="integration-grid">
            <div class="integration-card">
                <div>
                    <span class="card-pra-tag">CRUD Module</span>
                    <h3 class="card-pra-title">Student Lifecycle Management</h3>
                    <p class="card-pra-desc">Full CRUD operations over student records with live instant search, multi-field filters, modal updates, soft-deletions, and RFC-4180 Excel CSV exports.</p>
                </div>
                <div class="card-pra-tech">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Practical 11 &bull; Practical 13</span>
                </div>
            </div>

            <div class="integration-card">
                <div>
                    <span class="card-pra-tag">Events Module</span>
                    <h3 class="card-pra-title">Campus Events &amp; File Upload</h3>
                    <p class="card-pra-desc">Institutional event management system supporting verified multi-format poster uploads (JPEG/PNG/WEBP), MIME verification, size limits, and registrations.</p>
                </div>
                <div class="card-pra-tech">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Practical 12 &bull; Poster Upload</span>
                </div>
            </div>

            <div class="integration-card">
                <div>
                    <span class="card-pra-tag">Security Module</span>
                    <h3 class="card-pra-title">Role-Based Access &amp; Sessions</h3>
                    <p class="card-pra-desc">Strict server-side role gating (Admin vs Student), automatic 15-minute inactivity timeout, session regeneration, and persistent Remember-Me token authentication.</p>
                </div>
                <div class="card-pra-tech">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Practical 10 &bull; Practical 14 RBAC</span>
                </div>
            </div>

            <div class="integration-card">
                <div>
                    <span class="card-pra-tag">API Engine</span>
                    <h3 class="card-pra-title">RESTful PHP JSON &amp; Fetch API</h3>
                    <p class="card-pra-desc">Complete zero-page-reload asynchronous operations using standard HTTP methods (GET, POST, PUT, DELETE), CSRF protection, and standardized JSON responses.</p>
                </div>
                <div class="card-pra-tech">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Practical 13 &bull; Fetch API</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Campus Events Live Showcase -->
    <section style="background:#ffffff;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:64px 24px;" id="events">
        <div class="section-wrap" style="padding:0;">
            <div class="section-head">
                <span class="section-badge">Live Campus Life</span>
                <h2 class="section-title">Upcoming Institutional Events</h2>
                <p class="section-desc">Queried directly from the MySQL database with verified poster artwork and capacity monitoring.</p>
            </div>

            <div class="events-grid">
                <?php if (!empty($upcomingEvents)): ?>
                    <?php foreach ($upcomingEvents as $ev): ?>
                        <div class="event-showcase-card">
                            <?php 
                                $posterFile = $ev['poster_path'] ?? '';
                                $posterDiskPath = __DIR__ . '/' . $posterFile;
                                if (!empty($posterFile) && file_exists($posterDiskPath)):
                            ?>
                                <img src="<?php echo htmlspecialchars($posterFile); ?>" alt="<?php echo htmlspecialchars($ev['title']); ?>" class="event-poster-thumb">
                            <?php else: ?>
                                <div class="event-poster-placeholder">
                                    <div>
                                        <div style="font-size:24px;margin-bottom:8px;">🎓</div>
                                        <div><?php echo htmlspecialchars($ev['title']); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="event-card-body">
                                <span class="event-category-pill"><?php echo htmlspecialchars($ev['category']); ?></span>
                                <h3 class="event-card-title"><?php echo htmlspecialchars($ev['title']); ?></h3>
                                <div class="event-card-meta">
                                    <span>📅 <strong>Date:</strong> <?php echo date('d M Y', strtotime($ev['event_date'])); ?></span>
                                    <span>📍 <strong>Venue:</strong> <?php echo htmlspecialchars($ev['venue']); ?></span>
                                    <span>👥 <strong>Capacity:</strong> <?php echo (int)$ev['capacity']; ?> seats</span>
                                </div>
                                <p class="event-card-desc"><?php echo htmlspecialchars(substr($ev['description'], 0, 110)) . '...'; ?></p>
                                <div class="event-card-action">
                                    <span style="font-size:12px;font-weight:600;color:<?php echo ($ev['status'] === 'Upcoming' ? '#16a34a' : '#2563eb'); ?>;">
                                        ● <?php echo htmlspecialchars($ev['status']); ?>
                                    </span>
                                    <a href="login.php" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">
                                        Register in Portal &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;grid-column:1/-1;color:#64748b;">No campus events found in database.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Practical 1 to 14 Integration Matrix Section -->
    <section class="section-wrap" id="integration">
        <div class="section-head">
            <span class="section-badge">Academic Pedagogy</span>
            <h2 class="section-title">Integration of Practicals 1 to 14</h2>
            <p class="section-desc">Every practical developed during the semester is preserved, synthesized, and actively working inside this unified web application.</p>
        </div>

        <div class="integration-grid">
            <div class="integration-card">
                <span class="card-pra-tag">Practical 1</span>
                <h3 class="card-pra-title">Initiation &amp; Setup</h3>
                <p class="card-pra-desc">Problem definition, sitemap planning, wireframe design, and official GitHub repository establishment.</p>
                <div class="card-pra-tech">Requirements &bull; Git Repo</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 2</span>
                <h3 class="card-pra-title">Semantic HTML5 Structure</h3>
                <p class="card-pra-desc">Accessible layouts, ARIA landmarks, timetable, attendance, marks, and student profiles.</p>
                <div class="card-pra-tech">HTML5 &bull; Accessibility</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 3</span>
                <h3 class="card-pra-title">CSS Grid &amp; Flexbox</h3>
                <p class="card-pra-desc">Responsive multi-column navigation, mobile breakpoint media queries, and unified institutional cards.</p>
                <div class="card-pra-tech">CSS Grid &bull; Flexbox</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 4</span>
                <h3 class="card-pra-title">DOM &amp; Interactivity</h3>
                <p class="card-pra-desc">Dynamic event listeners, modal controllers, dynamic form validation cues, and tab switching.</p>
                <div class="card-pra-tech">Vanilla JavaScript &bull; DOM</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 5</span>
                <h3 class="card-pra-title">Client-Side Validation</h3>
                <p class="card-pra-desc">Real-time regex email checking, password complexity indicators, and interactive error styling.</p>
                <div class="card-pra-tech">Form Validation &bull; UX</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 6</span>
                <h3 class="card-pra-title">Fetch API &amp; JSON</h3>
                <p class="card-pra-desc">Client-side asynchronous fetching of external JSON data with keyword filtering and UI card updates.</p>
                <div class="card-pra-tech">Fetch API &bull; JSON Feeds</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 7</span>
                <h3 class="card-pra-title">PHP Form Processing</h3>
                <p class="card-pra-desc">Server-side sanitization, GET/POST processing, and CSV/JSON persistence for records and FAQs.</p>
                <div class="card-pra-tech">PHP 8.2 &bull; File Storage</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 8</span>
                <h3 class="card-pra-title">MySQL Schema &amp; ER Model</h3>
                <p class="card-pra-desc">Relational database design, foreign key constraints, UTF-8 collation, and singleton connection handler.</p>
                <div class="card-pra-tech">MySQL InnoDB &bull; Relational DB</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 9</span>
                <h3 class="card-pra-title">Secure User Registration</h3>
                <p class="card-pra-desc">Prepared statement duplicate checks, cryptographic Bcrypt password hashing, and registration audit logging.</p>
                <div class="card-pra-tech">password_hash &bull; CSRF</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 10</span>
                <h3 class="card-pra-title">Sessions &amp; Remember-Me</h3>
                <p class="card-pra-desc">Session fixation defense, automatic 15-minute timeout, persistent tokens, and dual-role redirection.</p>
                <div class="card-pra-tech">PHP Sessions &bull; Tokens</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 11</span>
                <h3 class="card-pra-title">Student Management CRUD</h3>
                <p class="card-pra-desc">Complete record listing, search, status filtering, soft-deletion/restoration, and Excel CSV export.</p>
                <div class="card-pra-tech">MySQLi CRUD &bull; CSV Export</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 12</span>
                <h3 class="card-pra-title">Events &amp; Poster Upload</h3>
                <p class="card-pra-desc">Secure file upload validation (MIME types, 2MB size cap, hashed filenames), event creation, and updates.</p>
                <div class="card-pra-tech">File Handling &bull; Validation</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 13</span>
                <h3 class="card-pra-title">PHP JSON API &amp; Fetch</h3>
                <p class="card-pra-desc">RESTful endpoint architecture (/api/records.php) powering live CRUD without reloading the browser.</p>
                <div class="card-pra-tech">REST JSON &bull; AJAX Fetch</div>
            </div>

            <div class="integration-card">
                <span class="card-pra-tag">Practical 14</span>
                <h3 class="card-pra-title">Admin Dashboard &amp; RBAC</h3>
                <p class="card-pra-desc">Executive metrics, dynamic role-based navigation sidebar, security audit trail, and user account status toggles.</p>
                <div class="card-pra-tech">RBAC &bull; Audit Trail</div>
            </div>
        </div>
    </section>

    <!-- Institutional Footer -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-col">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                    <span style="font-size:22px;font-weight:800;color:#ffffff;">StudentHub</span>
                    <span style="background:#1e3a8a;color:#60a5fa;font-size:11px;padding:3px 8px;border-radius:4px;font-weight:700;">WDF PRACTICAL 15</span>
                </div>
                <p>
                    Official semester-end integration project for <strong>Web Development Frameworks (ITUE203)</strong>. 
                    Unifying modern front-end engineering, robust PHP server logic, relational MySQL architecture, and enterprise security standards.
                </p>
                <p style="font-size:12.5px;color:#64748b;">
                    Developed by <strong>Krupal Parmar (25CS057)</strong> &bull; B.Tech Computer Science &amp; Engineering &bull; CSPIT &bull; CHARUSAT.
                </p>
            </div>

            <div class="footer-col">
                <h4>Quick Navigation</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Portal Home</a></li>
                    <li><a href="about.php">About StudentHub</a></li>
                    <li><a href="login.php">Student &amp; Admin Login</a></li>
                    <li><a href="register.php">Student Registration</a></li>
                    <li><a href="help.php">Helpdesk &amp; FAQs</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Core Modules</h4>
                <ul class="footer-links">
                    <li><a href="login.php">Academic Dashboard</a></li>
                    <li><a href="login.php">Student Records (CRUD)</a></li>
                    <li><a href="login.php">Campus Event Console</a></li>
                    <li><a href="login.php">Audit &amp; Security Logs</a></li>
                    <li><a href="export.php">Export CSV Directory</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Academic Affiliation</h4>
                <p style="font-size:13px;line-height:1.6;margin-bottom:8px;">
                    <strong>CHARUSAT</strong><br>
                    Charotar University of Science and Technology<br>
                    Changa, Gujarat 388421
                </p>
                <p style="font-size:13px;line-height:1.6;">
                    <strong>CSPIT</strong><br>
                    Chandubhai S. Patel Institute of Technology<br>
                    Department of Computer Science &amp; Engineering
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> StudentHub Portal &bull; CHARUSAT / CSPIT CSE. All rights reserved.
            </div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#60a5fa;">
                Practical 15 Final Integration &bull; 25CS057 &bull; v1.5.0
            </div>
        </div>
    </footer>

</body>
</html>
