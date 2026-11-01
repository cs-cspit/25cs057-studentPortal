<?php
/**
 * StudentHub Administrator Portal Dashboard
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
requireRole('Admin');

$user = getCurrentUser();
$conn = getDBConnection();
$csrfToken = getCSRFToken();

// 2. Dynamic Dashboard Statistics Loaded Authoritatively from MySQL
$stats = [
    'total_users'         => 0,
    'total_students'      => 0,
    'total_events'        => 0,
    'total_registrations' => 0,
    'active_students'     => 0,
    'active_users'        => 0
];

// Query Total Users & Active Breakdown
$uRes = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_cnt FROM users");
if ($uRes && $uRow = $uRes->fetch_assoc()) {
    $stats['total_users'] = (int)$uRow['total'];
    $stats['active_users'] = (int)($uRow['active_cnt'] ?? $uRow['total']);
}

// Query Total Students & Active Breakdown
$sRes = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_cnt FROM students");
if ($sRes && $sRow = $sRes->fetch_assoc()) {
    $stats['total_students'] = (int)$sRow['total'];
    $stats['active_students'] = (int)($sRow['active_cnt'] ?? 0);
}

// Query Total Events
$eRes = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($eRes && $eRow = $eRes->fetch_assoc()) {
    $stats['total_events'] = (int)$eRow['total'];
}

// Query Total Registrations
$rRes = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($rRes && $rRow = $rRes->fetch_assoc()) {
    $stats['total_registrations'] = (int)$rRow['total'];
}

// 3. Query Latest Registrations with Relational JOINs (Section 4.D)
$latestRegQuery = "SELECT 
        r.registration_id, 
        s.name AS student_name, 
        s.student_id AS enrollment_no, 
        e.title AS event_title, 
        e.category AS event_category,
        r.registration_date, 
        r.status, 
        r.notes 
    FROM registrations r 
    JOIN students s ON r.student_id = s.id 
    JOIN events e ON r.event_id = e.id 
    ORDER BY r.registration_date DESC, r.registration_id DESC 
    LIMIT 6";

$latestRegistrations = [];
$regRes = $conn->query($latestRegQuery);
if ($regRes) {
    while ($row = $regRes->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }
}

// 4. Query Recent Administrative Activity from audit_logs (Section 4.E)
$recentAuditQuery = "SELECT id, user_id, action, description, username, email, status, ip_address, created_at 
                     FROM audit_logs 
                     ORDER BY id DESC 
                     LIMIT 6";
$recentAuditLogs = [];
$auditRes = $conn->query($recentAuditQuery);
if ($auditRes) {
    while ($row = $auditRes->fetch_assoc()) {
        $recentAuditLogs[] = $row;
    }
}

// 5. Query Monthly Registration Data for Analytics Chart (Section 8 Advanced Extension)
$monthlyQuery = "SELECT 
        DATE_FORMAT(registration_date, '%b %Y') AS month_label,
        DATE_FORMAT(registration_date, '%Y-%m') AS sort_month,
        COUNT(*) AS total_count
    FROM registrations
    GROUP BY sort_month, month_label
    ORDER BY sort_month ASC
    LIMIT 12";

$monthlyAnalytics = [];
$monthlyRes = $conn->query($monthlyQuery);
if ($monthlyRes) {
    while ($row = $monthlyRes->fetch_assoc()) {
        $monthlyAnalytics[] = $row;
    }
}

// 6. Query User Accounts for Intermediate Toggle Preview
$usersPreview = [];
$uPreviewRes = $conn->query("SELECT id, username, email, role, status, full_name FROM users ORDER BY id ASC LIMIT 5");
if ($uPreviewRes) {
    while ($u = $uPreviewRes->fetch_assoc()) {
        $usersPreview[] = $u;
    }
}

$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard — StudentHub</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Consolas&display=swap" rel="stylesheet">
    <!-- Chart.js for High-Fidelity Analytics Visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="admin-portal-body">
    <div class="app-layout">
        <!-- Reusable Dynamic Sidebar (ADMIN Role) -->
        <?php renderDynamicSidebar('dashboard', $user); ?>

        <div class="main-wrapper">
            <!-- Dynamic Top Header with Admin Meta and Real-Time Date -->
            <?php renderDynamicTopHeader('Institutional Administrator Dashboard', $user); ?>

            <main class="page-container">
                <!-- Flash Notification Banner -->
                <?php foreach ($flashes as $f): ?>
                    <div class="alert-banner alert-<?php echo ($f['type'] === 'success') ? 'success' : 'danger'; ?>">
                        <span><strong><?php echo ($f['type'] === 'success') ? 'Success:' : 'Notice:'; ?></strong> <?php echo htmlspecialchars($f['message']); ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Dynamic Statistics Cards Grid (Section 4.C) -->
                <section class="stats-grid" id="statsGrid">
                    <!-- Stat Card 1: Total Users -->
                    <div class="stat-card stat-card-users" id="cardTotalUsers">
                        <div class="stat-info">
                            <span class="stat-label">Total Users</span>
                            <span class="stat-value" id="valTotalUsers"><?php echo number_format($stats['total_users']); ?></span>
                            <span class="stat-desc"><?php echo $stats['active_users']; ?> Active accounts in system</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>

                    <!-- Stat Card 2: Total Students -->
                    <div class="stat-card stat-card-students" id="cardTotalStudents">
                        <div class="stat-info">
                            <span class="stat-label">Total Students</span>
                            <span class="stat-value" id="valTotalStudents"><?php echo number_format($stats['total_students']); ?></span>
                            <span class="stat-desc"><?php echo $stats['active_students']; ?> Active academic records</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        </div>
                    </div>

                    <!-- Stat Card 3: Total Campus Events -->
                    <div class="stat-card stat-card-events" id="cardTotalEvents">
                        <div class="stat-info">
                            <span class="stat-label">Campus Events</span>
                            <span class="stat-value" id="valTotalEvents"><?php echo number_format($stats['total_events']); ?></span>
                            <span class="stat-desc">Technical &amp; cultural programs</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                    </div>

                    <!-- Stat Card 4: Total Registrations -->
                    <div class="stat-card stat-card-registrations" id="cardTotalRegistrations">
                        <div class="stat-info">
                            <span class="stat-label">Registrations</span>
                            <span class="stat-value" id="valTotalRegistrations"><?php echo number_format($stats['total_registrations']); ?></span>
                            <span class="stat-desc">Student event enrollments</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        </div>
                    </div>
                </section>

                <!-- Advanced Analytics Chart & Quick Overview Grid (Section 8) -->
                <div class="dashboard-grid">
                    <!-- Left Column: Monthly Registration Analytics Chart -->
                    <section class="panel-card" id="analyticsChartCard">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                Monthly Event Registration Analytics
                            </span>
                            <span class="panel-tag">Practical 14 Advanced Extension</span>
                        </div>
                        <div class="panel-body">
                            <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">
                                Trend analysis of student participation across university events (data queried in real time from <code>registrations</code> table):
                            </p>
                            <div class="chart-container">
                                <canvas id="monthlyRegistrationsChart"></canvas>
                            </div>
                        </div>
                    </section>

                    <!-- Right Column: User Management Status Toggle Preview (Section 8 Intermediate) -->
                    <section class="panel-card" id="userStatusToggleCard">
                        <div class="panel-header">
                            <span class="panel-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                User Status Management
                            </span>
                            <a href="user_management.php" style="font-size:12px;color:#2563eb;text-decoration:none;font-weight:600;">View All &rarr;</a>
                        </div>
                        <div class="panel-body no-padding">
                            <div class="table-responsive">
                                <table class="portal-table" style="font-size:12.5px;">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Status</th>
                                            <th style="text-align:right;">Toggle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usersPreview as $u): 
                                            $uStatus = $u['status'] ?? 'Active';
                                            $isSelf = ((int)$u['id'] === (int)$user['id']);
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                                    <span style="font-size:11px;color:var(--text-muted);display:block;"><?php echo htmlspecialchars($u['role']); ?></span>
                                                </td>
                                                <td>
                                                    <span id="statusBadge_<?php echo $u['id']; ?>" class="status-badge status-<?php echo strtolower($uStatus); ?>">
                                                        <?php echo htmlspecialchars($uStatus); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <?php if ($isSelf): ?>
                                                        <span style="font-size:11px;color:var(--text-muted);font-style:italic;">Self (Active)</span>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn-toggle-status <?php echo ($uStatus === 'Active') ? 'btn-toggle-active' : 'btn-toggle-inactive'; ?>" 
                                                                data-user-id="<?php echo $u['id']; ?>" 
                                                                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                                data-status="<?php echo $uStatus; ?>"
                                                                data-csrf="<?php echo $csrfToken; ?>">
                                                            <?php echo ($uStatus === 'Active') ? 'Deactivate' : 'Activate'; ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Latest Registrations Table (Section 4.D) -->
                <section class="panel-card" id="registrationsSection">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Latest Event Registrations (Relational JOIN Query)
                        </span>
                        <span class="panel-tag">Live Database Feed</span>
                    </div>
                    <div class="panel-body no-padding">
                        <?php if (empty($latestRegistrations)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </div>
                                <div class="empty-state-title">No Registrations Recorded Yet</div>
                                <p style="font-size:13px;margin-top:4px;">When students register for campus events, enrollment records will appear here automatically.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="portal-table" id="latestRegistrationsTable">
                                    <thead>
                                        <tr>
                                            <th>Registration ID</th>
                                            <th>Student Name</th>
                                            <th>Event Name</th>
                                            <th>Registration Date</th>
                                            <th>Status</th>
                                            <th>Participation Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latestRegistrations as $reg): ?>
                                            <tr>
                                                <td><code style="font-weight:700;color:#2563eb;">#<?php echo $reg['registration_id']; ?></code></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong>
                                                    <span style="font-size:11px;color:var(--text-muted);display:block;">Code: <code><?php echo htmlspecialchars($reg['enrollment_no']); ?></code></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($reg['event_title']); ?></strong>
                                                    <span style="font-size:11px;color:var(--text-muted);display:block;"><?php echo htmlspecialchars($reg['event_category']); ?></span>
                                                </td>
                                                <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);">
                                                    <?php echo date('M d, Y H:i', strtotime($reg['registration_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($reg['status']); ?>">
                                                        <?php echo htmlspecialchars($reg['status']); ?>
                                                    </span>
                                                </td>
                                                <td style="font-size:12.5px;color:var(--text-muted);">
                                                    <?php echo !empty($reg['notes']) ? htmlspecialchars($reg['notes']) : 'Standard Entry'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Recent Administrative Activity Feed (Section 4.E) -->
                <section class="panel-card" id="recentActivitySection">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            Recent Administrative &amp; Security Audit Trail
                        </span>
                        <a href="audit_logs.php" style="font-size:12.5px;color:#2563eb;text-decoration:none;font-weight:600;">View Complete Log &rarr;</a>
                    </div>
                    <div class="panel-body no-padding">
                        <?php if (empty($recentAuditLogs)): ?>
                            <div class="empty-state">
                                <div class="empty-state-title">No Audit Activity Recorded</div>
                                <p style="font-size:13px;margin-top:4px;">All administrative logins, creations, updates, and deletions will be logged here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="portal-table" id="recentAuditTable">
                                    <thead>
                                        <tr>
                                            <th>Log ID</th>
                                            <th>Timestamp</th>
                                            <th>Administrator / User</th>
                                            <th>Action Performed</th>
                                            <th>Description</th>
                                            <th>Client IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAuditLogs as $a): 
                                            $actName = $a['action'] ?: 'UNKNOWN';
                                            $prefix = strtolower(explode('_', $actName)[0]);
                                            $badgeClass = 'audit-' . $prefix;
                                        ?>
                                            <tr>
                                                <td><code style="font-weight:700;color:#2563eb;">#<?php echo $a['id']; ?></code></td>
                                                <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);">
                                                    <?php echo date('M d, Y H:i:s', strtotime($a['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($a['username'] ?? 'Admin'); ?></strong>
                                                    <?php if (!empty($a['user_id'])): ?>
                                                        <span style="font-size:11px;color:var(--text-muted);display:block;">UID: #<?php echo (int)$a['user_id']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="audit-badge <?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars($actName); ?>
                                                    </span>
                                                </td>
                                                <td style="max-width:380px;">
                                                    <?php echo htmlspecialchars($a['description'] ?? ''); ?>
                                                </td>
                                                <td>
                                                    <code style="font-size:11.5px;"><?php echo htmlspecialchars($a['ip_address']); ?></code>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Interactive Evaluation & RBAC Testing Suite (Section 5 & 11) -->
                <section class="panel-card" id="testSuiteCard">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            Practical 14 Security &amp; RBAC Verification Suite
                        </span>
                        <span class="panel-tag">Interactive Controls</span>
                    </div>
                    <div class="panel-body">
                        <p style="font-size:13px;color:var(--text-muted);">
                            Evaluate server-side authorization enforcement, student access denial (HTTP 403), audit logging verification, and session timeout routines:
                        </p>
                        <div class="security-toolbar">
                            <a href="student_dashboard.php" class="btn-tool btn-tool-warning" id="testRbacAdminBtn" title="Test server-side RBAC restriction">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <span>Test RBAC Denial (Attempt Student Dashboard)</span>
                            </a>
                            <a href="api_crud.php" class="btn-tool btn-tool-primary" id="testStudentCrudBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                <span>Student Management (P13 CRUD &amp; Logs)</span>
                            </a>
                            <a href="event_management.php" class="btn-tool btn-tool-primary" id="testEventManagementBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                                <span>Campus Event Management (P12)</span>
                            </a>
                            <a href="admin_dashboard.php?simulate_timeout=1" class="btn-tool" style="background:#f1f5f9;color:#475569;" id="testTimeoutBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>Simulate Inactivity Timeout</span>
                            </a>
                            <a href="logout.php" class="btn-tool btn-tool-danger" id="testLogoutBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Embedded Analytics Data for JavaScript Chart -->
    <script id="monthlyAnalyticsData" type="application/json">
        <?php echo json_encode($monthlyAnalytics); ?>
    </script>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>
