<?php
/**
 * StudentHub Authentication Processing Script
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

// 1. Strict HTTP POST method enforcement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 2. Input Retrieval and Trimming
$emailOrUsername = isset($_POST['email']) ? trim($_POST['email']) : '';
$password        = isset($_POST['password']) ? (string)$_POST['password'] : '';
$rememberMe      = isset($_POST['remember_me']) && ($_POST['remember_me'] === '1' || $_POST['remember_me'] === 'on');
$csrfToken       = $_POST['csrf_token'] ?? '';

// 3. Optional CSRF verification for form submissions
if (!empty($csrfToken) && !validateCSRFToken($csrfToken)) {
    header('Location: login.php?error=invalid');
    exit;
}

// 4. Validation: Empty inputs
if ($emailOrUsername === '' || $password === '') {
    header('Location: login.php?error=empty');
    exit;
}

// 5. Validation: If user provided an explicit email address format, validate structure
if (strpos($emailOrUsername, '@') !== false && !filter_var($emailOrUsername, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?error=malformed_email');
    exit;
}

// 6. Ensure database connection is operational
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    error_log('[StudentHub Auth Error] MySQLi connection is unavailable: ' . $e->getMessage());
    header('Location: login.php?error=invalid');
    exit;
}

// 7. Query user record using parameterized MySQLi prepared statement
// Accepts either registered email or username for maximum accessibility
$query = "SELECT id, username, email, password_hash, role, status, full_name, department, student_id_code 
          FROM users 
          WHERE email = ? OR username = ? 
          LIMIT 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log('[StudentHub Auth Error] Prepare statement failed: ' . $conn->error);
    header('Location: login.php?error=invalid');
    exit;
}

$stmt->bind_param('ss', $emailOrUsername, $emailOrUsername);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

// 8. Verify credentials using password_verify() against cryptographic hash
if (!$user || !password_verify($password, $user['password_hash'])) {
    // Record failed authentication attempt in security audit log
    logAuditEvent(
        $conn,
        $user ? (int)$user['id'] : null,
        'FAILED_LOGIN',
        "Failed login attempt for identifier '{$emailOrUsername}'"
    );
    header('Location: login.php?error=invalid');
    exit;
}

// 8b. Intermediate Extension: Check User Active/Inactive Account Status
if (($user['status'] ?? 'Active') === 'Inactive') {
    logAuditEvent(
        $conn,
        (int)$user['id'],
        'LOGIN_INACTIVE_DENIED',
        "Login blocked for deactivated account '{$user['username']}' (#{$user['id']})."
    );
    header('Location: login.php?error=inactive_account');
    exit;
}

// 9. Security: Regenerate Session ID to mitigate Session Fixation attacks
session_regenerate_id(true);

// 10. Establish authenticated session variables (Database role is source of truth)
$_SESSION['user_id']         = (int)$user['id'];
$_SESSION['username']        = $user['username'];
$_SESSION['email']           = $user['email'];
$_SESSION['role']            = $user['role']; // 'Admin' or 'Student'
$_SESSION['status']          = $user['status'] ?? 'Active';
$_SESSION['full_name']       = $user['full_name'];
$_SESSION['department']      = $user['department'];
$_SESSION['student_id_code'] = $user['student_id_code'];
$_SESSION['last_activity']   = time();

// 11. Handle persistent Remember-Me token if requested
if ($rememberMe) {
    createRememberMeToken($conn, (int)$user['id']);
}

// 12. Update last login timestamp in database
$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
if ($updateStmt) {
    $updateStmt->bind_param('i', $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
}

// 13. Log successful authentication event
logAuditEvent(
    $conn,
    (int)$user['id'],
    'LOGIN',
    "User '{$user['username']}' ({$user['role']}) authenticated successfully."
);

// 14. Direct user through the role-specific dashboard router
header('Location: dashboard.php');
exit;
