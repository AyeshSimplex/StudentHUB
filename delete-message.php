<?php
/**
 * Delete Message — StudentHub
 * Deletes a contact form message from the database.
 * Protected page — requires authentication.
 * Allows: message owner (user_id match) or admin.
 * Requires POST method with CSRF token.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

// Only allow POST requests for destructive actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: dashboard.php');
    exit();
}

// Validate CSRF token
requireCsrfToken('dashboard.php');

$userId = $_SESSION['user_id'];

// Ensure user_id column exists
ensureMessagesUserIdColumn($conn);

$messageId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($messageId <= 0) {
    $_SESSION['error'] = 'Invalid message ID.';
    header('Location: dashboard.php');
    exit();
}

// Verify message exists and check ownership
$stmt = $conn->prepare("SELECT id, name, subject, user_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    $_SESSION['error'] = 'Message not found or already deleted.';
    header('Location: dashboard.php');
    exit();
}

// Authorization: message owner OR admin
$isMessageOwner = (isset($message['user_id']) && (int)$message['user_id'] === (int)$userId);
$isAuthorized = $isMessageOwner || isAdmin();

if (!$isAuthorized) {
    $_SESSION['error'] = 'You are not authorized to delete this message.';
    header('Location: dashboard.php');
    exit();
}

// Delete message from database (replies cascade automatically)
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Message from "' . htmlspecialchars($message['name']) . '" deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete message. Please try again.';
}
$stmt->close();

header('Location: dashboard.php');
exit();
