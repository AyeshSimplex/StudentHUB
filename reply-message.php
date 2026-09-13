<?php
/**
 * Reply to Message — StudentHub
 * Allows any logged-in user to reply to a public message.
 * Admin replies are displayed as "StudentHub Team".
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];

// Ensure tables exist
ensureMessagesUserIdColumn($conn);
ensureMessageRepliesTable($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: dashboard.php');
    exit();
}

$messageId = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
$replyText = trim($_POST['reply_text'] ?? '');

if ($messageId <= 0) {
    $_SESSION['error'] = 'Invalid message ID.';
    header('Location: dashboard.php');
    exit();
}

// Validate reply text
if (strlen($replyText) < 2) {
    $_SESSION['error'] = 'Reply must be at least 2 characters.';
    header('Location: dashboard.php');
    exit();
}

// Verify the parent message exists
$stmt = $conn->prepare("SELECT id FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    $_SESSION['error'] = 'Message not found.';
    header('Location: dashboard.php');
    exit();
}

// Insert reply
$stmt = $conn->prepare("INSERT INTO message_replies (message_id, user_id, reply_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $messageId, $userId, $replyText);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Reply posted successfully.';
} else {
    $_SESSION['error'] = 'Failed to post reply. Please try again.';
}
$stmt->close();

header('Location: dashboard.php');
exit();
