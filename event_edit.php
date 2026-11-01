<?php
/**
 * Campus Event Edit & Update Endpoint
 * Practical 12: Campus Event Management with Validated Poster Upload and Role-Based Access
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/event_helpers.php';

// 1. Strict Server-Side Authentication & Role-Based Authorization
requireAuth();
requireRole('Admin');

$conn = getDBConnection();

// Support AJAX fetching of single event data for edit modal prefill
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json; charset=utf-8');
    $eventId = (int)($_GET['id'] ?? 0);
    if ($eventId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid event identifier.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, title, category, status, description, poster_path, event_date, venue, capacity FROM events WHERE id = ? LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database statement error.']);
        exit;
    }

    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $res = $stmt->get_result();
    $event = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event record not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'event' => $event]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: event_management.php');
    exit;
}

// 2. CSRF Token Validation
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Security Error: Invalid or expired session token (CSRF mismatch). Request halted.');
    header('Location: event_management.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid event ID provided for update.');
    header('Location: event_management.php');
    exit;
}

// Fetch existing record
$fetchStmt = $conn->prepare("SELECT id, title, poster_path FROM events WHERE id = ? LIMIT 1");
if (!$fetchStmt) {
    set_flash('error', 'Database query error: ' . $conn->error);
    header('Location: event_management.php');
    exit;
}

$fetchStmt->bind_param('i', $id);
$fetchStmt->execute();
$existing = $fetchStmt->get_result()->fetch_assoc();
$fetchStmt->close();

if (!$existing) {
    set_flash('error', "Event #{$id} does not exist in system records.");
    header('Location: event_management.php');
    exit;
}

$existingPosterPath = $existing['poster_path'];

// 3. Input Extraction & Server-Side Sanitization
$title       = trim($_POST['title'] ?? '');
$category    = trim($_POST['category'] ?? '');
$status      = trim($_POST['status'] ?? 'Upcoming');
$eventDate   = trim($_POST['event_date'] ?? '');
$capacity    = (int)($_POST['capacity'] ?? 0);
$venue       = trim($_POST['venue'] ?? '');
$description = trim($_POST['description'] ?? '');

$validationErrors = [];

if (empty($title)) {
    $validationErrors[] = "Event title is required.";
} elseif (mb_strlen($title) > 150) {
    $validationErrors[] = "Event title cannot exceed 150 characters.";
}

if (empty($category)) {
    $validationErrors[] = "Event category is required.";
}

$validStatuses = ['Upcoming', 'Active', 'Completed'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'Upcoming';
}

if (empty($eventDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    $validationErrors[] = "A valid event date (YYYY-MM-DD) is required.";
}

if ($capacity <= 0 || $capacity > 50000) {
    $validationErrors[] = "Capacity must be a positive integer between 1 and 50,000.";
}

if (empty($venue)) {
    $validationErrors[] = "Event venue is required.";
} elseif (mb_strlen($venue) > 150) {
    $validationErrors[] = "Venue cannot exceed 150 characters.";
}

if (empty($description)) {
    $validationErrors[] = "Event description is required.";
}

// 4. Secure Poster Upload Handling (Optional New Poster)
$newPosterPath = null;
$hasNewPoster = false;

if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadError = null;
    $newPosterPath = handlePosterUpload($_FILES['poster'], $uploadError);
    if ($uploadError !== null) {
        $validationErrors[] = $uploadError;
    } else {
        $hasNewPoster = true;
    }
}

// If validation errors exist, abort and notify user
if (!empty($validationErrors)) {
    if ($hasNewPoster && $newPosterPath && file_exists(__DIR__ . '/' . $newPosterPath)) {
        @unlink(__DIR__ . '/' . $newPosterPath);
    }
    set_flash('error', implode(' ', $validationErrors), 'validation');
    header('Location: event_management.php');
    exit;
}

// Target poster path to store
$finalPosterPath = $hasNewPoster ? $newPosterPath : $existingPosterPath;

// 5. Database Update using MySQLi Prepared Statement
$updateStmt = $conn->prepare("UPDATE events SET title = ?, category = ?, status = ?, description = ?, poster_path = ?, event_date = ?, venue = ?, capacity = ? WHERE id = ?");
if (!$updateStmt) {
    if ($hasNewPoster && $newPosterPath && file_exists(__DIR__ . '/' . $newPosterPath)) {
        @unlink(__DIR__ . '/' . $newPosterPath);
    }
    set_flash('error', 'Database Error: Failed to prepare event update statement: ' . $conn->error);
    header('Location: event_management.php');
    exit;
}

$updateStmt->bind_param('sssssssii', $title, $category, $status, $description, $finalPosterPath, $eventDate, $venue, $capacity, $id);

if ($updateStmt->execute()) {
    $updateStmt->close();

    // If a new poster was successfully assigned, safely remove old poster if not shared
    if ($hasNewPoster && !empty($existingPosterPath) && $existingPosterPath !== $newPosterPath) {
        safelyRemovePoster($conn, $existingPosterPath, $id);
    }

    logAuditEvent($conn, (int)$_SESSION['user_id'], 'EVENT_UPDATED', "Event #{$id} '{$title}' updated.");

    // Flash message matching Figure 4 format
    set_flash('success', "Event #{$id} `{$title}' updated successfully with updated details!");
} else {
    $dbErr = $updateStmt->error;
    $updateStmt->close();

    // Cleanup newly uploaded file on database failure
    if ($hasNewPoster && $newPosterPath && file_exists(__DIR__ . '/' . $newPosterPath)) {
        @unlink(__DIR__ . '/' . $newPosterPath);
    }

    set_flash('error', 'Database Error: Failed to update event record: ' . $dbErr);
}

header('Location: event_management.php');
exit;
