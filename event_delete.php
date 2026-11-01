<?php
/**
 * Campus Event Deletion Endpoint
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

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid event ID provided for deletion.');
    header('Location: event_management.php');
    exit;
}

$conn = getDBConnection();

// Fetch target event details before deletion
$fetchStmt = $conn->prepare("SELECT id, title, poster_path FROM events WHERE id = ? LIMIT 1");
if (!$fetchStmt) {
    set_flash('error', 'Database query error: ' . $conn->error);
    header('Location: event_management.php');
    exit;
}

$fetchStmt->bind_param('i', $id);
$fetchStmt->execute();
$event = $fetchStmt->get_result()->fetch_assoc();
$fetchStmt->close();

if (!$event) {
    set_flash('error', "Event #{$id} does not exist in system records.");
    header('Location: event_management.php');
    exit;
}

$title = $event['title'];
$posterPath = $event['poster_path'];

// 3. Delete Record using Prepared Statement
$delStmt = $conn->prepare("DELETE FROM events WHERE id = ?");
if (!$delStmt) {
    set_flash('error', 'Database Error: Failed to prepare event deletion statement: ' . $conn->error);
    header('Location: event_management.php');
    exit;
}

$delStmt->bind_param('i', $id);

if ($delStmt->execute()) {
    $delStmt->close();

    // 4. Safely clean up associated poster if not referenced elsewhere
    if (!empty($posterPath)) {
        safelyRemovePoster($conn, $posterPath, $id);
    }

    // Log administrative audit event
    logAuditEvent($conn, (int)$_SESSION['user_id'], 'EVENT_DELETED', "Event #{$id} '{$title}' was deleted.");

    // Success flash message matching Figure 5 format
    set_flash('success', "Event #{$id} `{$title}' was successfully deleted from records.");
} else {
    $dbErr = $delStmt->error;
    $delStmt->close();
    set_flash('error', 'Database Error: Failed to delete event record: ' . $dbErr);
}

header('Location: event_management.php');
exit;
