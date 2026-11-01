<?php
/**
 * StudentHub Audit Logger Helper Module
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once dirname(__DIR__) . '/db.php';

/**
 * Authoritatively records an administrative or security action into the audit_logs table.
 *
 * @param mysqli|null $conn Active database connection (auto-connected if null)
 * @param int|null $userId User ID who performed the action (or null if unauthenticated)
 * @param string $action Action name (e.g. LOGIN, LOGOUT, CREATE_EVENT, UPDATE_EVENT, DELETE_EVENT, CREATE_STUDENT, UPDATE_STUDENT, DELETE_STUDENT, USER_STATUS_TOGGLE)
 * @param string $description Detailed human-readable description of the operation
 * @param string $status Operation outcome ('SUCCESS' or 'FAILED')
 * @return bool True if logged successfully, false otherwise
 */
function recordAuditLog(?mysqli $conn, ?int $userId, string $action, string $description, string $status = 'SUCCESS'): bool {
    if (!$conn) {
        try {
            $conn = getDBConnection();
        } catch (Exception $e) {
            error_log('[StudentHub Audit Error] Connection failed: ' . $e->getMessage());
            return false;
        }
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    // Normalize username and email from active session if present
    $username = $_SESSION['username'] ?? null;
    $email    = $_SESSION['email'] ?? null;

    if ($userId !== null && ($username === null || $email === null)) {
        $uStmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param('i', $userId);
            $uStmt->execute();
            $res = $uStmt->get_result();
            if ($res && $uRow = $res->fetch_assoc()) {
                $username = $username ?? $uRow['username'];
                $email    = $email ?? $uRow['email'];
            }
            $uStmt->close();
        }
    }

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, username, email, status, ip_address, user_agent, details, event_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('[StudentHub Audit Error] Failed to prepare insert statement: ' . $conn->error);
        return false;
    }

    $stmt->bind_param('isssssssss', $userId, $action, $description, $username, $email, $status, $ipAddress, $userAgent, $description, $action);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

