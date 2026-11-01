<?php
/**
 * StudentHub API: Single Record Resource Endpoint (Retrieve, Update, Delete)
 * Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD Without Page Reload
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 *
 * Supported Methods:
 * - GET:    Retrieve individual student record by ID.
 * - PUT:    Update existing student record with validation and uniqueness checks.
 * - DELETE: Delete student record safely with confirmation and audit logging.
 *
 * Also supports POST with `_method=PUT` / `_method=DELETE` or `action=update` / `action=delete`
 * and `X-HTTP-Method-Override` headers for maximum client compatibility.
 */

require_once __DIR__ . '/db.php';

// Detect actual HTTP method, accounting for HTTP verb spoofing / override
$rawMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$overrideMethod = '';

if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $overrideMethod = strtoupper(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));
}

$payload = getRequestPayload();

if ($rawMethod === 'POST') {
    if (!empty($overrideMethod)) {
        $method = $overrideMethod;
    } elseif (!empty($payload['_method'])) {
        $method = strtoupper(trim((string)$payload['_method']));
    } elseif (!empty($_GET['_method'])) {
        $method = strtoupper(trim((string)$_GET['_method']));
    } elseif (!empty($payload['action']) && in_array(strtolower($payload['action']), ['update', 'delete', 'put'], true)) {
        $method = strtolower($payload['action']) === 'delete' ? 'DELETE' : 'PUT';
    } elseif (!empty($_GET['action']) && in_array(strtolower($_GET['action']), ['update', 'delete'], true)) {
        $method = strtolower($_GET['action']) === 'delete' ? 'DELETE' : 'PUT';
    } else {
        $method = 'POST';
    }
} else {
    $method = $rawMethod;
}

// Route to corresponding handler
switch ($method) {
    case 'GET':
        handleGetRecord();
        break;

    case 'PUT':
    case 'PATCH':
        handleUpdateRecord($payload);
        break;

    case 'DELETE':
        handleDeleteRecord($payload);
        break;

    default:
        sendJsonResponse(false, "Method {$method} is not supported on /api/record.php.", null, null, 405);
        break;
}

/**
 * Handles GET /api/record.php?id={id}
 * Retrieves an individual student record by its primary key ID.
 */
function handleGetRecord(): void {
    apiRequireAuth();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 'Invalid student record ID provided.', null, ['id' => 'Valid positive integer ID is required.'], 400);
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, student_id, name, email, department, course, year, status, created_at, updated_at FROM students WHERE id = ? LIMIT 1");
    if (!$stmt) {
        sendJsonResponse(false, 'Database preparation failed: ' . $conn->error, null, null, 500);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$record) {
        sendJsonResponse(false, "Student record with ID #{$id} was not found.", null, null, 404);
    }

    $record['id'] = (int)$record['id'];
    $record['year'] = (int)$record['year'];

    sendJsonResponse(true, 'Student record retrieved successfully.', $record, null, 200);
}

/**
 * Handles PUT /api/record.php?id={id}
 * Updates an existing record after validating input fields and checking for uniqueness.
 *
 * @param array $payload Request payload
 */
function handleUpdateRecord(array $payload): void {
    apiRequireAdmin();
    apiValidateCsrf($payload);

    $conn = getDBConnection();

    // Determine ID from query parameter or body payload
    $id = (int)($_GET['id'] ?? $payload['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 'Valid student record ID is required for update.', null, ['id' => 'Missing or invalid record ID.'], 400);
    }

    // Verify record exists before updating
    $existStmt = $conn->prepare("SELECT id, student_id, name FROM students WHERE id = ? LIMIT 1");
    $existStmt->bind_param('i', $id);
    $existStmt->execute();
    $existing = $existStmt->get_result()->fetch_assoc();
    $existStmt->close();

    if (!$existing) {
        sendJsonResponse(false, "Cannot update: Record with ID #{$id} does not exist.", null, null, 404);
    }

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
        $errors['student_id'] = 'Student ID can only contain letters, numbers, hyphens, and underscores.';
    } else {
        // Prevent duplicate Student ID across other records
        $checkStmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param('si', $studentId, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult && $checkResult->num_rows > 0) {
            $errors['student_id'] = "Student ID '{$studentId}' is already registered to another student.";
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
        $errors['email'] = 'Please enter a valid institutional email address.';
    } else {
        // Prevent duplicate email address across other records
        $checkEmailStmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
        $checkEmailStmt->bind_param('si', $email, $id);
        $checkEmailStmt->execute();
        $checkEmailRes = $checkEmailStmt->get_result();
        if ($checkEmailRes && $checkEmailRes->num_rows > 0) {
            $errors['email'] = "Email address '{$email}' is already registered to another student.";
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

    if (!empty($errors)) {
        sendJsonResponse(false, 'Validation failed. Please correct the highlighted errors.', null, $errors, 422);
    }

    // Execute Prepared Update
    $updateStmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, email = ?, department = ?, course = ?, year = ?, status = ? WHERE id = ?");
    if (!$updateStmt) {
        sendJsonResponse(false, 'Update preparation failed: ' . $conn->error, null, null, 500);
    }

    $updateStmt->bind_param('sssssisi', $studentId, $name, $email, $department, $course, $year, $status, $id);
    $executed = $updateStmt->execute();

    if (!$executed) {
        $err = $updateStmt->error;
        $updateStmt->close();
        sendJsonResponse(false, 'Failed to update record: ' . $err, null, null, 500);
    }
    $updateStmt->close();

    // Fetch the updated record
    $fetchStmt = $conn->prepare("SELECT id, student_id, name, email, department, course, year, status, created_at, updated_at FROM students WHERE id = ?");
    $fetchStmt->bind_param('i', $id);
    $fetchStmt->execute();
    $updatedRecord = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    if ($updatedRecord) {
        $updatedRecord['id'] = (int)$updatedRecord['id'];
        $updatedRecord['year'] = (int)$updatedRecord['year'];
    }

    // Security Audit Logging
    logAuditEvent(
        $conn,
        (int)($_SESSION['user_id'] ?? 0),
        'STUDENT_UPDATED',
        "Updated student ID #{$id} ({$studentId} - {$name})."
    );

    sendJsonResponse(true, "Student record for '{$name}' ({$studentId}) updated successfully.", $updatedRecord, null, 200);
}

/**
 * Handles DELETE /api/record.php?id={id}
 * Deletes a student record safely after verifying existence and authorization.
 *
 * @param array $payload Request payload
 */
function handleDeleteRecord(array $payload): void {
    apiRequireAdmin();
    apiValidateCsrf($payload);

    $conn = getDBConnection();

    // Determine ID from query parameter or body payload
    $id = (int)($_GET['id'] ?? $payload['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(false, 'Valid student record ID is required for deletion.', null, ['id' => 'Missing or invalid record ID.'], 400);
    }

    // Verify record exists before deletion
    $existStmt = $conn->prepare("SELECT id, student_id, name FROM students WHERE id = ? LIMIT 1");
    $existStmt->bind_param('i', $id);
    $existStmt->execute();
    $existing = $existStmt->get_result()->fetch_assoc();
    $existStmt->close();

    if (!$existing) {
        sendJsonResponse(false, "Cannot delete: Record with ID #{$id} does not exist.", null, null, 404);
    }

    $studentId   = $existing['student_id'];
    $studentName = $existing['name'];

    // Execute Prepared Delete
    $delStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    if (!$delStmt) {
        sendJsonResponse(false, 'Delete preparation failed: ' . $conn->error, null, null, 500);
    }

    $delStmt->bind_param('i', $id);
    $deleted = $delStmt->execute();

    if (!$deleted) {
        $err = $delStmt->error;
        $delStmt->close();
        sendJsonResponse(false, 'Failed to delete student record: ' . $err, null, null, 500);
    }
    $delStmt->close();

    // Security Audit Logging
    logAuditEvent(
        $conn,
        (int)($_SESSION['user_id'] ?? 0),
        'STUDENT_DELETED',
        "Deleted student record ID #{$id} ({$studentId} - {$studentName})."
    );

    sendJsonResponse(true, "Student record for '{$studentName}' ({$studentId}) was safely deleted.", [
        'id'         => $id,
        'student_id' => $studentId,
        'name'       => $studentName
    ], null, 200);
}
