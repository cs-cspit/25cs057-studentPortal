<?php
/**
 * StudentHub User Account Status Toggle Endpoint
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu, and Audit Log
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/audit_logger.php';

// 1. Strict Server-Side Authentication & Role-Based Authorization
requireAuth();
requireRole('Admin');

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid HTTP request method.']);
        exit;
    }
    header('Location: admin_dashboard.php');
    exit;
}

// 2. CSRF Token Validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['success' => false, 'message' => 'CSRF security token mismatch. Request rejected.']);
        exit;
    }
    set_flash('error', 'Security Error: CSRF token invalid.');
    header('Location: admin_dashboard.php');
    exit;
}

// 3. Input Validation
$userId    = (int)($_POST['user_id'] ?? 0);
$newStatus = trim($_POST['new_status'] ?? '');

if ($userId <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'message' => 'Valid user ID is required.']);
        exit;
    }
    set_flash('error', 'Invalid user ID.');
    header('Location: admin_dashboard.php');
    exit;
}

if (!in_array($newStatus, ['Active', 'Inactive'], true)) {
    $newStatus = 'Active';
}

$conn = getDBConnection();

// 4. Retrieve Target User Record
$uStmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1");
$uStmt->bind_param('i', $userId);
$uStmt->execute();
$targetUser = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

if (!$targetUser) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 404);
        echo json_encode(['success' => false, 'message' => "User #{$userId} does not exist."]);
        exit;
    }
    set_flash('error', "User #{$userId} does not exist.");
    header('Location: admin_dashboard.php');
    exit;
}

// 5. Security Guard: Prevent Self-Deactivation by Logged-in Admin
if ($userId === (int)$_SESSION['user_id'] && $newStatus === 'Inactive') {
    $msg = 'Action prohibited: You cannot deactivate your own active administrator account.';
    if ($isAjax) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    set_flash('error', $msg);
    header('Location: admin_dashboard.php');
    exit;
}

// 6. Execute Prepared Update Statement
$updateStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
if (!$updateStmt) {
    if ($isAjax) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    set_flash('error', 'Database query failure.');
    header('Location: admin_dashboard.php');
    exit;
}

$updateStmt->bind_param('si', $newStatus, $userId);
$executed = $updateStmt->execute();
$updateStmt->close();

if ($executed) {
    // 7. Security Audit Logging (Mandatory Practical 14 Requirement)
    $actingAdmin = $_SESSION['username'] ?? 'Admin';
    $targetUsername = $targetUser['username'];
    $auditDesc = "Administrator '{$actingAdmin}' toggled account status of user #{$userId} ({$targetUsername}) to '{$newStatus}'.";
    
    recordAuditLog(
        $conn,
        (int)$_SESSION['user_id'],
        'USER_STATUS_TOGGLE',
        $auditDesc,
        'SUCCESS'
    );

    $successMsg = "User '{$targetUsername}' status successfully updated to {$newStatus}.";
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'message'   => $successMsg,
            'user_id'   => $userId,
            'new_status'=> $newStatus
        ]);
        exit;
    }

    set_flash('success', $successMsg);
} else {
    if ($isAjax) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'Failed to update record.']);
        exit;
    }
    set_flash('error', 'Failed to update record.');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php'));
exit;

