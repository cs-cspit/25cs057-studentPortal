<?php
/**
 * StudentHub Secure Logout Handler
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? (string)$_SESSION['username'] : 'User';

// 1. Revoke persistent Remember-Me tokens from database and clear client cookie
try {
    $conn = getDBConnection();
    if ($userId !== null) {
        revokeAllUserRememberTokens($conn, $userId);
        logAuditEvent($conn, $userId, 'LOGOUT', "User '{$username}' logged out securely.");
    } else {
        clearRememberMeCookie();
    }
} catch (Exception $e) {
    clearRememberMeCookie();
}

// 2. Clear all session variables in memory
$_SESSION = [];

// 3. Expire and clear the session cookie from the client browser
$sessionCookieName = session_name();
if (isset($_COOKIE[$sessionCookieName])) {
    $params = session_get_cookie_params();
    setcookie(
        $sessionCookieName,
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4. Destroy server-side session data
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 5. Redirect to login with logout confirmation notice
header('Location: login.php?msg=logged_out');
exit;
