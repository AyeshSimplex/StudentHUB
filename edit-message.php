<?php
/**
 * Edit Message — StudentHub
 * Allows a user to edit their own contact message.
 * Protected page — requires authentication and message ownership.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];

// Ensure user_id column exists
ensureMessagesUserIdColumn($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: dashboard.php');
    exit();
}

$messageId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
$newSubject = trim($_POST['subject'] ?? '');
$newMessage = trim($_POST['message'] ?? '');

if ($messageId <= 0) {
    $_SESSION['error'] = 'Invalid message ID.';
    header('Location: dashboard.php');
    exit();
}

// Validate input
if (strlen($newSubject) < 3) {
    $_SESSION['error'] = 'Subject must be at least 3 characters.';
    header('Location: dashboard.php');
    exit();
}
if (strlen($newMessage) < 10) {
    $_SESSION['error'] = 'Message must be at least 10 characters.';
    header('Location: dashboard.php');
    exit();
}

// Verify message exists and check ownership
$stmt = $conn->prepare("SELECT id, user_id FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    $_SESSION['error'] = 'Message not found.';
    header('Location: dashboard.php');
    exit();
}

// Only the owner can edit their own message
if (!isset($message['user_id']) || $message['user_id'] != $userId) {
    $_SESSION['error'] = 'You can only edit your own messages.';
    header('Location: dashboard.php');
    exit();
}

// Update the message
$stmt = $conn->prepare("UPDATE messages SET subject = ?, message = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssii", $newSubject, $newMessage, $messageId, $userId);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    $_SESSION['success'] = 'Message updated successfully.';
} else {
    $_SESSION['error'] = 'Failed to update message. Please try again.';
}
$stmt->close();

header('Location: dashboard.php');
exit();
