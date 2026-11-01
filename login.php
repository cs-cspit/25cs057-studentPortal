<?php
/**
 * StudentHub Secure Login Portal
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

// If user is already authenticated, route directly to appropriate dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = getCSRFToken();

// Parse alert message or error code
$error = $_GET['error'] ?? '';
$msg   = $_GET['msg'] ?? '';

$alerts = [
    'logged_out' => [
        'type' => 'success',
        'title' => 'Logged Out Successfully',
        'text' => 'Your authenticated session has been completely cleared and security tokens revoked.'
    ],
    'empty' => [
        'type' => 'warning',
        'title' => 'Missing Credentials',
        'text' => 'Please provide both your registered email or username and password.'
    ],
    'malformed_email' => [
        'type' => 'warning',
        'title' => 'Malformed Email Format',
        'text' => 'Please enter a valid email address structure (e.g., student@studenthub.edu).'
    ],
    'invalid' => [
        'type' => 'danger',
        'title' => 'Authentication Failed',
        'text' => 'Invalid email or password. Please verify your credentials and try again.'
    ],
    'login_required' => [
        'type' => 'info',
        'title' => 'Authentication Required',
        'text' => 'Please log in with your credentials to access the protected StudentHub portal.'
    ],
    'timeout' => [
        'type' => 'warning',
        'title' => 'Session Inactivity Timeout',
        'text' => 'Your session was automatically invalidated due to inactivity. Please sign in again.'
    ],
    'unauthorized' => [
        'type' => 'danger',
        'title' => 'Unauthorized Access',
        'text' => 'Your account does not possess the permissions required for that resource.'
    ],
    'inactive_account' => [
        'type' => 'danger',
        'title' => 'Account Deactivated',
        'text' => 'Your student account has been marked Inactive by the system administrator. Please contact academic IT services.'
    ]
];

$activeAlert = null;
if (!empty($msg) && isset($alerts[$msg])) {
    $activeAlert = $alerts[$msg];
} elseif (!empty($error) && isset($alerts[$error])) {
    $activeAlert = $alerts[$error];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub — Secure Institutional Login Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <!-- University Institutional Header -->
        <header class="portal-header">
            <div class="logo-group">
                <img src="logos/charusat_logo.jpeg" alt="CHARUSAT Logo" class="brand-logo" onerror="this.style.display='none'">
                <img src="logos/cspit_logo.jpeg" alt="CSPIT Logo" class="brand-logo" onerror="this.style.display='none'">
            </div>
            <div class="institution-meta">
                <span class="inst-name">CHAROTAR UNIVERSITY OF SCIENCE AND TECHNOLOGY</span>
                <span class="dept-name">CSPIT &bull; Department of Computer Science & Engineering</span>
                <span class="course-badge">ITUE203 — Web Development Frameworks &bull; Practical 10</span>
            </div>
        </header>

        <!-- Main Authentication Card -->
        <main class="auth-card" id="loginCard">
            <div class="card-hero">
                <div class="shield-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h2>StudentHub Secure Login</h2>
                <p class="subtitle">Access role-based portals with session protection & RBAC</p>
            </div>

            <!-- Dynamic Feedback Alert -->
            <?php if ($activeAlert): ?>
                <div class="alert alert-<?php echo htmlspecialchars($activeAlert['type']); ?>" role="alert" id="authAlert">
                    <div class="alert-icon">
                        <?php if ($activeAlert['type'] === 'success'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php elseif ($activeAlert['type'] === 'danger'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        <?php elseif ($activeAlert['type'] === 'warning'): ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <?php else: ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <?php endif; ?>
                    </div>
                    <div class="alert-content">
                        <strong><?php echo htmlspecialchars($activeAlert['title']); ?>:</strong>
                        <span><?php echo htmlspecialchars($activeAlert['text']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="authenticate.php" method="POST" class="auth-form" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Identifier Input -->
                <div class="form-group">
                    <label for="email">Email or Username <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" name="email" id="email" class="form-control" placeholder="e.g., student@studenthub.edu" required autofocus autocomplete="username">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <div class="label-row">
                        <label for="password">Password <span class="required">*</span></label>
                        <span class="hash-note">Verified via <code>password_verify()</code></span>
                    </div>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter account password" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-pw" id="togglePassword" aria-label="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember-Me & Security Settings -->
                <div class="form-row-between">
                    <label class="checkbox-container" for="remember_me">
                        <input type="checkbox" name="remember_me" id="remember_me" value="1">
                        <span class="custom-checkbox"></span>
                        <span class="checkbox-label">Remember me on this device</span>
                    </label>
                    <span class="token-spec-pill" title="Cryptographically random selector & hashed token">Secure SHA-256</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-block" id="submitLoginBtn">
                    <span>Sign In to StudentHub</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>

            <!-- Test Credentials Quick-Fill (For Lab & Evaluation Convenience) -->
            <div class="test-helper-card">
                <div class="test-helper-header">
                    <span>Quick Lab Evaluation Credentials:</span>
                </div>
                <div class="chip-container">
                    <button type="button" class="chip chip-student" onclick="fillCredentials('student@studenthub.edu', 'Student@123')">
                        <span class="chip-badge">STUDENT</span>
                        <span class="chip-email">student@studenthub.edu</span>
                        <span class="chip-pw">(Student@123)</span>
                    </button>
                    <button type="button" class="chip chip-admin" onclick="fillCredentials('admin@studenthub.edu', 'Admin@123')">
                        <span class="chip-badge">ADMIN</span>
                        <span class="chip-email">admin@studenthub.edu</span>
                        <span class="chip-pw">(Admin@123)</span>
                    </button>
                </div>
            </div>

            <!-- Portal Links -->
            <div style="text-align:center;margin-top:16px;font-size:13.5px;color:#64748b;">
                Don't have an account? <a href="register.php" style="color:#2563eb;font-weight:600;text-decoration:none;">Create Student Account</a> &bull; <a href="index.php" style="color:#2563eb;text-decoration:none;">Return to Portal Home</a>
            </div>

            <!-- Security Assurance Footer -->
            <footer class="card-footer-info">
                <div class="security-features">
                    <span class="sec-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Prepared Statements</span>
                    <span class="sec-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Session ID Regeneration</span>
                    <span class="sec-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Server-Side RBAC</span>
                    <span class="sec-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Inactivity Timeout</span>
                </div>
            </footer>
        </main>
    </div>

    <script>
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
            });
        }
    </script>
</body>
</html>
