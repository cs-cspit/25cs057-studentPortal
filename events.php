<?php
/**
 * StudentHub — Campus Event Management Module Router
 * Practical 12 & Practical 15: Unified Campus Events
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/timeout.php';
require_once __DIR__ . '/auth.php';

requireAuth();

if (isAdmin()) {
    header('Location: event_management.php');
} else {
    header('Location: student_dashboard.php#registrationsSection');
}
exit;
