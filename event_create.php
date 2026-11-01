<?php
/**
 * Campus Event Creation Endpoint
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

// 4. Secure Poster Upload Handling & Validation
$posterPath = null;
$uploadError = null;

if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
    $posterPath = handlePosterUpload($_FILES['poster'], $uploadError);
    if ($uploadError !== null) {
        $validationErrors[] = $uploadError;
    }
}

// If validation errors exist, abort and notify user
if (!empty($validationErrors)) {
    // If a poster was uploaded before encountering another validation error, clean it up immediately
    if ($posterPath && file_exists(__DIR__ . '/' . $posterPath)) {
        @unlink(__DIR__ . '/' . $posterPath);
    }
    set_flash('error', implode(' ', $validationErrors), 'validation');
    header('Location: event_management.php');
    exit;
}

// 5. Database Insertion using MySQLi Prepared Statement
$conn = getDBConnection();

$stmt = $conn->prepare("INSERT INTO events (title, category, status, description, poster_path, event_date, venue, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    if ($posterPath && file_exists(__DIR__ . '/' . $posterPath)) {
        @unlink(__DIR__ . '/' . $posterPath);
    }
    set_flash('error', 'Database Error: Failed to prepare event creation statement: ' . $conn->error);
    header('Location: event_management.php');
    exit;
}

$stmt->bind_param('sssssssi', $title, $category, $status, $description, $posterPath, $eventDate, $venue, $capacity);

if ($stmt->execute()) {
    $newEventId = $conn->insert_id;
    $stmt->close();

    // Log administrative audit event
    logAuditEvent($conn, (int)$_SESSION['user_id'], 'EVENT_CREATED', "Event #{$newEventId} '{$title}' created with poster '{$posterPath}'.");

    // Success notification matching Figure 2 format
    set_flash('success', "Event #{$newEventId} `{$title}' created successfully with verified poster upload!");
} else {
    $dbErr = $stmt->error;
    $stmt->close();

    // Cleanup newly uploaded file on database failure (Rule: prevent orphaned files)
    if ($posterPath && file_exists(__DIR__ . '/' . $posterPath)) {
        @unlink(__DIR__ . '/' . $posterPath);
    }

    set_flash('error', 'Database Error: Failed to save event record: ' . $dbErr);
}

header('Location: event_management.php');
exit;
