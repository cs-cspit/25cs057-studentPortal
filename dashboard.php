<?php
/**
 * StudentHub Role-Based Dashboard Router
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

// 1. Enforce authentication and check session inactivity
if (!isLoggedIn()) {
    header('Location: login.php?error=login_required');
    exit;
}

// 2. Route to role-specific dashboard based solely on verified server session state
$userRole = (string)($_SESSION['role'] ?? '');

if ($userRole === 'Admin') {
    header('Location: admin_dashboard.php');
    exit;
} elseif ($userRole === 'Student') {
    header('Location: student_dashboard.php');
    exit;
} else {
    // Unrecognized or corrupted role: invalidate session and safely redirect
    $_SESSION = [];
    $cookieName = session_name();
    if (isset($_COOKIE[$cookieName])) {
        $params = session_get_cookie_params();
        setcookie($cookieName, '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: login.php?error=unauthorized');
    exit;
}
