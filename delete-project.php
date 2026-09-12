<?php
/**
 * Delete Project — StudentHub
 * Deletes a project ONLY if it belongs to the logged-in user.
 * Protected page — requires authentication + authorization.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($projectId <= 0) {
    $_SESSION['error'] = 'Invalid project ID.';
    header('Location: dashboard.php');
    exit();
}

// Fetch the project to verify ownership
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    $_SESSION['error'] = 'Project not found.';
    header('Location: dashboard.php');
    exit();
}

// Authorization check — user can only delete their own projects
if ($project['user_id'] != $userId) {
    $_SESSION['error'] = 'You are not authorized to delete this project.';
    header('Location: dashboard.php');
    exit();
}

// Delete the project image file if it exists
if ($project['image'] && file_exists('uploads/projects/' . $project['image'])) {
    unlink('uploads/projects/' . $project['image']);
}

// Delete the project from database
$stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $projectId, $userId);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Project deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete the project. Please try again.';
}
$stmt->close();

header('Location: dashboard.php');
exit();
?>
