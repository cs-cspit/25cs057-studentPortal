<?php
/**
 * StudentHub CSV Export Module
 * Practical 11: Student Record Management
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057 | Academic Year: 2026-27
 * 
 * Exports all student records (including Inactive records) into an RFC 4180
 * compliant CSV file formatted specifically for Microsoft Excel.
 */

require_once __DIR__ . '/db.php';

$conn = getDBConnection();

// Set HTTP Headers to trigger instant file download
$filename = 'students_export.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

$output = fopen('php://output', 'w');

// Prepend UTF-8 Byte Order Mark (BOM) for seamless Microsoft Excel rendering
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header Row (Exact Match to Syllabus & Reference Document)
$headers = [
    'ID',
    'Student ID',
    'Name',
    'Email',
    'Department',
    'Course',
    'Year',
    'Status',
    'Created At',
    'Updated At'
];
fputcsv($output, $headers);

// Query all student records ordered by primary key ID ascending
$sql = "SELECT id, student_id, name, email, department, course, year, status, created_at, updated_at 
        FROM students 
        ORDER BY id ASC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format timestamps to ISO 8601 format matching reference document
        $createdAtFormatted = date('Y-m-d\TH:i:s.000\Z', strtotime($row['created_at']));
        $updatedAtFormatted = date('Y-m-d\TH:i:s.000\Z', strtotime($row['updated_at']));

        $csvRow = [
            $row['id'],
            $row['student_id'],
            $row['name'],
            $row['email'],
            $row['department'],
            $row['course'],
            $row['year'],
            $row['status'],
            $createdAtFormatted,
            $updatedAtFormatted
        ];
        fputcsv($output, $csvRow);
    }
}

fclose($output);
exit;
