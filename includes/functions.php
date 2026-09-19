<?php
/**
 * Common Functions — StudentHub
 *
 * This file contains reusable helper functions used
 * across the application.
 */

// Start session with hardened security settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}

/**
 * Generate or retrieve a CSRF token for the current session
 * @return string The CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token
 * @param string $token The token submitted with the form
 * @return bool True if valid
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF input field for use in forms
 * @return string HTML hidden input element
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * Validate CSRF token from POST request; redirect with error on failure
 * @param string $redirectUrl URL to redirect to on failure
 */
function requireCsrfToken($redirectUrl = 'index.php') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        $_SESSION['error'] = 'Invalid or expired security token. Please try again.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Check if a user is currently logged in
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please log in to access that page.';
        header('Location: login.php');
        exit();
    }
}

/**
 * Sanitize output to prevent XSS attacks
 * @param string $data The string to sanitize
 * @return string Sanitized string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Handle image upload with validation
 *
 * @param array $file The $_FILES array element
 * @param string $uploadDir The target upload directory
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadImage($file, $uploadDir = 'uploads/projects/') {
    $result = ['success' => false, 'filename' => '', 'error' => ''];

    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload failed. Please try again.';
        return $result;
    }

    // Allowed image types
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    // Check file type
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Only JPG, JPEG, PNG, and WEBP images are allowed.';
        return $result;
    }

    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $result['error'] = 'Invalid file extension. Only jpg, jpeg, png, and webp are allowed.';
        return $result;
    }

    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File is too large. Maximum size is 5MB.';
        return $result;
    }

    // Generate safe unique filename
    $newFilename = uniqid('project_', true) . '.' . $extension;
    $targetPath = $uploadDir . $newFilename;

    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['filename'] = $newFilename;
    } else {
        $result['error'] = 'Failed to save the uploaded file.';
    }

    return $result;
}

/**
 * Get time ago string from a date
 * @param string $datetime The datetime string
 * @return string Human-readable time ago
 */
function getTimeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // If the date is in the future (invert is 0), just say "Just now" to handle clock skews
    if ($diff->invert === 0) {
        return 'Just now';
    }

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Truncate text to a given length
 * @param string $text The text to truncate
 * @param int $length Maximum length
 * @return string Truncated text with ellipsis
 */
function truncateText($text, $length = 120) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Get the current page name for active nav highlighting
 * @return string Current page filename
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Display a flash message and clear it from session
 * @param string $type The message type (success, error, warning, info)
 * @return string|null The HTML for the alert, or null
 */
function flashMessage($type = 'success') {
    if (isset($_SESSION[$type])) {
        $alertClass = $type === 'error' ? 'danger' : $type;
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">'
             . sanitize($message)
             . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
             . '</div>';
    }
    return null;
}

/**
 * Ensure is_admin column exists in users table
 * @param mysqli $conn Database connection
 */
function ensureUserAdminColumn($conn) {
    static $adminColumnChecked = false;
    if ($adminColumnChecked) return;
    $check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'is_admin'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 AFTER `profile_image`");
        // Upgrade existing admins during migration
        $conn->query("UPDATE `users` SET `is_admin` = 1 WHERE `id` = 1 OR `email` = 'demo@studenthub.lk' OR `username` = 'ayesh'");
    }
    $adminColumnChecked = true;
}

/**
 * Ensure profile_image column exists in users table
 * @param mysqli $conn Database connection
 */
function ensureUserAvatarColumn($conn) {
    static $avatarColumnChecked = false;
    if ($avatarColumnChecked) return;
    $check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'profile_image'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `users` ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL AFTER `skills`");
    }
    $avatarColumnChecked = true;
}

/**
 * Handle user avatar upload with validation
 * @param array $file The $_FILES array element
 * @param string $uploadDir The target upload directory
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadAvatar($file, $uploadDir = 'uploads/avatars/') {
    $result = ['success' => false, 'filename' => '', 'error' => ''];

    // Ensure target directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $result['error'] = 'No file was uploaded.';
        return $result;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Avatar upload failed. Please try again.';
        return $result;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    $fileType = @mime_content_type($file['tmp_name']);
    if (!$fileType || !in_array($fileType, $allowedTypes)) {
        $result['error'] = 'Invalid image type. Only JPG, PNG, and WEBP profile photos are allowed.';
        return $result;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $result['error'] = 'Invalid file extension. Please select a .jpg, .png, or .webp image.';
        return $result;
    }

    // Max 5MB
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $result['error'] = 'Profile photo exceeds 5MB limit. Please choose a smaller image.';
        return $result;
    }

    $newFilename = uniqid('avatar_', true) . '.' . $extension;
    $targetPath = rtrim($uploadDir, '/\\') . '/' . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['filename'] = $newFilename;
    } else {
        $result['error'] = 'Could not save the profile photo to server.';
    }

    return $result;
}

/**
 * Safely delete an avatar file from server
 * @param string $filename File name in uploads/avatars/
 * @param string $uploadDir Upload directory
 */
function deleteAvatarFile($filename, $uploadDir = 'uploads/avatars/') {
    if (empty($filename)) return;
    $filePath = rtrim($uploadDir, '/\\') . '/' . basename($filename);
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }
}

/**
 * Get user data by ID
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return array|null User data or null
 */
function getUserById($conn, $userId) {
    ensureUserAvatarColumn($conn);
    ensureUserAdminColumn($conn);
    $stmt = $conn->prepare("SELECT id, username, full_name, email, faculty, skills, profile_image, is_admin, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Count projects for a user
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return int Number of projects
 */
function countUserProjects($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

/**
 * Ensure project_images table exists
 * @param mysqli $conn Database connection
 */
function initProjectImagesTable($conn) {
    static $initialized = false;
    if ($initialized) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `project_images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `caption` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $initialized = true;
}

/**
 * Fetch all gallery images for a project
 * @param mysqli $conn Database connection
 * @param int $projectId Project ID
 * @return array List of image associative arrays
 */
function getProjectImages($conn, $projectId) {
    initProjectImagesTable($conn);
    $images = [];
    $stmt = $conn->prepare("SELECT id, project_id, image, caption, created_at FROM project_images WHERE project_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
    }
    return $images;
}

/**
 * Handle multiple image uploads for a project
 * @param array $fileInput Normalized array from $_FILES['key']
 * @param string $uploadDir Upload directory path
 * @return array Array containing 'uploaded' (array of filenames) and 'errors' (array of strings)
 */
function uploadMultipleImages($fileInput, $uploadDir = 'uploads/projects/') {
    $uploaded = [];
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!isset($fileInput['name'])) {
        return ['uploaded' => [], 'errors' => ['No files provided.']];
    }

    // Check if single or multiple files
    $names = is_array($fileInput['name']) ? $fileInput['name'] : [$fileInput['name']];
    $tmpNames = is_array($fileInput['tmp_name']) ? $fileInput['tmp_name'] : [$fileInput['tmp_name']];
    $errorsList = is_array($fileInput['error']) ? $fileInput['error'] : [$fileInput['error']];
    $sizes = is_array($fileInput['size']) ? $fileInput['size'] : [$fileInput['size']];

    for ($i = 0; $i < count($names); $i++) {
        if ($errorsList[$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($errorsList[$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'Failed to upload ' . sanitize($names[$i]) . ' (Error code: ' . $errorsList[$i] . ')';
            continue;
        }

        $fileType = @mime_content_type($tmpNames[$i]);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = sanitize($names[$i]) . ': Invalid file type. Only JPG, PNG, and WEBP are allowed.';
            continue;
        }

        $extension = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = sanitize($names[$i]) . ': Invalid file extension.';
            continue;
        }

        if ($sizes[$i] > $maxSize) {
            $errors[] = sanitize($names[$i]) . ': File exceeds 5MB limit.';
            continue;
        }

        $newFilename = uniqid('gallery_', true) . '.' . $extension;
        $targetPath = $uploadDir . $newFilename;

        if (move_uploaded_file($tmpNames[$i], $targetPath)) {
            $uploaded[] = $newFilename;
        } else {
            $errors[] = 'Failed to save ' . sanitize($names[$i]);
        }
    }

    return ['uploaded' => $uploaded, 'errors' => $errors];
}

/**
 * Delete an image belonging to a project owned by user
 * @param mysqli $conn Database connection
 * @param int $imageId Project image ID
 * @param int $userId User ID for ownership check
 * @return bool True on success, false on failure
 */
function deleteProjectGalleryImage($conn, $imageId, $userId) {
    initProjectImagesTable($conn);
    // Fetch image info and verify ownership via projects table
    $stmt = $conn->prepare("SELECT pi.id, pi.project_id, pi.image, p.user_id, p.image as cover_image
                            FROM project_images pi
                            JOIN projects p ON pi.project_id = p.id
                            WHERE pi.id = ?");
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$img || $img['user_id'] != $userId) {
        return false;
    }

    // Remove file if exists
    $filePath = 'uploads/projects/' . $img['image'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    // Delete record
    $delStmt = $conn->prepare("DELETE FROM project_images WHERE id = ?");
    $delStmt->bind_param("i", $imageId);
    $success = $delStmt->execute();
    $delStmt->close();

    // If this image was also the cover image in projects table, clear or update it
    if ($img['image'] === $img['cover_image']) {
        // Fallback to another image or null
        $nextStmt = $conn->prepare("SELECT image FROM project_images WHERE project_id = ? LIMIT 1");
        $nextStmt->bind_param("i", $img['project_id']);
        $nextStmt->execute();
        $nextImg = $nextStmt->get_result()->fetch_assoc();
        $nextStmt->close();

        $newCover = $nextImg ? $nextImg['image'] : null;
        $updCover = $conn->prepare("UPDATE projects SET image = ? WHERE id = ?");
        $updCover->bind_param("si", $newCover, $img['project_id']);
        $updCover->execute();
        $updCover->close();
    }

    return $success;
}

/**
 * Set an image as the project cover
 * @param mysqli $conn Database connection
 * @param int $projectId Project ID
 * @param string $imageName Filename
 * @param int $userId User ID for ownership check
 * @return bool True on success
 */
function setProjectCoverImage($conn, $projectId, $imageName, $userId) {
    $stmt = $conn->prepare("UPDATE projects SET image = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $imageName, $projectId, $userId);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}

/**
 * Check if the current user is an admin
 * @return bool True if the current session user is the admin
 */
function isAdmin() {
    return (isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1);
}

/**
 * Ensure user_id column exists in messages table
 * @param mysqli $conn Database connection
 */
function ensureMessagesUserIdColumn($conn) {
    static $checked = false;
    if ($checked) return;
    $check = $conn->query("SHOW COLUMNS FROM `messages` LIKE 'user_id'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `messages` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `message`");
        // Add foreign key only if it doesn't exist
        $conn->query("ALTER TABLE `messages` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL");
    }
    $checked = true;
}

/**
 * Ensure message_replies table exists
 * @param mysqli $conn Database connection
 */
function ensureMessageRepliesTable($conn) {
    static $initialized = false;
    if ($initialized) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `message_replies` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `message_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `reply_text` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $initialized = true;
}

/**
 * Ensure reviews table exists
 * @param mysqli $conn Database connection
 */
function ensureReviewsTable($conn) {
    static $initialized = false;
    if ($initialized) return;
    $conn->query("CREATE TABLE IF NOT EXISTS `reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `rating` TINYINT NOT NULL,
        `review_text` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $initialized = true;
}
?>
