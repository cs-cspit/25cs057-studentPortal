-- =============================================================================
-- STUDENTHUB PRACTICAL 14: DYNAMIC DASHBOARD SQL AGGREGATION QUERIES
-- Course: ITUE203 - Web Development Frameworks (WDF)
-- Student ID: 25CS057
-- =============================================================================

-- Query 1: Total Registered Users & Active Account Breakdown
SELECT 
    COUNT(*) AS total_users, 
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_users 
FROM users;

-- Query 2: Total Student Records & Active Academic Status
SELECT 
    COUNT(*) AS total_students, 
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_students 
FROM students;

-- Query 3: Total Campus Events Count
SELECT COUNT(*) AS total_events FROM events;

-- Query 4: Total Event Registrations Count
SELECT COUNT(*) AS total_registrations FROM registrations;

-- Query 5: Relational INNER JOIN Query for Latest Registrations Feed (Section 4.D)
SELECT 
    r.registration_id, 
    s.name AS student_name, 
    s.student_id AS enrollment_no, 
    e.title AS event_title, 
    e.category AS event_category,
    r.registration_date, 
    r.status, 
    r.notes 
FROM registrations r 
JOIN students s ON r.student_id = s.id 
JOIN events e ON r.event_id = e.id 
ORDER BY r.registration_date DESC, r.registration_id DESC 
LIMIT 6;

-- Query 6: Monthly Registration Grouping Query for Analytics Chart (Section 8)
SELECT 
    DATE_FORMAT(registration_date, '%b %Y') AS month_label,
    DATE_FORMAT(registration_date, '%Y-%m') AS sort_month,
    COUNT(*) AS total_count
FROM registrations
GROUP BY sort_month, month_label
ORDER BY sort_month ASC
LIMIT 12;

