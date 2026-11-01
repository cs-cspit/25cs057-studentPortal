<?php
/**
 * StudentHub — Secure Student Registration Portal
 * Practical 9 & Practical 15: Student Registration with Prepared Statements,
 * Duplicate Checking, Password Hashing, and Automatic Profile Initialization
 * 
 * Student: Krupal Parmar | Student ID: 25CS057
 * Institute: Chandubhai S. Patel Institute of Technology (CSPIT)
 * University: Charotar University of Science and Technology (CHARUSAT)
 * Subject: Web Development Frameworks (ITUE203)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

// Generate cryptographically secure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = getDBConnection();

$errors         = [];
$isSubmitted    = false;
$isSuccess      = false;
$registeredUser = null;

// Form values
$fullName      = '';
$studentIdCode = '';
$department    = 'Computer Science & Engineering';
$course        = 'B.Tech Computer Science & Engineering';
$year          = 3;
$username      = '';
$email         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isSubmitted = true;

    // 1. Verify CSRF Token
    $postCsrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postCsrf)) {
        $errors['csrf'] = 'Security validation failed (CSRF token mismatch). Please refresh and resubmit.';
    }

    // 2. Retrieve & Sanitize Inputs
    $fullName      = trim((string)($_POST['full_name'] ?? ''));
    $studentIdCode = strtoupper(trim((string)($_POST['student_id_code'] ?? '')));
    $department    = trim((string)($_POST['department'] ?? 'Computer Science & Engineering'));
    $course        = trim((string)($_POST['course'] ?? 'B.Tech Computer Science & Engineering'));
    $year          = max(1, min(4, (int)($_POST['year'] ?? 1)));
    $username      = trim((string)($_POST['username'] ?? ''));
    $email         = strtolower(trim((string)($_POST['email'] ?? '')));
    $password      = (string)($_POST['password'] ?? '');
    $confirmPass   = (string)($_POST['confirm_password'] ?? '');

    // 3. Validation Logic
    if (empty($fullName)) {
        $errors['full_name'] = 'Full Name is required.';
    } elseif (strlen($fullName) < 3) {
        $errors['full_name'] = 'Full Name must be at least 3 characters long.';
    }

    if (empty($studentIdCode)) {
        $errors['student_id_code'] = 'Student ID (Enrollment Number) is required.';
    } elseif (!preg_match('/^[0-9]{2}[A-Za-z]{2,3}[0-9]{3}$/', $studentIdCode)) {
        // E.g. 25CS057 or 24IT033
        $errors['student_id_code'] = 'Student ID must follow university format (e.g., 25CS057).';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username can only contain alphanumeric characters and underscores.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address structure.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if (empty($confirmPass)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPass) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 4. Duplicate Checks via MySQLi Prepared Statements
    if (empty($errors)) {
        // Check duplicate username
        $stmtUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($stmtUser) {
            $stmtUser->bind_param("s", $username);
            $stmtUser->execute();
            $stmtUser->store_result();
            if ($stmtUser->num_rows > 0) {
                $errors['username'] = "The username '{$username}' is already taken. Please choose another.";
            }
            $stmtUser->close();
        }

        // Check duplicate email
        $stmtEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmtEmail) {
            $stmtEmail->bind_param("s", $email);
            $stmtEmail->execute();
            $stmtEmail->store_result();
            if ($stmtEmail->num_rows > 0) {
                $errors['email'] = "An account with email '{$email}' is already registered.";
            }
            $stmtEmail->close();
        }
    }

    // 5. Database Insertion (Users and Students Entities)
    if (empty($errors)) {
        try {
            // Hash password with Bcrypt
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert User Account
            $insUser = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status, full_name, department, student_id_code) VALUES (?, ?, ?, 'Student', 'Active', ?, ?, ?)");
            $insUser->bind_param("ssssss", $username, $email, $passwordHash, $fullName, $department, $studentIdCode);
            $insUser->execute();
            $newUserId = (int)$conn->insert_id;
            $insUser->close();

            // Insert / Sync Student Record in `students` table
            $insStudent = $conn->prepare("INSERT INTO students (student_id, name, email, department, course, year, status) 
                                         VALUES (?, ?, ?, ?, ?, ?, 'Active') 
                                         ON DUPLICATE KEY UPDATE name = VALUES(name), department = VALUES(department), course = VALUES(course), year = VALUES(year)");
            $insStudent->bind_param("sssssi", $studentIdCode, $fullName, $email, $department, $course, $year);
            $insStudent->execute();
            $insStudent->close();

            // Record Security Audit Log
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
            $desc = "Student account created for '{$username}' ({$studentIdCode}) with Bcrypt password hashing.";

            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description, username, email, status, ip_address, user_agent, details, event_type) 
                                      VALUES (?, 'REGISTER', ?, ?, ?, 'SUCCESS', ?, ?, ?, 'REGISTER')");
            if ($logStmt) {
                $logStmt->bind_param("issssss", $newUserId, $desc, $username, $email, $ipAddress, $userAgent, $desc);
                $logStmt->execute();
                $logStmt->close();
            }

            $isSuccess = true;
            $registeredUser = [
                'id'       => $newUserId,
                'name'     => $fullName,
                'code'     => $studentIdCode,
                'username' => $username,
                'email'    => $email
            ];

            // Clear plain passwords from memory
            unset($password, $confirmPass);

            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        } catch (Exception $ex) {
            $errors['db'] = 'Registration failed: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration — StudentHub | CHARUSAT CSPIT</title>
    
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        .register-body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            font-family: 'Inter', sans-serif;
        }
        .register-container {
            width: 100%;
            max-width: 680px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .register-header h2 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 800;
        }
        .register-header p {
            margin: 0;
            color: #bfdbfe;
            font-size: 13.5px;
        }
        .register-content {
            padding: 32px 36px;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.15s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .field-error {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            display: block;
            font-weight: 500;
        }
        .btn-register-submit {
            width: 100%;
            background: #2563eb;
            color: #ffffff;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
            margin-top: 10px;
        }
        .btn-register-submit:hover {
            background: #1d4ed8;
        }
        .alert-box {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 22px;
        }
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .register-footer-links {
            text-align: center;
            margin-top: 24px;
            font-size: 13.5px;
            color: #64748b;
        }
        .register-footer-links a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
            .register-content { padding: 24px 20px; }
        }
    </style>
</head>
<body class="register-body">

    <div class="register-container">
        <div class="register-header">
            <div style="display:flex;justify-content:center;gap:8px;margin-bottom:12px;">
                <?php if (file_exists(__DIR__ . '/logos/charusat_logo.jpeg')): ?>
                    <img src="logos/charusat_logo.jpeg" alt="CHARUSAT" style="height:36px;background:#fff;border-radius:4px;padding:2px;">
                <?php endif; ?>
                <?php if (file_exists(__DIR__ . '/logos/cspit_logo.jpeg')): ?>
                    <img src="logos/cspit_logo.jpeg" alt="CSPIT" style="height:36px;background:#fff;border-radius:4px;padding:2px;">
                <?php endif; ?>
            </div>
            <h2>StudentHub Registration</h2>
            <p>Enroll in the CHARUSAT Academic Management System</p>
        </div>

        <div class="register-content">

            <?php if (!empty($errors)): ?>
                <div class="alert-box alert-danger">
                    <strong>Please resolve the following errors:</strong>
                    <ul style="margin:6px 0 0;padding-left:20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($isSuccess && $registeredUser): ?>
                <div class="alert-box alert-success" style="text-align:center;">
                    <div style="font-size:24px;margin-bottom:8px;">✅</div>
                    <h3 style="margin:0 0 8px;font-size:18px;">Registration Successful!</h3>
                    <p style="margin:0 0 16px;">
                        Welcome <strong><?php echo htmlspecialchars($registeredUser['name']); ?></strong> (<?php echo htmlspecialchars($registeredUser['code']); ?>)! 
                        Your student account has been created with secure password hashing.
                    </p>
                    <a href="login.php" class="btn-register-submit" style="display:inline-block;text-decoration:none;padding:10px 24px;width:auto;">
                        Proceed to Portal Login &rarr;
                    </a>
                </div>
            <?php else: ?>

                <form method="POST" action="register.php" id="registrationForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name *</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g., Krupal Parmar" value="<?php echo htmlspecialchars($fullName); ?>" required>
                            <?php if (isset($errors['full_name'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['full_name']); ?></span><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student_id_code">Student ID (Enrollment) *</label>
                            <input type="text" name="student_id_code" id="student_id_code" class="form-control" placeholder="e.g., 25CS057" value="<?php echo htmlspecialchars($studentIdCode); ?>" required>
                            <?php if (isset($errors['student_id_code'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['student_id_code']); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="department">Department *</label>
                            <select name="department" id="department" class="form-control">
                                <option value="Computer Science & Engineering" <?php echo ($department === 'Computer Science & Engineering') ? 'selected' : ''; ?>>Computer Science &amp; Engineering</option>
                                <option value="Information Technology" <?php echo ($department === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                <option value="Electronics & Communication" <?php echo ($department === 'Electronics & Communication') ? 'selected' : ''; ?>>Electronics &amp; Communication</option>
                                <option value="Mechanical Engineering" <?php echo ($department === 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                <option value="Civil Engineering" <?php echo ($department === 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="year">Year of Study *</label>
                            <select name="year" id="year" class="form-control">
                                <option value="1" <?php echo ($year === 1) ? 'selected' : ''; ?>>1st Year (Fresher)</option>
                                <option value="2" <?php echo ($year === 2) ? 'selected' : ''; ?>>2nd Year (Sophomore)</option>
                                <option value="3" <?php echo ($year === 3) ? 'selected' : ''; ?>>3rd Year (Junior)</option>
                                <option value="4" <?php echo ($year === 4) ? 'selected' : ''; ?>>4th Year (Senior)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="username">Username *</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="e.g., krupal_parmar" value="<?php echo htmlspecialchars($username); ?>" required>
                            <?php if (isset($errors['username'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['username']); ?></span><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Institutional Email *</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="e.g., 25cs057@charusat.edu.in" value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['email']); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                            <?php if (isset($errors['password'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['password']); ?></span><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                            <?php if (isset($errors['confirm_password'])): ?><span class="field-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn-register-submit" id="submitRegisterBtn">
                        Create Student Account
                    </button>
                </form>

            <?php endif; ?>

            <div class="register-footer-links">
                Already registered? <a href="login.php">Sign in to your portal</a> &bull; <a href="index.php">Return to Home</a>
            </div>

        </div>
    </div>

    <!-- Client-Side Form Validation Script (Practical 5) -->
    <script>
        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            const pass = document.getElementById('password')?.value;
            const confirm = document.getElementById('confirm_password')?.value;
            const email = document.getElementById('email')?.value;

            if (pass && confirm && pass !== confirm) {
                e.preventDefault();
                alert('Validation Alert: Passwords do not match. Please ensure both passwords match.');
                return false;
            }
            if (pass && pass.length < 6) {
                e.preventDefault();
                alert('Validation Alert: Password must be at least 6 characters long.');
                return false;
            }
        });
    </script>
</body>
</html>
