<?php
/**
 * Delete Review — StudentHub
 * Allows a user to delete their own review, or admin to delete any review.
 * Requires POST method with CSRF token.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

// Only allow POST requests for destructive actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: projects.php');
    exit();
}

// Validate CSRF token
requireCsrfToken('projects.php');

$userId = $_SESSION['user_id'];

// Ensure reviews table exists
ensureReviewsTable($conn);

$reviewId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

if ($reviewId <= 0) {
    $_SESSION['error'] = 'Invalid review ID.';
    header('Location: projects.php');
    exit();
}

// Verify review exists
$stmt = $conn->prepare("SELECT id, user_id, project_id FROM reviews WHERE id = ?");
$stmt->bind_param("i", $reviewId);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) {
    $_SESSION['error'] = 'Review not found.';
    header('Location: projects.php');
    exit();
}

$redirectId = $review['project_id'];

// Authorization: review owner OR admin
$isReviewOwner = ((int)$review['user_id'] === (int)$userId);
$isAuthorized = $isReviewOwner || isAdmin();

if (!$isAuthorized) {
    $_SESSION['error'] = 'You can only delete your own reviews.';
    header("Location: project-details.php?id=$redirectId");
    exit();
}

// Delete review
$stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
$stmt->bind_param("i", $reviewId);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Review deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete review. Please try again.';
}
$stmt->close();

header("Location: project-details.php?id=$redirectId");
exit();
