<?php
/**
 * StudentHub API: Records Collection Endpoint (List & Create)
 * Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD Without Page Reload
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 *
 * Supported Methods:
 * - GET:  List student records with optional search, filters, and sorting (Prepared statements).
 * - POST: Create a new student record with strict server-side validation, duplicate prevention, and CSRF check.
 */

require_once __DIR__ . '/db.php';

// Route by HTTP Request Method
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    handleListRecords();
} elseif ($method === 'POST') {
    handleCreateRecord();
} else {
    sendJsonResponse(false, "HTTP method {$method} is not allowed on /api/records.php.", null, null, 405);
}

/**
 * Handles GET /api/records.php
 * Retrieves filtered or complete list of student records from MySQL using prepared statements.
 */
function handleListRecords(): void {
    // Both Admin and Student authenticated users are permitted to view records
    apiRequireAuth();

    $conn = getDBConnection();

    // Query parameters
    $search     = trim((string)($_GET['q'] ?? $_GET['search'] ?? ''));
    $department = trim((string)($_GET['department'] ?? ''));
    $status     = trim((string)($_GET['status'] ?? ''));
    $year       = (int)($_GET['year'] ?? 0);
    $sortBy     = trim((string)($_GET['sort'] ?? 'id'));
    $sortOrder  = strtoupper(trim((string)($_GET['order'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';

    // Whitelist sort columns to eliminate SQL injection vectors
    $allowedSortColumns = [
        'id'         => 'id',
        'student_id' => 'student_id',
        'name'       => 'name',
        'email'      => 'email',
        'department' => 'department',
        'course'     => 'course',
        'year'       => 'year',
        'status'     => 'status',
        'created_at' => 'created_at'
    ];
    $sortColumn = $allowedSortColumns[$sortBy] ?? 'id';

    // Build dynamic query with prepared statements
    $conditions = [];
    $paramTypes = '';
    $paramValues = [];

    if ($search !== '') {
        $conditions[] = "(student_id LIKE ? OR name LIKE ? OR email LIKE ? OR department LIKE ? OR course LIKE ?)";
        $searchWildcard = '%' . $search . '%';
        $paramTypes .= 'sssss';
        $paramValues[] = $searchWildcard;
        $paramValues[] = $searchWildcard;
        $paramValues[] = $searchWildcard;
        $paramValues[] = $searchWildcard;
        $paramValues[] = $searchWildcard;
    }

    if ($department !== '' && $department !== 'All') {
        $conditions[] = "department = ?";
        $paramTypes .= 's';
        $paramValues[] = $department;
    }

    if ($status !== '' && in_array($status, ['Active', 'Inactive'], true)) {
        $conditions[] = "status = ?";
        $paramTypes .= 's';
        $paramValues[] = $status;
    }

    if ($year >= 1 && $year <= 4) {
        $conditions[] = "year = ?";
        $paramTypes .= 'i';
        $paramValues[] = $year;
    }

    $sql = "SELECT id, student_id, name, email, department, course, year, status, created_at, updated_at FROM students";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY {$sortColumn} {$sortOrder}";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendJsonResponse(false, 'Failed to prepare database query: ' . $conn->error, null, null, 500);
    }

    if (!empty($paramValues)) {
        $stmt->bind_param($paramTypes, ...$paramValues);
    }

    if (!$stmt->execute()) {
        sendJsonResponse(false, 'Failed to execute query: ' . $stmt->error, null, null, 500);
    }

    $result = $stmt->get_result();
    $records = [];
    while ($row = $result->fetch_assoc()) {
        // Cast types appropriately
        $row['id']   = (int)$row['id'];
        $row['year'] = (int)$row['year'];
        $records[]   = $row;
    }
    $stmt->close();

    // Summary statistics for dashboard badges
    $statsQuery = $conn->query("SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_count,
        SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS inactive_count
        FROM students");
    $stats = $statsQuery ? $statsQuery->fetch_assoc() : ['total_count' => count($records), 'active_count' => 0, 'inactive_count' => 0];

    sendJsonResponse(true, count($records) > 0 ? 'Records retrieved successfully.' : 'No records found matching criteria.', [
        'records'       => $records,
        'count'         => count($records),
        'total_records' => (int)($stats['total_count'] ?? 0),
        'active_count'  => (int)($stats['active_count'] ?? 0),
        'inactive_count'=> (int)($stats['inactive_count'] ?? 0),
        'user_role'     => $_SESSION['role'] ?? 'Student',
        'is_admin'      => ($_SESSION['role'] ?? '') === 'Admin'
    ]);
}

/**
 * Handles POST /api/records.php
 * Validates submitted fields, verifies uniqueness, and creates a student record.
 */
function handleCreateRecord(): void {
    // Strictly require Administrator role and valid CSRF token
    apiRequireAdmin();
    $payload = getRequestPayload();
    apiValidateCsrf($payload);

    $conn = getDBConnection();

    // Sanitize and extract incoming inputs
    $studentId  = strtoupper(trim((string)($payload['student_id'] ?? '')));
    $name       = trim((string)($payload['name'] ?? ''));
    $email      = strtolower(trim((string)($payload['email'] ?? '')));
    $department = trim((string)($payload['department'] ?? ''));
    $course     = trim((string)($payload['course'] ?? ''));
    $year       = (int)($payload['year'] ?? 0);
    $status     = trim((string)($payload['status'] ?? 'Active'));

    $errors = [];

    // 1. Validate Student ID
    if ($studentId === '') {
        $errors['student_id'] = 'Student ID / Enrollment Number is required.';
    } elseif (strlen($studentId) < 4 || strlen($studentId) > 20) {
        $errors['student_id'] = 'Student ID must be between 4 and 20 characters.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/i', $studentId)) {
        $errors['student_id'] = 'Student ID can only contain letters, numbers, hyphens, and underscores (e.g. 25CS057).';
    } else {
        // Prevent duplicate Student ID
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? LIMIT 1");
        $checkStmt->bind_param('s', $studentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult && $checkResult->num_rows > 0) {
            $errors['student_id'] = "Student ID '{$studentId}' is already registered in the system.";
        }
        $checkStmt->close();
    }

    // 2. Validate Full Name
    if ($name === '') {
        $errors['name'] = 'Full Name is required.';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors['name'] = 'Full Name must be between 2 and 100 characters.';
    } elseif (!preg_match("/^[a-zA-Z\s.'-]+$/", $name)) {
        $errors['name'] = 'Full Name contains invalid characters.';
    }

    // 3. Validate Email Format & Uniqueness
    if ($email === '') {
        $errors['email'] = 'Institutional Email Address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        $errors['email'] = 'Please enter a valid email address (e.g. student@charusat.edu.in).';
    } else {
        // Prevent duplicate email address
        $checkEmailStmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
        $checkEmailStmt->bind_param('s', $email);
        $checkEmailStmt->execute();
        $checkEmailRes = $checkEmailStmt->get_result();
        if ($checkEmailRes && $checkEmailRes->num_rows > 0) {
            $errors['email'] = "Email address '{$email}' is already registered.";
        }
        $checkEmailStmt->close();
    }

    // 4. Validate Department
    if ($department === '') {
        $errors['department'] = 'Department selection is required.';
    } elseif (strlen($department) > 100) {
        $errors['department'] = 'Department name cannot exceed 100 characters.';
    }

    // 5. Validate Course
    if ($course === '') {
        $errors['course'] = 'Degree Course / Program is required.';
    } elseif (strlen($course) > 100) {
        $errors['course'] = 'Course title cannot exceed 100 characters.';
    }

    // 6. Validate Academic Year
    if ($year < 1 || $year > 4) {
        $errors['year'] = 'Academic Year must be between 1 and 4.';
    }

    // 7. Validate Status
    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $status = 'Active';
    }

    // Return HTTP 422 if any validation constraints failed
    if (!empty($errors)) {
        sendJsonResponse(false, 'Validation failed. Please correct the highlighted errors.', null, $errors, 422);
    }

    // Insert record with MySQLi Prepared Statement
    $insertStmt = $conn->prepare("INSERT INTO students (student_id, name, email, department, course, year, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$insertStmt) {
        sendJsonResponse(false, 'Database insert preparation failed: ' . $conn->error, null, null, 500);
    }

    $insertStmt->bind_param('sssssis', $studentId, $name, $email, $department, $course, $year, $status);
    $executed = $insertStmt->execute();

    if (!$executed) {
        $err = $insertStmt->error;
        $insertStmt->close();
        sendJsonResponse(false, 'Failed to create student record: ' . $err, null, null, 500);
    }

    $newId = $insertStmt->insert_id;
    $insertStmt->close();

    // Query newly created record
    $fetchStmt = $conn->prepare("SELECT id, student_id, name, email, department, course, year, status, created_at, updated_at FROM students WHERE id = ?");
    $fetchStmt->bind_param('i', $newId);
    $fetchStmt->execute();
    $newRecord = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    if ($newRecord) {
        $newRecord['id'] = (int)$newRecord['id'];
        $newRecord['year'] = (int)$newRecord['year'];
    }

    // Security Audit Logging
    logAuditEvent(
        $conn,
        (int)($_SESSION['user_id'] ?? 0),
        'STUDENT_CREATED',
        "Created student record {$studentId} ({$name}) in department {$department}."
    );

    sendJsonResponse(true, "Student record for '{$name}' ({$studentId}) was created successfully.", $newRecord, null, 201);
}
