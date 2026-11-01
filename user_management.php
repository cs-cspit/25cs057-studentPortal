<?php
/**
 * StudentHub User Management & Account Status Administration
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

// 2. Retrieve All Registered Users
$usersRes = $conn->query("SELECT id, username, email, role, status, full_name, department, student_id_code, last_login, created_at FROM users ORDER BY id ASC");
$users = [];
$totalUsers = 0;
$activeCount = 0;
$inactiveCount = 0;

if ($usersRes) {
    while ($row = $usersRes->fetch_assoc()) {
        $users[] = $row;
        $totalUsers++;
        if (($row['status'] ?? 'Active') === 'Active') {
            $activeCount++;
        } else {
            $inactiveCount++;
        }
    }
}

$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management — StudentHub Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Consolas&display=swap" rel="stylesheet">
</head>
<body class="admin-portal-body">
    <div class="app-layout">
        <!-- Dynamic Sidebar Navigation -->
        <?php renderDynamicSidebar('users', $user); ?>

        <div class="main-wrapper">
            <!-- Dynamic Top Header -->
            <?php renderDynamicTopHeader('User Directory & Access Status Control', $user); ?>

            <main class="page-container">
                <!-- Flash Messages -->
                <?php foreach ($flashes as $f): ?>
                    <div class="alert-banner alert-<?php echo ($f['type'] === 'success') ? 'success' : 'danger'; ?>">
                        <span><strong><?php echo ($f['type'] === 'success') ? 'Success:' : 'Notice:'; ?></strong> <?php echo htmlspecialchars($f['message']); ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- User Account Summary Chips -->
                <div class="stats-grid">
                    <div class="stat-card stat-card-users">
                        <div class="stat-info">
                            <span class="stat-label">Total Registered Users</span>
                            <span class="stat-value"><?php echo $totalUsers; ?></span>
                            <span class="stat-desc">Institutional system accounts</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>

                    <div class="stat-card stat-card-students">
                        <div class="stat-info">
                            <span class="stat-label">Active Accounts</span>
                            <span class="stat-value"><?php echo $activeCount; ?></span>
                            <span class="stat-desc">Permitted to sign in and register</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                    </div>

                    <div class="stat-card stat-card-registrations">
                        <div class="stat-info">
                            <span class="stat-label">Deactivated / Inactive</span>
                            <span class="stat-value"><?php echo $inactiveCount; ?></span>
                            <span class="stat-desc">Access blocked via server-side RBAC</span>
                        </div>
                        <div class="stat-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Users Directory Table -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            Institutional User Accounts &amp; Status Toggle
                        </span>
                        <span class="panel-tag">Practical 14 Intermediate Extension</span>
                    </div>
                    <div class="panel-body no-padding">
                        <div class="table-responsive">
                            <table class="portal-table" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Institutional Email</th>
                                        <th>Assigned Role</th>
                                        <th>Account Status</th>
                                        <th>Full Name</th>
                                        <th>Department / Code</th>
                                        <th>Last Login</th>
                                        <th style="text-align:right;">Status Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): 
                                        $uStatus = $u['status'] ?? 'Active';
                                        $isSelf = ((int)$u['id'] === (int)$user['id']);
                                    ?>
                                        <tr>
                                            <td><code style="font-weight:700;color:#2563eb;">#<?php echo $u['id']; ?></code></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                                <?php if ($isSelf): ?>
                                                    <span style="font-size:10px;background:#eff6ff;color:#1d4ed8;padding:1px 5px;border-radius:3px;margin-left:4px;">You</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($u['email']); ?></code></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($u['role']); ?>">
                                                    <?php echo strtoupper($u['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span id="statusBadge_<?php echo $u['id']; ?>" class="status-badge status-<?php echo strtolower($u['status'] ?? 'active'); ?>">
                                                    <?php echo htmlspecialchars($u['status'] ?? 'Active'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                            <td>
                                                <div style="font-size:12px;color:var(--text-muted);">
                                                    <?php echo htmlspecialchars($u['department']); ?>
                                                    <?php if (!empty($u['student_id_code'])): ?>
                                                        &bull; <code><?php echo htmlspecialchars($u['student_id_code']); ?></code>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                                                <?php echo !empty($u['last_login']) ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never'; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <?php if ($isSelf): ?>
                                                    <span style="font-size:11px;color:var(--text-muted);font-style:italic;">Active (Self)</span>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn-toggle-status <?php echo ($uStatus === 'Active') ? 'btn-toggle-active' : 'btn-toggle-inactive'; ?>" 
                                                            data-user-id="<?php echo $u['id']; ?>" 
                                                            data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                            data-status="<?php echo $uStatus; ?>"
                                                            data-csrf="<?php echo $csrfToken; ?>">
                                                        <?php if ($uStatus === 'Active'): ?>
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                                            Deactivate
                                                        <?php else: ?>
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                            Activate
                                                        <?php endif; ?>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>

