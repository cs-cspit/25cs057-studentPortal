<?php
/**
 * StudentHub Authentication & Role-Based Access Control (RBAC) Module
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';

// Automatically enforce session timeout on any page requiring this auth helper
checkSessionTimeout();

/**
 * Checks whether an active, valid user session exists.
 * If unauthenticated, attempts automatic login via persistent Remember-Me token cookie.
 *
 * @return bool True if authenticated, false otherwise
 */
function isLoggedIn(): bool {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['role'])) {
        return true;
    }

    // Attempt persistent Remember-Me authentication if cookie is provided
    if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
        return attemptRememberMeLogin();
    }

    return false;
}

/**
 * Enforces authentication. Redirects unauthenticated visitors to login.php.
 *
 * @return void
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php?error=login_required');
        exit;
    }
}

/**
 * Enforces server-side Role-Based Access Control (RBAC).
 * If user role does not match the required role, immediately responds with HTTP 403 Forbidden
 * and terminates script execution to prevent unauthorized page rendering.
 *
 * @param string $requiredRole The exact required role ('Admin' or 'Student')
 * @return void
 */
function requireRole(string $requiredRole): void {
    requireAuth();

    $currentRole = (string)($_SESSION['role'] ?? '');

    if ($currentRole !== $requiredRole) {
        http_response_code(403);
        $username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
        $safeCurrentRole = htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8');
        $safeRequiredRole = htmlspecialchars($requiredRole, ENT_QUOTES, 'UTF-8');
        
        // Log unauthorized RBAC access attempt
        try {
            $conn = getDBConnection();
            logAuditEvent(
                $conn,
                (int)$_SESSION['user_id'],
                'UNAUTHORIZED_ACCESS_ATTEMPT',
                "User '{$username}' ({$safeCurrentRole}) attempted to access {$safeRequiredRole} area: " . $_SERVER['REQUEST_URI']
            );
        } catch (Exception $e) {
            // Suppress technical errors
        }

        // Render clean institutional 403 Access Denied template
        renderAccessDeniedPage($safeCurrentRole, $safeRequiredRole);
        exit; // Terminate execution immediately
    }
}

/**
 * Returns sanitized current user session data.
 *
 * @return array|null User session array or null if unauthenticated
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'              => (int)$_SESSION['user_id'],
        'username'        => $_SESSION['username'],
        'email'           => $_SESSION['email'],
        'role'            => $_SESSION['role'],
        'full_name'       => $_SESSION['full_name'] ?? 'User',
        'department'      => $_SESSION['department'] ?? 'Computer Science & Engineering',
        'student_id_code' => $_SESSION['student_id_code'] ?? null,
        'last_activity'   => $_SESSION['last_activity'] ?? time()
    ];
}

/**
 * Issues a cryptographically secure Remember-Me cookie and stores hashed validator in database.
 * Follows industry-standard split-token pattern (selector + validator).
 *
 * @param mysqli $conn Active database connection
 * @param int $userId Authenticated user ID
 * @param int $expiryDays Days until cookie expiration (default: 30 days)
 * @return bool True if created, false on error
 */
function createRememberMeToken(mysqli $conn, int $userId, int $expiryDays = 30): bool {
    try {
        // 1. Generate cryptographically secure random selector (16 bytes = 32 hex chars)
        $selector = bin2hex(random_bytes(16));
        
        // 2. Generate cryptographically secure random validator (32 bytes = 64 hex chars)
        $validator = bin2hex(random_bytes(32));
        
        // 3. Hash validator using SHA-256 for persistent database storage
        $tokenHash = hash('sha256', $validator);
        
        $expiresAt = date('Y-m-d H:i:s', time() + (86400 * $expiryDays));
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

        // 4. Store selector and hashed validator in database
        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, user_agent) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log('[StudentHub Auth] Failed to prepare remember-me statement: ' . $conn->error);
            return false;
        }

        $stmt->bind_param('issss', $userId, $selector, $tokenHash, $expiresAt, $userAgent);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // 5. Transmit split-token to client in secure HttpOnly cookie
            $cookieValue = $selector . ':' . $validator;
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                       (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            setcookie('remember_token', $cookieValue, [
                'expires'  => time() + (86400 * $expiryDays),
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            return true;
        }
    } catch (Exception $e) {
        error_log('[StudentHub Auth] Exception generating remember-me token: ' . $e->getMessage());
    }
    return false;
}

/**
 * Attempts persistent login via the split-token Remember-Me cookie.
 * Rotates the validator token on successful verification to prevent replay attacks.
 *
 * @return bool True if successfully authenticated, false otherwise
 */
function attemptRememberMeLogin(): bool {
    if (!isset($_COOKIE['remember_token']) || empty($_COOKIE['remember_token'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['remember_token'], 2);
    if (count($parts) !== 2) {
        clearRememberMeCookie();
        return false;
    }

    [$selector, $validator] = $parts;
    if (strlen($selector) !== 32 || strlen($validator) !== 64) {
        clearRememberMeCookie();
        return false;
    }

    try {
        $conn = getDBConnection();

        // 1. Retrieve persistent token record by unique selector
        $stmt = $conn->prepare("SELECT id, user_id, token_hash, expires_at FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $res = $stmt->get_result();
        $tokenRecord = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$tokenRecord) {
            // Expired or invalid selector: clean up client cookie
            clearRememberMeCookie();
            return false;
        }

        // 2. Constant-time cryptographic comparison using hash_equals
        $calculatedHash = hash('sha256', $validator);
        if (!hash_equals($tokenRecord['token_hash'], $calculatedHash)) {
            // Hash mismatch: possible token theft or tampering. Revoke all user tokens.
            revokeAllUserRememberTokens($conn, (int)$tokenRecord['user_id']);
            clearRememberMeCookie();
            error_log('[StudentHub Auth Warning] Remember-me validator hash mismatch for user ID: ' . $tokenRecord['user_id']);
            return false;
        }

        // 3. Retrieve user profile
        $userStmt = $conn->prepare("SELECT id, username, email, role, full_name, department, student_id_code FROM users WHERE id = ? LIMIT 1");
        if (!$userStmt) {
            return false;
        }

        $userId = (int)$tokenRecord['user_id'];
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userRes = $userStmt->get_result();
        $user = $userRes ? $userRes->fetch_assoc() : null;
        $userStmt->close();

        if (!$user) {
            clearRememberMeCookie();
            return false;
        }

        // 4. Token Rotation: generate a fresh validator token to invalidate the previous one
        $newValidator = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newValidator);
        $newExpiry = date('Y-m-d H:i:s', time() + (86400 * 30));

        $updateStmt = $conn->prepare("UPDATE remember_tokens SET token_hash = ?, expires_at = ? WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param('ssi', $newTokenHash, $newExpiry, $tokenRecord['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        // Update client cookie with rotated validator
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        setcookie('remember_token', $selector . ':' . $newValidator, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // 5. Establish authenticated session
        session_regenerate_id(true);
        $_SESSION['user_id']         = (int)$user['id'];
        $_SESSION['username']        = $user['username'];
        $_SESSION['email']           = $user['email'];
        $_SESSION['role']            = $user['role'];
        $_SESSION['full_name']       = $user['full_name'];
        $_SESSION['department']      = $user['department'];
        $_SESSION['student_id_code'] = $user['student_id_code'];
        $_SESSION['last_activity']   = time();

        logAuditEvent($conn, $userId, 'REMEMBER_ME_LOGIN', "User '{$user['username']}' authenticated via persistent Remember-Me token.");
        return true;

    } catch (Exception $e) {
        error_log('[StudentHub Auth] Remember-me error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Revokes all Remember-Me tokens for a user from database and clears cookie.
 *
 * @param mysqli $conn
 * @param int $userId
 * @return void
 */
function revokeAllUserRememberTokens(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
    clearRememberMeCookie();
}

/**
 * Clears the client-side Remember-Me cookie.
 *
 * @return void
 */
function clearRememberMeCookie(): void {
    if (isset($_COOKIE['remember_token'])) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        setcookie('remember_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['remember_token']);
    }
}

/**
 * Generates or retrieves a per-session CSRF token.
 *
 * @return string CSRF token
 */
function getCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token.
 *
 * @param string|null $token
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Renders an institutional HTTP 403 Forbidden Access Denied view.
 *
 * @param string $currentRole Current user's role
 * @param string $requiredRole Role required by target resource
 * @return void
 */
function renderAccessDeniedPage(string $currentRole, string $requiredRole): void {
    $returnUrl = ($currentRole === 'Admin') ? 'admin_dashboard.php' : 'student_dashboard.php';
    $requestedPage = htmlspecialchars(basename($_SERVER['SCRIPT_NAME'] ?? 'admin_dashboard.php'), ENT_QUOTES, 'UTF-8');
    
    $resourceName = 'Administrator Portal';
    if (strpos($requestedPage, 'event') !== false) {
        $resourceName = 'Campus Event Management Console';
    } elseif (strpos($requestedPage, 'audit') !== false) {
        $resourceName = 'Institutional Security Audit Trail';
    } elseif (strpos($requestedPage, 'user') !== false) {
        $resourceName = 'User Management & Status Console';
    } elseif (strpos($requestedPage, 'api') !== false) {
        $resourceName = 'Student Management Console';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 - Unauthorized Access</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="error-body">
        <div class="error-card">
            <div class="error-badge-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="error-code">403</div>
            <h1 class="error-title">403 — Unauthorized Access</h1>
            <p class="error-description">
                The <strong><?php echo $resourceName; ?></strong> is strictly restricted to authorized administrators. You do not possess the required security privileges to access this administrative portal.
            </p>
            
            <div class="role-comparison-card">
                <div class="role-item">
                    <span class="role-label">Requested Resource:</span>
                    <span class="role-resource"><code><?php echo $requestedPage; ?></code></span>
                </div>
                <div class="role-item">
                    <span class="role-label">Your Assigned Role:</span>
                    <span class="badge badge-<?php echo strtolower($currentRole); ?>"><?php echo strtoupper($currentRole); ?></span>
                </div>
                <div class="role-separator">&rarr;</div>
                <div class="role-item">
                    <span class="role-label">Required Role:</span>
                    <span class="badge badge-<?php echo strtolower($requiredRole); ?>"><?php echo strtoupper($requiredRole); ?></span>
                </div>
            </div>

            <p class="security-notice">
                <strong>Enforcement Mechanism:</strong> Authoritative server-side Role-Based Access Control (RBAC) in <code>auth.php</code> (HTTP 403 Forbidden emitted, unauthorized operations strictly halted).
            </p>

            <div class="error-actions">
                <a href="<?php echo $returnUrl; ?>" class="btn btn-primary" id="btnReturnDashboard">Return to Student Dashboard</a>
                <a href="logout.php" class="btn btn-secondary" id="btnSignOut">Sign Out</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Generates and returns a cryptographically secure CSRF token in session.
 */
function get_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token against the active session token.
 */
function verify_csrf_token(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sets a flash message to be displayed on the next page view.
 */
function set_flash(string $type, string $message, string $category = 'general'): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'     => $type,
        'message'  => $message,
        'category' => $category
    ];
}

/**
 * Retrieves and clears all pending flash messages.
 */
function get_flashes(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

