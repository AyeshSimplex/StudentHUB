<?php
/**
 * Set Cover Image Handler — StudentHub
 * Sets a specific image as the main project cover image.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$projectId = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
$imageName = isset($_REQUEST['image']) ? basename(trim($_REQUEST['image'])) : '';
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json');

if ($projectId <= 0 || empty($imageName)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
        exit();
    }
    $_SESSION['error'] = 'Invalid parameters.';
    header('Location: ' . ($projectId > 0 ? "edit-project.php?id=$projectId" : 'dashboard.php'));
    exit();
}

// Verify that the image belongs to this project
initProjectImagesTable($conn);
$stmt = $conn->prepare("SELECT pi.id FROM project_images pi JOIN projects p ON pi.project_id = p.id WHERE pi.project_id = ? AND pi.image = ? AND p.user_id = ?");
$stmt->bind_param("isi", $projectId, $imageName, $userId);
$stmt->execute();
$valid = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$valid) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Image not found or unauthorized.']);
        exit();
    }
    $_SESSION['error'] = 'Image not found or unauthorized.';
    header('Location: edit-project.php?id=' . $projectId);
    exit();
}

$updated = setProjectCoverImage($conn, $projectId, $imageName, $userId);

if ($updated) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cover image updated successfully.']);
        exit();
    }
    $_SESSION['success'] = 'Cover image updated successfully!';
} else {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update cover image.']);
        exit();
    }
    $_SESSION['error'] = 'Failed to update cover image.';
}

header('Location: edit-project.php?id=' . $projectId);
exit();
