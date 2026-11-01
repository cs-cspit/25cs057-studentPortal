<?php
/**
 * StudentHub Centralized API Database Connection & JSON Response Helper
 * Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD Without Page Reload
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

// Disable raw PHP error/warning rendering to prevent HTML pollution of JSON output
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Send standard JSON Content-Type and CORS headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Register global exception and fatal shutdown handlers to ensure valid JSON responses
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success'   => false,
        'message'   => 'Internal server error occurred.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});

// Require the project's centralized database connection and session management
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../timeout.php';

// Verify session timeout for authenticated API interactions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkSessionTimeout();

/**
 * Sends a consistent, standardized JSON API response and terminates execution.
 *
 * @param bool $success Operation status
 * @param string $message Descriptive human-readable message
 * @param mixed $data Optional data payload
 * @param array|null $errors Optional validation errors dictionary
 * @param int $statusCode HTTP response status code (e.g. 200, 201, 400, 401, 403, 404, 422, 500)
 * @return void
 */
function sendJsonResponse(bool $success, string $message, $data = null, ?array $errors = null, int $statusCode = 200): void {
    http_response_code($statusCode);

    $response = [
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($errors !== null) {
        $response['errors'] = $errors;
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Extracts and decodes JSON request payload from php://input, falling back to $_POST.
 *
 * @return array Decoded associative array
 */
function getRequestPayload(): array {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST ?? [];
}

/**
 * Extracts CSRF token from headers (X-CSRF-Token) or request payload body.
 *
 * @param array $payload
 * @return string|null CSRF token string or null if not present
 */
function extractCsrfToken(array $payload = []): ?string {
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return trim((string)$_SERVER['HTTP_X_CSRF_TOKEN']);
    }
    if (!empty($payload['csrf_token'])) {
        return trim((string)$payload['csrf_token']);
    }
    if (!empty($_POST['csrf_token'])) {
        return trim((string)$_POST['csrf_token']);
    }
    return null;
}

/**
 * Enforces authenticated user session for API endpoints.
 * Emits HTTP 401 Unauthorized if unauthenticated.
 */
function apiRequireAuth(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        sendJsonResponse(false, 'Unauthorized: Active user session required. Please sign in.', null, null, 401);
    }
}

/**
 * Enforces Administrator role for state-changing CRUD operations.
 * Emits HTTP 403 Forbidden if user is not an Admin.
 */
function apiRequireAdmin(): void {
    apiRequireAuth();
    if ($_SESSION['role'] !== 'Admin') {
        sendJsonResponse(
            false,
            'Forbidden: Administrator privileges are required to perform this action.',
            null,
            ['authorization' => 'Current role is Read-Only (' . htmlspecialchars($_SESSION['role']) . ')'],
            403
        );
    }
}

/**
 * Validates CSRF token for state-changing HTTP methods (POST, PUT, DELETE, PATCH).
 *
 * @param array $payload
 */
function apiValidateCsrf(array $payload = []): void {
    $token = extractCsrfToken($payload);
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
        sendJsonResponse(
            false,
            'Forbidden: CSRF validation failed or token missing.',
            null,
            ['csrf_token' => 'Valid CSRF token must be provided via X-CSRF-Token header or payload body.'],
            403
        );
    }
}
