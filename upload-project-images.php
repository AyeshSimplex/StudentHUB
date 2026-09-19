<?php
/**
 * Upload Project Images Handler — StudentHub
 * Allows authors to upload one or multiple project images after publishing.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
$redirect = isset($_POST['redirect']) && $_POST['redirect'] === 'edit'
            ? "edit-project.php?id=$projectId"
            : "project-details.php?id=$projectId";

if ($projectId <= 0) {
    $_SESSION['error'] = 'Invalid project ID.';
    header('Location: dashboard.php');
    exit();
}

// Verify project ownership
$stmt = $conn->prepare("SELECT id, image FROM projects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $projectId, $userId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    $_SESSION['error'] = 'Project not found or you are not authorized.';
    header('Location: dashboard.php');
    exit();
}

initProjectImagesTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['project_images'])) {
    requireCsrfToken("project-details.php?id=$projectId");
    $caption = trim($_POST['caption'] ?? '');
    $uploadResult = uploadMultipleImages($_FILES['project_images']);

    if (!empty($uploadResult['uploaded'])) {
        $insertStmt = $conn->prepare("INSERT INTO project_images (project_id, image, caption) VALUES (?, ?, ?)");
        foreach ($uploadResult['uploaded'] as $uploadedImage) {
            $insertStmt->bind_param("iss", $projectId, $uploadedImage, $caption);
            $insertStmt->execute();
        }
        $insertStmt->close();

        // If the project doesn't have a cover image yet, set the first uploaded image as cover
        if (empty($project['image']) || !file_exists('uploads/projects/' . $project['image'])) {
            $firstImage = $uploadResult['uploaded'][0];
            $updCover = $conn->prepare("UPDATE projects SET image = ? WHERE id = ?");
            $updCover->bind_param("si", $firstImage, $projectId);
            $updCover->execute();
            $updCover->close();
        }

        $count = count($uploadResult['uploaded']);
        $_SESSION['success'] = $count === 1 ? 'Image added successfully!' : "$count images added to project gallery!";
    }

    if (!empty($uploadResult['errors'])) {
        $_SESSION['error'] = implode('<br>', $uploadResult['errors']);
    }

    if (empty($uploadResult['uploaded']) && empty($uploadResult['errors'])) {
        $_SESSION['error'] = 'Please select at least one image to upload.';
    }
}

header("Location: $redirect");
exit();
