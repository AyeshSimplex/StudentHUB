<?php
/**
 * Delete Reply — StudentHub
 * Allows a user to delete their own reply, or admin to delete any reply.
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

// Ensure replies table exists
ensureMessageRepliesTable($conn);

$replyId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($replyId <= 0) {
    $_SESSION['error'] = 'Invalid reply ID.';
    header('Location: dashboard.php');
    exit();
}

// Verify reply exists
$stmt = $conn->prepare("SELECT id, user_id FROM message_replies WHERE id = ?");
$stmt->bind_param("i", $replyId);
$stmt->execute();
$reply = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reply) {
    $_SESSION['error'] = 'Reply not found.';
    header('Location: dashboard.php');
    exit();
}

// Authorization: reply owner OR admin
$isReplyOwner = ((int)$reply['user_id'] === (int)$userId);
$isAuthorized = $isReplyOwner || isAdmin();

if (!$isAuthorized) {
    $_SESSION['error'] = 'You can only delete your own replies.';
    header("Location: dashboard.php");
    exit();
}

// Delete reply
$stmt = $conn->prepare("DELETE FROM message_replies WHERE id = ?");
$stmt->bind_param("i", $replyId);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Reply deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete reply. Please try again.';
}
$stmt->close();

header("Location: dashboard.php");
exit();
