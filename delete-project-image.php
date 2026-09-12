<?php
/**
 * Delete Project Image Handler — StudentHub
 * Deletes an image from project_images and removes the physical file.
 * Requires user authentication & ownership of the project.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$imageId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$projectId = isset($_REQUEST['project_id']) ? intval($_REQUEST['project_id']) : 0;
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json');

if ($imageId <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid image ID.']);
        exit();
    }
    $_SESSION['error'] = 'Invalid image ID.';
    header('Location: ' . ($projectId > 0 ? "edit-project.php?id=$projectId" : 'dashboard.php'));
    exit();
}

// Perform safe deletion with user ownership check
$deleted = deleteProjectGalleryImage($conn, $imageId, $userId);

if ($deleted) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Image removed successfully.']);
        exit();
    }
    $_SESSION['success'] = 'Image removed successfully.';
} else {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Could not delete image or unauthorized.']);
        exit();
    }
    $_SESSION['error'] = 'Could not delete image or unauthorized.';
}

header('Location: ' . ($projectId > 0 ? "edit-project.php?id=$projectId" : 'dashboard.php'));
exit();
