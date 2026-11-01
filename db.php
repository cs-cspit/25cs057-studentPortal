<?php
/**
 * StudentHub Centralized Database Connection & Helper Functions
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE)
 * Student ID: 25CS057
 */

// 1. Centralized Database Configuration for Local XAMPP
$dbConfig = [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME') ?: 'studenthub_db',
    'user'     => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '', // Default XAMPP blank password
    'charset'  => 'utf8mb4'
];

/**
 * Establishes and returns a shared MySQLi connection instance.
 *
 * @param array|null $customConfig Optional custom configuration override
 * @return mysqli Active MySQLi connection instance
 * @throws Exception If connection fails (safe message without technical leaks)
 */
function getDBConnection(?array $customConfig = null): mysqli {
    static $connInstance = null;

    if ($connInstance !== null && $connInstance instanceof mysqli && @$connInstance->ping()) {
        return $connInstance;
    }

    global $dbConfig;
    $config = $customConfig ?? $dbConfig;

    // Disable raw MySQLi exception output to prevent credential or internal path leakage
    mysqli_report(MYSQLI_REPORT_OFF);

    $mysqli = @new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['dbname'],
        $config['port']
    );

    if ($mysqli->connect_errno) {
        error_log('[StudentHub MySQLi Error] Failed to connect: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        throw new Exception('Database connection failed. Please ensure MySQL is running in XAMPP.');
    }

    if (!$mysqli->set_charset($config['charset'])) {
        error_log('[StudentHub MySQLi Error] Charset setting failed: ' . $mysqli->error);
    }

    $connInstance = $mysqli;
    return $connInstance;
}

/**
 * Logs a security audit event to the audit_logs table.
 *
 * @param mysqli|null $conn Database connection instance
 * @param int|null $userId User ID associated with event, or null if unauthenticated
 * @param string $eventType Type of security event (e.g., 'SUCCESSFUL_LOGIN', 'FAILED_LOGIN', 'LOGOUT', 'TIMEOUT')
 * @param string $description Detailed human-readable description
 * @return bool True if logged successfully, false otherwise
 */
function logAuditEvent(?mysqli $conn, ?int $userId, string $eventType, string $description): bool {
    require_once __DIR__ . '/includes/audit_logger.php';
    return recordAuditLog($conn, $userId, $eventType, $description, 'SUCCESS');
}
