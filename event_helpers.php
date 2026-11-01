<?php
/**
 * StudentHub Campus Event Management Helper Module
 * Practical 12: Campus Event Management with Validated Poster Upload and Role-Based Access
 * Course: ITUE203 - Web Development Frameworks (CSPIT - CSE, CHARUSAT)
 * Student ID: 25CS057
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

const EVENT_UPLOAD_DIR = __DIR__ . '/uploads/event_posters';
const MAX_POSTER_SIZE_BYTES = 2097152; // 2 MB (2 * 1024 * 1024)
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Validates and securely saves an uploaded event poster file.
 *
 * @param array $file The $_FILES['poster'] array entry
 * @param string|null &$errorMessage Output variable for user-facing error message
 * @return string|null Relative stored file path (e.g. 'uploads/event_posters/poster_xyz.png') or null on error
 */
function handlePosterUpload(array $file, ?string &$errorMessage = null): ?string {
    // 1. Check if file is provided and no system upload errors
    if (!isset($file['error']) || is_array($file['error'])) {
        $errorMessage = "Invalid upload parameters.";
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        // No file provided
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errorMessage = "Validation Error: File size limit exceeded! Maximum allowed size is 2 MB.";
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Upload error encountered (Code: " . (int)$file['error'] . ").";
        return null;
    }

    $tmpPath = $file['tmp_name'];
    $origName = $file['name'] ?? 'poster';
    $fileSize = (int)$file['size'];

    // 2. Strict file size enforcement (2 MB)
    if ($fileSize > MAX_POSTER_SIZE_BYTES || filesize($tmpPath) > MAX_POSTER_SIZE_BYTES) {
        $sizeMB = number_format($fileSize / (1024 * 1024), 2);
        $errorMessage = "Validation Error: File size limit exceeded! File size is {$sizeMB} MB, exceeding the configured 2MB upload limit.";
        return null;
    }

    // 3. Extension check
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        $cleanExt = $ext ? '.' . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') : 'unknown';
        $errorMessage = "Validation Error: Unsupported file format ({$cleanExt})! Only JPG, JPEG, PNG, and WEBP image files are allowed.";
        return null;
    }

    // 4. Authoritative MIME type verification using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    if (!in_array($detectedMime, ALLOWED_MIME_TYPES, true)) {
        $safeOrig = htmlspecialchars($origName, ENT_QUOTES, 'UTF-8');
        $errorMessage = "File upload rejected: Selected file '{$safeOrig}' is an unsupported type. Only JPG, PNG, WEBP are allowed.";
        return null;
    }

    // 5. Image integrity verification using getimagesize()
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false || $imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
        $errorMessage = "Validation Error: File rejected! The uploaded file is corrupted or not a genuine image.";
        return null;
    }

    // Double check image type constant
    $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!in_array($imageInfo[2], $validTypes, true)) {
        $errorMessage = "Validation Error: Image type mismatch. Only authentic JPEG, PNG, and WEBP images are supported.";
        return null;
    }

    // 6. Generate cryptographically random filename
    $extNormalized = ($ext === 'jpeg') ? 'jpg' : $ext;
    $randomHex = bin2hex(random_bytes(10));
    $newFilename = 'poster_' . $randomHex . '.' . $extNormalized;

    // 7. Ensure target directory exists
    if (!is_dir(EVENT_UPLOAD_DIR)) {
        mkdir(EVENT_UPLOAD_DIR, 0755, true);
    }

    $destPath = EVENT_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newFilename;

    // 8. Move uploaded file to secure storage
    if (!move_uploaded_file($tmpPath, $destPath)) {
        $errorMessage = "Server Error: Failed to safely store the uploaded poster image.";
        return null;
    }

    // Set secure read-only permissions on saved file
    @chmod($destPath, 0644);

    // Return relative path for database storage and client rendering
    return 'uploads/event_posters/' . $newFilename;
}

/**
 * Safely removes a poster image file from storage if it exists, is inside the upload directory,
 * and is not referenced by any other event record.
 *
 * @param mysqli $conn Active database connection
 * @param string|null $posterPath Relative path from database
 * @param int|null $excludeEventId Optional event ID currently being edited/deleted
 * @return bool True if deleted or non-existent, false on error
 */
function safelyRemovePoster(mysqli $conn, ?string $posterPath, ?int $excludeEventId = null): bool {
    if (empty($posterPath)) {
        return true;
    }

    // Sanitize path to prevent directory traversal
    $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $posterPath);
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $normalized;

    // Check that target file is actually inside the uploads/event_posters directory
    $realUploadDir = realpath(EVENT_UPLOAD_DIR);
    $realFilePath = realpath($fullPath);

    if ($realFilePath === false || strpos($realFilePath, $realUploadDir) !== 0) {
        // File does not exist or is outside the upload directory (e.g., attempt to delete unrelated system file)
        return false;
    }

    // Check if any OTHER event is still referencing this poster
    $query = "SELECT COUNT(*) as cnt FROM events WHERE poster_path = ?";
    if ($excludeEventId !== null) {
        $query .= " AND id != " . (int)$excludeEventId;
    }

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('s', $posterPath);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row && (int)$row['cnt'] > 0) {
            // Shared poster: do not delete the physical file
            return true;
        }
    }

    // Delete the file
    if (file_exists($realFilePath)) {
        return @unlink($realFilePath);
    }

    return true;
}
