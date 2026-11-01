<?php
/**
 * StudentHub System Audit Trail & Security Activity Logs
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/admin_menu.php';

// 1. Enforce Server-Side Authentication & Role-Based Authorization
requireAuth();
requireRole('Admin');

$user = getCurrentUser();
$conn = getDBConnection();

// 2. Query Filters & Pagination
$filterAction = trim($_GET['action'] ?? 'ALL');
$filterStatus = trim($_GET['status'] ?? 'ALL');
$searchQuery  = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 12;
$offset       = ($page - 1) * $limit;

$whereClauses = [];
$paramTypes   = '';
$paramValues  = [];

if ($filterAction !== 'ALL' && $filterAction !== '') {
    $whereClauses[] = "(action = ? OR event_type = ?)";
    $paramTypes    .= 'ss';
    $paramValues[]  = $filterAction;
    $paramValues[]  = $filterAction;
}

if ($filterStatus !== 'ALL' && $filterStatus !== '') {
    $whereClauses[] = "status = ?";
    $paramTypes    .= 's';
    $paramValues[]  = $filterStatus;
}

if ($searchQuery !== '') {
    $whereClauses[] = "(description LIKE ? OR username LIKE ? OR ip_address LIKE ?)";
    $wildcard = "%{$searchQuery}%";
    $paramTypes .= 'sss';
    $paramValues[] = $wildcard;
    $paramValues[] = $wildcard;
    $paramValues[] = $wildcard;
}

$whereSql = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

// Count total matching records
$countSql = "SELECT COUNT(*) as total FROM audit_logs" . $whereSql;
$countStmt = $conn->prepare($countSql);
if (!empty($paramValues)) {
    $countStmt->bind_param($paramTypes, ...$paramValues);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, ceil($totalRecords / $limit));

// Fetch paginated audit log records
$sql = "SELECT id, user_id, action, description, username, email, status, ip_address, user_agent, created_at 
        FROM audit_logs" . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$paginatedTypes = $paramTypes . 'ii';
$paginatedParams = array_merge($paramValues, [$limit, $offset]);
$stmt->bind_param($paginatedTypes, ...$paginatedParams);
$stmt->execute();
$auditLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Distinct action list for filter dropdown
$actionsRes = $conn->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action != '' ORDER BY action ASC");
$availableActions = [];
if ($actionsRes) {
    while ($row = $actionsRes->fetch_assoc()) {
        $availableActions[] = $row['action'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs — StudentHub Admin</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Consolas&display=swap" rel="stylesheet">
</head>
<body class="admin-portal-body">
    <div class="app-layout">
        <!-- Reusable Dynamic Sidebar (ADMIN Role) -->
        <?php renderDynamicSidebar('audit_logs', $user); ?>

        <div class="main-wrapper">
            <!-- Dynamic Top Header -->
            <?php renderDynamicTopHeader('Institutional Security Audit Trail', $user); ?>

            <main class="page-container">
                <!-- Filter & Search Controls -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                            Audit Log Filters &amp; Search
                        </span>
                        <span class="panel-tag"><?php echo number_format($totalRecords); ?> Total Logged Events</span>
                    </div>
                    <div class="panel-body">
                        <form method="GET" action="audit_logs.php" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">
                            <div style="flex:1;min-width:200px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--text-muted);">Search Keyword</label>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search description, user, IP..." style="width:100%;padding:8px 12px;border:1px solid var(--border-color);border-radius:var(--radius-sm);font-size:13px;">
                            </div>

                            <div style="min-width:180px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--text-muted);">Action Type</label>
                                <select name="action" style="width:100%;padding:8px 12px;border:1px solid var(--border-color);border-radius:var(--radius-sm);font-size:13px;background:#fff;">
                                    <option value="ALL">All Actions</option>
                                    <?php foreach ($availableActions as $act): ?>
                                        <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($filterAction === $act) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($act); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div style="min-width:140px;">
                                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--text-muted);">Status</label>
                                <select name="status" style="width:100%;padding:8px 12px;border:1px solid var(--border-color);border-radius:var(--radius-sm);font-size:13px;background:#fff;">
                                    <option value="ALL">All Statuses</option>
                                    <option value="SUCCESS" <?php echo ($filterStatus === 'SUCCESS') ? 'selected' : ''; ?>>SUCCESS</option>
                                    <option value="FAILED" <?php echo ($filterStatus === 'FAILED') ? 'selected' : ''; ?>>FAILED</option>
                                </select>
                            </div>

                            <div style="display:flex;gap:8px;">
                                <button type="submit" class="btn-tool btn-tool-primary" style="padding:8px 16px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    Filter
                                </button>
                                <a href="audit_logs.php" class="btn-tool" style="background:#f1f5f9;color:#475569;padding:8px 14px;">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Audit Logs Data Table -->
                <div class="panel-card">
                    <div class="panel-header">
                        <span class="panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            Security &amp; Administrative Event Records
                        </span>
                        <span class="panel-tag">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    </div>
                    <div class="panel-body no-padding">
                        <?php if (empty($auditLogs)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                </div>
                                <div class="empty-state-title">No Audit Logs Found</div>
                                <p style="font-size:13px;margin-top:4px;">No security actions match your filter criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="portal-table" id="auditLogTable">
                                    <thead>
                                        <tr>
                                            <th>Log ID</th>
                                            <th>Timestamp</th>
                                            <th>Actor / User</th>
                                            <th>Action Type</th>
                                            <th>Description</th>
                                            <th>Client IP</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($auditLogs as $log): 
                                            $actionType = $log['action'] ?: 'UNKNOWN';
                                            $actPrefix = strtolower(explode('_', $actionType)[0]);
                                            $badgeClass = 'audit-' . $actPrefix;
                                        ?>
                                            <tr>
                                                <td><code style="font-weight:700;color:#2563eb;">#<?php echo $log['id']; ?></code></td>
                                                <td style="white-space:nowrap;font-size:12px;color:var(--text-muted);">
                                                    <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($log['username'] ?? 'System/Guest'); ?></strong>
                                                    <?php if (!empty($log['user_id'])): ?>
                                                        <span style="font-size:11px;color:var(--text-muted);display:block;">UID: #<?php echo (int)$log['user_id']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="audit-badge <?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars($actionType); ?>
                                                    </span>
                                                </td>
                                                <td style="max-width:340px;">
                                                    <?php echo htmlspecialchars($log['description'] ?? ''); ?>
                                                </td>
                                                <td>
                                                    <code style="font-size:11.5px;"><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo strtolower($log['status']); ?>">
                                                        <?php echo htmlspecialchars($log['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Controls -->
                            <?php if ($totalPages > 1): ?>
                                <div style="padding:16px 22px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border-color);">
                                    <span style="font-size:13px;color:var(--text-muted);">Showing <?php echo $offset + 1; ?>–<?php echo min($totalRecords, $offset + $limit); ?> of <?php echo $totalRecords; ?> records</span>
                                    <div style="display:flex;gap:6px;">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($filterAction); ?>&status=<?php echo urlencode($filterStatus); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-tool" style="padding:6px 12px;font-size:12px;background:#f1f5f9;color:#334155;">&laquo; Prev</a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($filterAction); ?>&status=<?php echo urlencode($filterStatus); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-tool" style="padding:6px 12px;font-size:12px;<?php echo ($i === $page) ? 'background:#2563eb;color:#fff;' : 'background:#f1f5f9;color:#334155;'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($filterAction); ?>&status=<?php echo urlencode($filterStatus); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-tool" style="padding:6px 12px;font-size:12px;background:#f1f5f9;color:#334155;">Next &raquo;</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>

