-- =============================================================================
-- STUDENTHUB PRACTICAL 13: DATABASE SCHEMA & SEED DATA
-- Course: ITUE203 - Web Development Frameworks (WDF)
-- Institution: CSPIT - CSE, Charotar University of Science and Technology (CHARUSAT)
-- Student ID: 25CS057 | Academic Year: 2026-27 | Semester: 3rd
-- Topic: PHP JSON API and AJAX/Fetch-Based CRUD Without Page Reload
-- Database: studenthub_db | Character Set: utf8mb4 | Engine: InnoDB
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `studenthub_db`;

-- -----------------------------------------------------------------------------
-- 1. Users Table (Core RBAC & Authentication Store)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Admin', 'Student') NOT NULL DEFAULT 'Student',
  `full_name` VARCHAR(100) NOT NULL DEFAULT 'Student User',
  `department` VARCHAR(100) NOT NULL DEFAULT 'Computer Science & Engineering',
  `student_id_code` VARCHAR(20) NULL,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Remember-Me Tokens Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `selector` VARCHAR(32) NOT NULL UNIQUE,
  `token_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_selector` (`selector`),
  INDEX `idx_user_tokens` (`user_id`),
  CONSTRAINT `fk_remember_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Security Audit Logging Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_event` (`event_type`),
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Events Table (Campus Events with Poster Support)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(60) NOT NULL,
  `status` ENUM('Upcoming', 'Active', 'Completed') NOT NULL DEFAULT 'Upcoming',
  `description` TEXT NOT NULL,
  `poster_path` VARCHAR(255) NULL,
  `event_date` DATE NOT NULL,
  `venue` VARCHAR(150) NOT NULL,
  `capacity` INT NOT NULL DEFAULT 100,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `event_id` INT AS (`id`) VIRTUAL,
  `event_name` VARCHAR(150) AS (`title`) VIRTUAL,
  `max_capacity` INT AS (`capacity`) VIRTUAL,
  INDEX `idx_events_status` (`status`),
  INDEX `idx_events_date` (`event_date`),
  INDEX `idx_events_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Registrations Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrations` (
  `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `registration_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Confirmed', 'Pending', 'Waitlisted', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
  `notes` VARCHAR(255) NULL,
  UNIQUE KEY `uq_student_event_registration` (`student_id`, `event_id`),
  INDEX `idx_reg_student` (`student_id`),
  INDEX `idx_reg_event` (`event_id`),
  CONSTRAINT `fk_registrations_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Students Table (Target Entity for Practical 13 Fetch API CRUD)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `department` VARCHAR(100) NOT NULL,
  `course` VARCHAR(100) NOT NULL,
  `year` INT NOT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_student_email` (`email`),
  INDEX `idx_course` (`course`),
  INDEX `idx_status` (`status`),
  INDEX `idx_department` (`department`),
  INDEX `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Seed Baseline User Accounts
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `full_name`, `department`, `student_id_code`)
VALUES 
  (10, 'student_user', 'student@studenthub.edu', '$2y$10$pwPAyHCDLTc109ZX4V3YgOTxTw0oUSueqp5ZKzoI3Iv/MHzY6I/QO', 'Student', 'Aarav Patel', 'Computer Science & Engineering', '25CS057'),
  (20, 'admin_user', 'admin@studenthub.edu', '$2y$10$T8cOU3Zmwg27YkMQHxvAY.aZcR6Yp3cPnxkcWTa3/5yv0yVuePELC', 'Admin', 'Dr. Rajesh Shah (System Admin)', 'Information Technology & Systems', 'EMP-ADMIN-01')
ON DUPLICATE KEY UPDATE 
  `password_hash` = VALUES(`password_hash`),
  `role` = VALUES(`role`),
  `full_name` = VALUES(`full_name`),
  `student_id_code` = VALUES(`student_id_code`);

-- -----------------------------------------------------------------------------
-- 8. Seed Baseline Student Records (18 Authentic Academic Records)
-- -----------------------------------------------------------------------------
INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `department`, `course`, `year`, `status`, `created_at`, `updated_at`) VALUES
(1, '24CS012', 'Aarav Sharma', 'aarav.sharma24@charusat.edu.in', 'Computer Science & Engineering', 'B.Tech Computer Science & Engineering', 2, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(2, '23IT045', 'Priya Patel', 'priya.patel23@charusat.edu.in', 'Information Technology', 'B.Tech Information Technology', 3, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(3, '25CS088', 'Rohan Mehta', 'rohan.mehta25@charusat.edu.in', 'Computer Science & Engineering', 'B.Tech Computer Science & Engineering', 1, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(4, '22EC019', 'Ananya Iyer', 'ananya.iyer22@charusat.edu.in', 'Electronics & Communication', 'B.Tech Electronics & Communication', 4, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(5, '24IT033', 'Kavya Desai', 'kavya.desai24@charusat.edu.in', 'Information Technology', 'B.Tech Information Technology', 2, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(6, '23CE014', 'Devansh Trivedi', 'devansh.trivedi23@charusat.edu.in', 'Civil Engineering', 'B.Tech Civil Engineering', 3, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(7, '22ME007', 'Siddharth Joshi', 'siddharth.joshi22@charusat.edu.in', 'Mechanical Engineering', 'B.Tech Mechanical Engineering', 4, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(8, '25IT062', 'Ishita Verma', 'ishita.verma25@charusat.edu.in', 'Information Technology', 'B.Tech Information Technology', 1, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(9, '24EC041', 'Varun Nambiar', 'varun.nambiar24@charusat.edu.in', 'Electronics & Communication', 'B.Tech Electronics & Communication', 2, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(10, '23CS105', 'Tanvi Kulkarni', 'tanvi.kulkarni23@charusat.edu.in', 'Computer Science & Engineering', 'B.Tech Computer Science & Engineering', 3, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(11, '22CE048', 'Aditya Sengupta', 'aditya.sengupta22@charusat.edu.in', 'Civil Engineering', 'B.Tech Civil Engineering', 4, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(12, '25ME029', 'Meera Pillai', 'meera.pillai25@charusat.edu.in', 'Mechanical Engineering', 'B.Tech Mechanical Engineering', 1, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(13, '24CS077', 'Nikhil Agarwal', 'nikhil.agarwal24@charusat.edu.in', 'Computer Science & Engineering', 'B.Tech Computer Science & Engineering', 2, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(14, '23EC052', 'Diya Bhattacharya', 'diya.bhatt23@charusat.edu.in', 'Electronics & Communication', 'B.Tech Electronics & Communication', 3, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(15, '25CS119', 'Kabir Mathur', 'kabir.mathur25@charusat.edu.in', 'Computer Science & Engineering', 'B.Tech Computer Science & Engineering', 1, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(16, '24ME035', 'Rhea Kapoor', 'rhea.kapoor24@charusat.edu.in', 'Mechanical Engineering', 'B.Tech Mechanical Engineering', 2, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(17, '22IT018', 'Aryan Dave', 'aryan.dave22@charusat.edu.in', 'Information Technology', 'B.Tech Information Technology', 4, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16'),
(18, '23CE026', 'Sneha Nair', 'sneha.nair23@charusat.edu.in', 'Civil Engineering', 'B.Tech Civil Engineering', 3, 'Active', '2026-10-15 19:35:16', '2026-10-15 19:35:16')
ON DUPLICATE KEY UPDATE
  `student_id` = VALUES(`student_id`),
  `name` = VALUES(`name`),
  `email` = VALUES(`email`),
  `department` = VALUES(`department`),
  `course` = VALUES(`course`),
  `year` = VALUES(`year`),
  `status` = VALUES(`status`);

-- -----------------------------------------------------------------------------
-- 9. Seed Baseline Events
-- -----------------------------------------------------------------------------
INSERT INTO `events` (`id`, `title`, `category`, `status`, `description`, `poster_path`, `event_date`, `venue`, `capacity`)
VALUES
  (1, 'HackNova 2026: National 24-Hour Codefest', 'Coding Competition', 'Upcoming', 'An intensive 24-hour hackathon where student teams develop cutting-edge solutions for real-world problems.', 'uploads/event_posters/poster_hacknova.png', '2026-03-28', 'Dr. APJ Abdul Kalam Auditorium & CS Labs', 155),
  (2, 'Hands-on Deep Learning with PyTorch', 'Technical Workshop', 'Upcoming', 'A comprehensive practical workshop covering deep learning fundamentals and neural network architectures.', 'uploads/event_posters/poster_deeplearning.png', '2026-03-15', 'High Performance Computing Lab, Room 302', 60),
  (3, 'Cloud Native DevOps & Kubernetes Summit', 'Industry Seminar', 'Upcoming', 'Interactive session on container orchestration, CI/CD pipelines, and cloud deployment best practices.', 'uploads/event_posters/poster_cloudnative.png', '2026-04-05', 'Seminar Hall A, Technology Building', 120),
  (4, 'Spandan 2026: Annual Campus Cultural Fiesta', 'Cultural Festival', 'Upcoming', 'Charusat premier annual cultural extravaganza featuring music, drama, fine arts, and literary competitions.', 'uploads/event_posters/poster_spandan.png', '2026-04-18', 'Central University Grounds & Open Air Theatre', 500),
  (5, 'CyberShield: Ethical Hacking & Security Con', 'Security Workshop', 'Upcoming', 'Hands-on penetration testing, threat modeling, and network defense strategies led by cybersecurity experts.', 'uploads/event_posters/poster_cybershield.png', '2026-05-02', 'Cyber Security Research Center', 80)
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `category` = VALUES(`category`),
  `status` = VALUES(`status`),
  `description` = VALUES(`description`),
  `poster_path` = VALUES(`poster_path`),
  `event_date` = VALUES(`event_date`),
  `venue` = VALUES(`venue`),
  `capacity` = VALUES(`capacity`);
