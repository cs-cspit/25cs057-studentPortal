<?php
/**
 * StudentHub Student Portal Dashboard
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/admin_menu.php';

// 1. Strict Server-Side Authentication & Role-Based Authorization
requireAuth();
requireRole('Student');

$user = getCurrentUser();
$studentUsername  = htmlspecialchars($user['username'] ?? 'Student', ENT_QUOTES, 'UTF-8');
$studentEmail     = htmlspecialchars($user['email'] ?? 'student@studenthub.edu', ENT_QUOTES, 'UTF-8');
$studentFullName  = htmlspecialchars($user['full_name'] ?? 'Aarav Patel', ENT_QUOTES, 'UTF-8');
$studentIdCode    = htmlspecialchars($user['student_id_code'] ?? '25CS057', ENT_QUOTES, 'UTF-8');
$studentDept      = htmlspecialchars($user['department'] ?? 'Computer Science & Engineering', ENT_QUOTES, 'UTF-8');
$maskedSessionId  = substr(session_id(), 0, 8) . '••••••••' . substr(session_id(), -4);
$hasRememberCookie = isset($_COOKIE['remember_token']);

$conn = getDBConnection();

// Fetch student's registrations
$myRegistrations = [];
$regStmt = $conn->prepare("SELECT r.registration_id, e.title AS event_title, e.category, e.event_date, e.venue, r.registration_date, r.status, r.notes 
                           FROM registrations r 
                           JOIN events e ON r.event_id = e.id 
                           WHERE r.student_id = 1 OR r.student_id = (SELECT id FROM students WHERE student_id = ? LIMIT 1)
                           ORDER BY r.registration_date DESC");
if ($regStmt) {
    $regStmt->bind_param('s', $studentIdCode);
    $regStmt->execute();
    $res = $regStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $myRegistrations[] = $row;
    }
    $regStmt->close();
}

// Fetch upcoming campus events for student view
$eventsList = [];
$eRes = $conn->query("SELECT id, title, category, status, event_date, venue, capacity FROM events ORDER BY event_date ASC LIMIT 4");
if ($eRes) {
    while ($e = $eRes->fetch_assoc()) {
        $eventsList[] = $e;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Academic Portal — StudentHub</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Consolas&display=swap" rel="stylesheet">
</head>
<body class="admin-portal-body">
    <div class="app-layout">
        <!-- Reusable Dynamic Sidebar (STUDENT Role - Admin links strictly restricted) -->
        <?php renderDynamicSidebar('student_dashboard', $user); ?>

        <div class="main-wrapper">
            <!-- Dynamic Top Header -->
            <?php renderDynamicTopHeader('Student Academic Portal', $user); ?>

            <main class="page-container">
                <!-- Student Hero Profile Banner -->
                <div class="panel-card" id="profileSection" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: #ffffff;">
                    <div class="panel-body" style="padding: 28px;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px;">
                            <div style="display:flex;align-items:center;gap:18px;">
                                <div style="width:58px;height:58px;border-radius:50%;background:#3b82f6;border:3px solid #60a5fa;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;">
                                    <?php echo strtoupper(substr($studentFullName, 0, 1)); ?>
                                </div>
                                <div>
                                    <h2 style="font-size:22px;font-weight:700;"><?php echo $studentFullName; ?></h2>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;font-size:12.5px;color:#bfdbfe;">
                                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:4px;">ID: <strong><?php echo $studentIdCode; ?></strong></span>
                                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:4px;">Email: <strong><?php echo $studentEmail; ?></strong></span>
                                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:4px;">Dept: <strong><?php echo $studentDept; ?></strong></span>
                                        <span style="background:rgba(255,255,255,0.12);padding:2px 8px;border-radius:4px;">Semester: <strong>3rd (B.Tech CSE)</strong></span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-student" style="font-size:12px;padding:6px 12px;background:#dcfce7;color:#166534;font-weight:700;">
                                    AUTHENTICATED STUDENT ROLE
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Overview Statistics -->
                <div class="stats-grid">
                    <div class="stat-card stat-card-students">
                        <div class="stat-info">
                            <span class="stat-label">Enrolled Semester</span>
                            <span class="stat-value">3rd</span>
                            <span class="stat-desc">B.Tech Computer Science &amp; Engineering</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        </div>
                    </div>

                    <div class="stat-card stat-card-registrations">
                        <div class="stat-info">
                            <span class="stat-label">My Event Registrations</span>
                            <span class="stat-value"><?php echo count($myRegistrations); ?></span>
                            <span class="stat-desc">Active campus event participations</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        </div>
                    </div>

                    <div class="stat-card stat-card-events">
                        <div class="stat-info">
                            <span class="stat-label">Campus Opportunities</span>
                            <span class="stat-value"><?php echo count($eventsList); ?></span>
                            <span class="stat-desc">Available upcoming technical events</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- My Registrations Section -->
                <div class="panel-card" id="registrationsSection">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            My Campus Event Registrations
                        </span>
                        <span class="panel-tag"><?php echo count($myRegistrations); ?> Confirmed Enrollments</span>
                    </div>
                    <div class="panel-body no-padding">
                        <?php if (empty($myRegistrations)): ?>
                            <div class="empty-state">
                                <div class="empty-state-title">No Registrations Found</div>
                                <p style="font-size:13px;margin-top:4px;">You have not registered for any events yet. Check out upcoming campus events!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="portal-table" id="myRegistrationsTable">
                                    <thead>
                                        <tr>
                                            <th>Registration ID</th>
                                            <th>Event Name</th>
                                            <th>Category</th>
                                            <th>Event Date</th>
                                            <th>Venue</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myRegistrations as $reg): ?>
                                            <tr>
                                                <td><code style="font-weight:700;color:#2563eb;">#<?php echo $reg['registration_id']; ?></code></td>
                                                <td><strong><?php echo htmlspecialchars($reg['event_title']); ?></strong></td>
                                                <td><span class="panel-tag"><?php echo htmlspecialchars($reg['category']); ?></span></td>
                                                <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($reg['event_date']); ?></td>
                                                <td style="font-size:12.5px;color:var(--text-muted);"><?php echo htmlspecialchars($reg['venue']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($reg['status']); ?>">
                                                        <?php echo htmlspecialchars($reg['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Practical 2 Academic Workflows: Attendance, Examination Results & Timetable -->
                <div class="dashboard-grid" style="margin-bottom:24px;">
                    <!-- Attendance Progress Overview -->
                    <div class="panel-card" id="attendanceSection">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                Academic Attendance Overview
                            </span>
                            <span class="panel-tag" style="background:#dcfce7;color:#166534;">92.4% Overall</span>
                        </div>
                        <div class="panel-body">
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px;">
                                        <span>ITUE203: Web Development Frameworks</span>
                                        <span style="color:#16a34a;">94.2% (49/52)</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                        <div style="width:94.2%;height:100%;background:#22c55e;"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px;">
                                        <span>ITUE201: Data Structures &amp; Algorithms</span>
                                        <span style="color:#16a34a;">91.0% (41/45)</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                        <div style="width:91.0%;height:100%;background:#22c55e;"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px;">
                                        <span>ITUE202: Database Management Systems</span>
                                        <span style="color:#16a34a;">95.5% (43/45)</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                        <div style="width:95.5%;height:100%;background:#22c55e;"></div>
                                    </div>
                                </div>
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px;">
                                        <span>ITUE204: Operating Systems Concepts</span>
                                        <span style="color:#2563eb;">89.0% (40/45)</span>
                                    </div>
                                    <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                        <div style="width:89.0%;height:100%;background:#3b82f6;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Examination Results Summary -->
                    <div class="panel-card" id="resultsSection">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                Semester Performance &amp; Grades
                            </span>
                            <span class="panel-tag" style="background:#eff6ff;color:#1e40af;">SGPA: 9.15</span>
                        </div>
                        <div class="panel-body">
                            <div class="table-container" style="border:none;margin:0;">
                                <table class="portal-table" style="font-size:12.5px;">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Subject Title</th>
                                            <th>Credits</th>
                                            <th>Grade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>ITUE203</code></td>
                                            <td>Web Development Frameworks</td>
                                            <td>4.0</td>
                                            <td><span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;">AA</span></td>
                                            <td><span class="status-badge status-confirmed">PASS</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>ITUE201</code></td>
                                            <td>Data Structures &amp; Algorithms</td>
                                            <td>5.0</td>
                                            <td><span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;">AB</span></td>
                                            <td><span class="status-badge status-confirmed">PASS</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>ITUE202</code></td>
                                            <td>Database Management Systems</td>
                                            <td>4.0</td>
                                            <td><span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;">AA</span></td>
                                            <td><span class="status-badge status-confirmed">PASS</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>ITUE204</code></td>
                                            <td>Operating Systems</td>
                                            <td>4.0</td>
                                            <td><span class="badge" style="background:#dcfce7;color:#166534;font-weight:700;">AB</span></td>
                                            <td><span class="status-badge status-confirmed">PASS</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timetable & Notices Layout -->
                <div class="dashboard-grid" style="margin-bottom:24px;">
                    <!-- Weekly Timetable Schedule -->
                    <div class="panel-card" id="timetableSection">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Weekly Lecture Timetable
                            </span>
                            <span class="panel-tag">Room 304 &bull; CSE</span>
                        </div>
                        <div class="panel-body">
                            <div class="table-container" style="border:none;margin:0;">
                                <table class="portal-table" style="font-size:12px;">
                                    <thead>
                                        <tr>
                                            <th>Time Slot</th>
                                            <th>Mon</th>
                                            <th>Tue</th>
                                            <th>Wed</th>
                                            <th>Thu</th>
                                            <th>Fri</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>09:10 - 10:10</code></td>
                                            <td><strong>ITUE203</strong> (WDF)</td>
                                            <td><strong>ITUE201</strong> (DSA)</td>
                                            <td><strong>ITUE202</strong> (DBMS)</td>
                                            <td><strong>ITUE204</strong> (OS)</td>
                                            <td><strong>ITUE203</strong> (WDF)</td>
                                        </tr>
                                        <tr>
                                            <td><code>10:10 - 11:10</code></td>
                                            <td><strong>ITUE202</strong> (DBMS)</td>
                                            <td><strong>ITUE204</strong> (OS)</td>
                                            <td><strong>ITUE201</strong> (DSA)</td>
                                            <td><strong>ITUE203</strong> (WDF)</td>
                                            <td><strong>ITUE201</strong> (DSA)</td>
                                        </tr>
                                        <tr>
                                            <td><code>11:10 - 11:30</code></td>
                                            <td colspan="5" style="text-align:center;background:#f8fafc;color:#64748b;font-weight:600;">☕ RECESS &amp; REFRESHMENT</td>
                                        </tr>
                                        <tr>
                                            <td><code>11:30 - 01:30</code></td>
                                            <td>Lab: WDF (Lab 3)</td>
                                            <td>Lab: DBMS (Lab 1)</td>
                                            <td>Lab: DSA (Lab 2)</td>
                                            <td>Lab: OS (Lab 4)</td>
                                            <td>Project Lab</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Campus Notices & Circulars -->
                    <div class="panel-card" id="noticesSection">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                Department Circulars &amp; Notices
                            </span>
                            <span class="panel-tag" style="background:#eff6ff;color:#1e40af;">3 New Notices</span>
                        </div>
                        <div class="panel-body">
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                <div style="padding:10px 12px;background:#f8fafc;border-left:3px solid #2563eb;border-radius:4px;">
                                    <div style="font-size:13px;font-weight:700;color:#1e293b;">Mid-Semester Laboratory Evaluations Schedule</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Practical evaluations for WDF, DBMS, and OS commence from next Monday in Lab 3.</div>
                                </div>
                                <div style="padding:10px 12px;background:#f8fafc;border-left:3px solid #16a34a;border-radius:4px;">
                                    <div style="font-size:13px;font-weight:700;color:#1e293b;">HackNova 2026 Registration Open</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:2px;">National 24-hour hackathon registrations are now active under the Events Console.</div>
                                </div>
                                <div style="padding:10px 12px;background:#f8fafc;border-left:3px solid #f59e0b;border-radius:4px;">
                                    <div style="font-size:13px;font-weight:700;color:#1e293b;">University Library Book Return Reminder</div>
                                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Ensure all reference textbooks are renewed or returned by the 15th to avoid late fines.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Session Security Inspector & RBAC Verification -->
                <div class="dashboard-grid">
                    <div class="panel-card">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Authenticated Session Inspector
                            </span>
                            <span class="panel-tag">SSL &bull; Protected</span>
                        </div>
                        <div class="panel-body">
                            <table class="portal-table" style="font-size:13px;">
                                <tr>
                                    <td style="width:40%;color:var(--text-muted);"><strong>Session ID (Masked):</strong></td>
                                    <td><code><?php echo $maskedSessionId; ?></code></td>
                                </tr>
                                <tr>
                                    <td style="color:var(--text-muted);"><strong>User ID:</strong></td>
                                    <td><code><?php echo $user['id']; ?></code></td>
                                </tr>
                                <tr>
                                    <td style="color:var(--text-muted);"><strong>Assigned Role:</strong></td>
                                    <td><span class="badge badge-student">STUDENT</span></td>
                                </tr>
                                <tr>
                                    <td style="color:var(--text-muted);"><strong>Inactivity Threshold:</strong></td>
                                    <td><code>15 Minutes (900s)</code></td>
                                </tr>
                                <tr>
                                    <td style="color:var(--text-muted);"><strong>Persistent Cookie:</strong></td>
                                    <td>
                                        <span class="status-badge <?php echo $hasRememberCookie ? 'status-confirmed' : 'status-pending'; ?>">
                                            <?php echo $hasRememberCookie ? 'Active Remember-Me Token' : 'Standard Session'; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Role-Based Access Control Evaluation Toolbar -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                RBAC Security Evaluation Controls
                            </span>
                            <span class="panel-tag">Practical 14 Test</span>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
                                Test authoritative server-side RBAC enforcement: clicking the button below will attempt to directly access <code>admin_dashboard.php</code> without ADMIN privileges, triggering an immediate HTTP 403 Forbidden denial:
                            </p>
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <a href="admin_dashboard.php" class="btn-tool btn-tool-danger" id="testRbacStudentBtn" style="justify-content:center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>Attempt Admin Dashboard (Expect HTTP 403)</span>
                                </a>
                                <a href="audit_logs.php" class="btn-tool btn-tool-danger" id="testAuditLogsStudentBtn" style="justify-content:center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                    <span>Attempt Admin Audit Logs (Expect HTTP 403)</span>
                                </a>
                                <a href="logout.php" class="btn-tool" style="background:#f1f5f9;color:#475569;justify-content:center;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>
