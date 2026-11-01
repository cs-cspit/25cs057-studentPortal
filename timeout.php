<?php
/**
 * StudentHub Session Configuration and Inactivity Timeout Enforcer
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';

// Inactivity timeout threshold: default 900 seconds (15 minutes).
// Can be customized via environment variable or query simulation for practical evaluation.
if (!defined('SESSION_TIMEOUT_DURATION')) {
    define('SESSION_TIMEOUT_DURATION', 900);
}

// 1. Secure Session Cookie Initialization (Must precede session_start)
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS environment
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,              // Session cookie expires when browser is closed
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,        // True if TLS/HTTPS is active
        'httponly' => true,            // Mitigates cookie theft via XSS
        'samesite' => 'Lax'            // Mitigates Cross-Site Request Forgery (CSRF)
    ]);

    session_start();
}

/**
 * Checks for session inactivity timeout and terminates expired sessions.
 * Also supports an explicit simulation parameter (?simulate_timeout=1) for lab verification.
 *
 * @param int $customDuration Optional override for timeout duration in seconds
 * @return void
 */
function checkSessionTimeout(int $customDuration = SESSION_TIMEOUT_DURATION): void {
    if (!isset($_SESSION['user_id'])) {
        return; // No active user session to check
    }

    $currentTime = time();
    $lastActivity = $_SESSION['last_activity'] ?? $currentTime;

    // Check if inactivity window has elapsed or if simulation was explicitly triggered
    $isSimulated = isset($_GET['simulate_timeout']) && $_GET['simulate_timeout'] === '1';
    $isExpired = ($currentTime - $lastActivity) > $customDuration || $isSimulated;

    if ($isExpired) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $username = (string)($_SESSION['username'] ?? 'User');

        // Log security audit event
        try {
            $conn = getDBConnection();
            logAuditEvent(
                $conn,
                $userId,
                'SESSION_TIMEOUT',
                "User '{$username}' (ID: {$userId}) session expired due to inactivity."
            );
        } catch (Exception $e) {
            // Suppress technical logging error to prevent client disclosure
        }

        // 1. Clear session array in memory
        $_SESSION = [];

        // 2. Expire browser session cookie
        $cookieName = session_name();
        if (isset($_COOKIE[$cookieName])) {
            $params = session_get_cookie_params();
            setcookie(
                $cookieName,
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // 3. Destroy server session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // 4. Redirect to login with timeout notice
        header('Location: login.php?error=timeout');
        exit;
    }

    // Refresh last activity timestamp on successful user interaction
    $_SESSION['last_activity'] = $currentTime;
}
