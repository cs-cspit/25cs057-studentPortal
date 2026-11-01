-- =============================================================================
-- STUDENTHUB PRACTICAL 14: DATABASE MIGRATION SCRIPT
-- Course: ITUE203 - Web Development Frameworks (WDF)
-- Institution: CSPIT - CSE, Charotar University of Science and Technology (CHARUSAT)
-- Student ID: 25CS057 | Academic Year: 2026-27 | Semester: 3rd
-- Topic: Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log
-- Database: studenthub_db
-- =============================================================================

USE `studenthub_db`;

-- -----------------------------------------------------------------------------
-- 1. Ensure audit_logs table exists and possesses standard schema
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(60) NOT NULL,
  `description` TEXT NOT NULL,
  `username` VARCHAR(50) NULL,
  `email` VARCHAR(100) NULL,
  `status` ENUM('SUCCESS', 'FAILED') NOT NULL DEFAULT 'SUCCESS',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `user_agent` VARCHAR(255) NULL,
  `details` TEXT NULL,
  `event_type` VARCHAR(60) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_user_id` (`user_id`),
  INDEX `idx_audit_status` (`status`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Safely add missing columns if audit_logs was created in an earlier practical
ALTER TABLE `audit_logs`
  ADD COLUMN IF NOT EXISTS `user_id` INT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `action`,
  ADD COLUMN IF NOT EXISTS `event_type` VARCHAR(60) NULL AFTER `details`;

-- Backfill description and details for consistency
UPDATE `audit_logs` SET `description` = `details` WHERE `description` IS NULL AND `details` IS NOT NULL;
UPDATE `audit_logs` SET `details` = `description` WHERE `details` IS NULL AND `description` IS NOT NULL;
UPDATE `audit_logs` SET `event_type` = `action` WHERE `event_type` IS NULL;

-- -----------------------------------------------------------------------------
-- 2. Extend users table for Intermediate Extension: Active/Inactive Status Toggle
-- -----------------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active' AFTER `role`;

-- -----------------------------------------------------------------------------
-- 3. Seed Realistic Audit Log History for Practical 14 Evaluation
-- -----------------------------------------------------------------------------
INSERT INTO `audit_logs` (`user_id`, `action`, `description`, `username`, `email`, `status`, `ip_address`, `user_agent`, `details`, `event_type`, `created_at`)
VALUES
  (20, 'LOGIN', 'Administrator Dr. Rajesh Shah signed in successfully.', 'admin_user', 'admin@studenthub.edu', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Administrator Dr. Rajesh Shah signed in successfully.', 'LOGIN', NOW() - INTERVAL 2 HOUR),
  (20, 'CREATE_EVENT', 'Created campus event #1 "HackNova 2026: National 24-Hour Codefest" with verified poster.', 'admin_user', 'admin@studenthub.edu', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Created campus event #1 "HackNova 2026: National 24-Hour Codefest" with verified poster.', 'CREATE_EVENT', NOW() - INTERVAL 1 HOUR - INTERVAL 45 MINUTE),
  (20, 'UPDATE_STUDENT', 'Updated student record #1 (24CS012 - Aarav Sharma) department credentials.', 'admin_user', 'admin@studenthub.edu', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Updated student record #1 (24CS012 - Aarav Sharma) department credentials.', 'UPDATE_STUDENT', NOW() - INTERVAL 1 HOUR - INTERVAL 15 MINUTE),
  (20, 'USER_STATUS_TOGGLE', 'Toggled status of user #2 (priya_patel) to Active.', 'admin_user', 'admin@studenthub.edu', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Toggled status of user #2 (priya_patel) to Active.', 'USER_STATUS_TOGGLE', NOW() - INTERVAL 45 MINUTE),
  (20, 'CREATE_STUDENT', 'Created new student record 25CS099 (Devam Parikh) in CSE Department.', 'admin_user', 'admin@studenthub.edu', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Created new student record 25CS099 (Devam Parikh) in CSE Department.', 'CREATE_STUDENT', NOW() - INTERVAL 20 MINUTE),
  (10, 'UNAUTHORIZED_ACCESS_ATTEMPT', 'User student_user attempted to access admin_dashboard.php without ADMIN privilege.', 'student_user', 'student@studenthub.edu', 'FAILED', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'User student_user attempted to access admin_dashboard.php without ADMIN privilege.', 'UNAUTHORIZED_ACCESS_ATTEMPT', NOW() - INTERVAL 10 MINUTE);

-- -----------------------------------------------------------------------------
-- 4. Seed Multi-Month Registration Data for Advanced Extension: Monthly Analytics
-- -----------------------------------------------------------------------------
INSERT INTO `registrations` (`student_id`, `event_id`, `registration_date`, `status`, `notes`)
VALUES
  (7, 1, '2025-11-15 10:00:00', 'Confirmed', 'November Early Bird Registrant'),
  (8, 2, '2025-11-20 14:30:00', 'Confirmed', 'November AI Workshop Attendee'),
  (9, 3, '2025-12-05 11:15:00', 'Confirmed', 'December Cloud Summit Delegate'),
  (10, 4, '2025-12-18 16:45:00', 'Confirmed', 'December Spandan Volunteer'),
  (11, 2, '2026-01-10 09:20:00', 'Confirmed', 'January Deep Learning Batch'),
  (12, 3, '2026-01-22 15:10:00', 'Confirmed', 'January DevOps Registration'),
  (13, 5, '2026-01-28 11:30:00', 'Confirmed', 'January CyberShield Participant'),
  (14, 1, '2026-03-02 10:15:00', 'Confirmed', 'March HackNova Team Beta'),
  (15, 4, '2026-03-05 14:00:00', 'Confirmed', 'March Spandan Stage Performance'),
  (16, 5, '2026-03-10 16:20:00', 'Confirmed', 'March Security Con Delegate')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `notes` = VALUES(`notes`);

