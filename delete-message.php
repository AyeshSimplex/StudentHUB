<?php
/**
 * Delete Message — StudentHub
 * Deletes a contact form inquiry from the database.
 * Protected page — requires authentication and admin authorization (Ayesh Rathnayaka / demo).
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';

// Authorization check: Ayesh Rathnayaka / Demo Administrator account
$isAuthorized = ($userId == 1 || $username === 'ayesh' || in_array($email, ['demo@studenthub.lk', 'ent2023048@tec.rjt.ac.lk']));

if (!$isAuthorized) {
    $_SESSION['error'] = 'You are not authorized to delete contact messages.';
    header('Location: dashboard.php');
    exit();
}

$messageId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($messageId <= 0) {
    $_SESSION['error'] = 'Invalid message ID.';
    header('Location: dashboard.php');
    exit();
}

// Verify message exists
$stmt = $conn->prepare("SELECT id, name, subject FROM messages WHERE id = ?");
$stmt->bind_param("i", $messageId);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    $_SESSION['error'] = 'Message not found or already deleted.';
    header('Location: dashboard.php');
    exit();
}

// Delete message from database
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
