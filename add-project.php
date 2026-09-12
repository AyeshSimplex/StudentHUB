<?php
/**
 * Add Project Page — StudentHub
 * Form for creating a new project with image upload.
 * Protected page — requires authentication.
 */
$pageTitle = 'Add Project';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$errors = [];
$old = [
    'title' => '', 'description' => '', 'category' => '',
    'technologies' => '', 'skills' => '', 'project_url' => '', 'github_url' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $technologies = trim($_POST['technologies'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $projectUrl = trim($_POST['project_url'] ?? '');
    $githubUrl = trim($_POST['github_url'] ?? '');
    $userId = $_SESSION['user_id'];

    // Save old values
    $old = compact('title', 'description', 'category', 'technologies', 'skills');
    $old['project_url'] = $projectUrl;
    $old['github_url'] = $githubUrl;

    // Server-side validation
    if (strlen($title) < 3) {
        $errors[] = 'Project title must be at least 3 characters.';
    }
    if (strlen($description) < 20) {
        $errors[] = 'Description must be at least 20 characters.';
    }
    if (empty($category)) {
        $errors[] = 'Please select a category.';
    }
    if (strlen($technologies) < 2) {
        $errors[] = 'Please enter the technologies used.';
    }

    // Handle image upload
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $imageName = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    // If no errors, insert project
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, category, technologies, skills, image, project_url, github_url, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $title, $description, $category, $technologies, $skills, $imageName, $projectUrl, $githubUrl, $userId);

        if ($stmt->execute()) {
            $newProjectId = $conn->insert_id;
            $stmt->close();

            initProjectImagesTable($conn);

            // Also record primary image in project_images
            if ($imageName) {
                $piStmt = $conn->prepare("INSERT INTO project_images (project_id, image, caption) VALUES (?, ?, 'Cover Image')");
                $piStmt->bind_param("is", $newProjectId, $imageName);
                $piStmt->execute();
                $piStmt->close();
            }

            // Handle optional additional images
            if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
                $galleryUpload = uploadMultipleImages($_FILES['additional_images']);
                if (!empty($galleryUpload['uploaded'])) {
                    $insG = $conn->prepare("INSERT INTO project_images (project_id, image, caption) VALUES (?, ?, 'Project Screenshot')");
                    foreach ($galleryUpload['uploaded'] as $gImg) {
                        $insG->bind_param("is", $newProjectId, $gImg);
                        $insG->execute();
                    }
                    $insG->close();

                    if (!$imageName) {
                        $firstGalleryImg = $galleryUpload['uploaded'][0];
                        $updC = $conn->prepare("UPDATE projects SET image = ? WHERE id = ?");
                        $updC->bind_param("si", $firstGalleryImg, $newProjectId);
                        $updC->execute();
                        $updC->close();
                    }
                }
            }

            $_SESSION['success'] = 'Project published successfully!';
            header("Location: project-details.php?id=$newProjectId&published=1");
            exit();
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-plus-circle me-2"></i>Add New Project</h1>
        <p>Share your work with the StudentHub community</p>
    </div>
</section>

<!-- Add Project Form -->
<section class="section-padding" style="background: var(--bg-alt);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="auth-card fade-in" style="max-width: none;">
                    <h3 class="mb-4"><i class="bi bi-folder-plus me-2 accent-text"></i>Project Details</h3>

                    <!-- Errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo sanitize($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="projectForm" method="POST" action="add-project.php" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label for="project_title" class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_title" name="title" 
                                   value="<?php echo sanitize($old['title']); ?>" placeholder="e.g. Smart Waste Management System" required>
                            <div class="form-error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="project_description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="project_description" name="description" rows="5" 
                                      placeholder="Describe your project in detail..." required><?php echo sanitize($old['description']); ?></textarea>
                            <div class="form-error"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="project_category" name="category" required>
                                        <option value="">Select category</option>
                                        <?php
                                        $categories = ['Information Technology', 'Engineering', 'Materials Technology', 'Science', 'Business', 'Design', 'Agriculture', 'Other'];
                                        foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php echo $old['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_technologies" class="form-label">Technologies Used <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="project_technologies" name="technologies" 
                                           value="<?php echo sanitize($old['technologies']); ?>" placeholder="e.g. PHP, MySQL, JavaScript" required>
                                    <div class="form-error"></div>
                                    <small class="text-muted">Separate with commas</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="project_skills" class="form-label">Skills Demonstrated</label>
                            <input type="text" class="form-control" id="project_skills" name="skills" 
                                   value="<?php echo sanitize($old['skills']); ?>" placeholder="e.g. Full Stack Development, Database Design">
                            <small class="text-muted">Separate with commas</small>
                        </div>

                        <div class="mb-3">
                            <label for="project_image" class="form-label">Primary Cover Image</label>
                            <input type="file" class="form-control" id="project_image" name="image" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">Accepted formats: JPG, PNG, WEBP. Max size: 5MB.</small>
                            <img id="imagePreview" src="" alt="Preview" class="mt-2 rounded" style="display:none; max-height: 200px; width: auto;">
                        </div>

                        <div class="mb-3">
                            <label for="additional_images" class="form-label">Additional Screenshots / Gallery Images <span class="badge bg-light text-dark border">Optional</span></label>
                            <input type="file" class="form-control" id="additional_images" name="additional_images[]" multiple accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>You can select multiple screenshots now or easily add more photos anytime after publishing.</small>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_url" class="form-label">Project URL</label>
                                    <input type="url" class="form-control" id="project_url" name="project_url" 
                                           value="<?php echo sanitize($old['project_url']); ?>" placeholder="https://...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="github_url" class="form-label">GitHub URL</label>
                                    <input type="url" class="form-control" id="github_url" name="github_url" 
                                           value="<?php echo sanitize($old['github_url']); ?>" placeholder="https://github.com/...">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mt-2">
                            <button type="submit" class="btn btn-accent flex-grow-1">
                                <i class="bi bi-plus-lg me-2"></i>Add Project
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
