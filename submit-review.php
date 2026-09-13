<?php
/**
 * Submit Review — StudentHub
 * Allows a logged-in user to submit a review and star rating for any project.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];

// Ensure reviews table exists
ensureReviewsTable($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: projects.php');
    exit();
}

$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$reviewText = trim($_POST['review_text'] ?? '');

if ($projectId <= 0) {
    $_SESSION['error'] = 'Invalid project ID.';
    header('Location: projects.php');
    exit();
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    $_SESSION['error'] = 'Please select a star rating between 1 and 5.';
    header("Location: project-details.php?id=$projectId");
    exit();
}

// Verify the project exists
$stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    $_SESSION['error'] = 'Project not found.';
    header('Location: projects.php');
    exit();
}

// Check if user already reviewed this project
$stmt = $conn->prepare("SELECT id FROM reviews WHERE project_id = ? AND user_id = ?");
$stmt->bind_param("ii", $projectId, $userId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update existing review
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ?, created_at = CURRENT_TIMESTAMP WHERE project_id = ? AND user_id = ?");
    $stmt->bind_param("isii", $rating, $reviewText, $projectId, $userId);
    $action = 'updated';
} else {
    // Insert new review
    $stmt = $conn->prepare("INSERT INTO reviews (project_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $projectId, $userId, $rating, $reviewText);
    $action = 'submitted';
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Your review has been $action successfully.";
} else {
    $_SESSION['error'] = 'Failed to submit review. Please try again.';
}
$stmt->close();

header("Location: project-details.php?id=$projectId");
exit();
