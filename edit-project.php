<?php
/**
 * Edit Project Page — StudentHub
 * Allows users to edit their projects and manage project images/gallery after publish.
 * Protected page — requires authentication + authorization.
 */
$pageTitle = 'Edit Project';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];

// Get project ID from URL
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($projectId <= 0) {
    $_SESSION['error'] = 'Invalid project ID.';
    header('Location: dashboard.php');
    exit();
}

initProjectImagesTable($conn);

// Fetch the project
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

// Authorization check — user can only edit their own projects
if ((int)$project['user_id'] !== (int)$userId) {
    $_SESSION['error'] = 'You are not authorized to edit this project.';
    header('Location: dashboard.php');
    exit();
}

// Use project data as defaults
$old = [
    'title' => $project['title'],
    'description' => $project['description'],
    'category' => $project['category'],
    'technologies' => $project['technologies'],
    'skills' => $project['skills'],
    'project_url' => $project['project_url'],
    'github_url' => $project['github_url'],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken("edit-project.php?id=$projectId");
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $technologies = trim($_POST['technologies'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $projectUrl = trim($_POST['project_url'] ?? '');
    $githubUrl = trim($_POST['github_url'] ?? '');

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

    // Handle cover image
    $imageName = $project['image'];

    // Check if user requested to remove current cover image
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] == '1') {
        if ($project['image'] && file_exists('uploads/projects/' . $project['image'])) {
            // Check if also in project_images before unlinking
            $chkStmt = $conn->prepare("SELECT id FROM project_images WHERE image = ?");
            $chkStmt->bind_param("s", $project['image']);
            $chkStmt->execute();
            $inGallery = $chkStmt->get_result()->fetch_assoc();
            $chkStmt->close();
            if (!$inGallery) {
                @unlink('uploads/projects/' . $project['image']);
            }
        }
        $imageName = null;
    }

    // Check if a new cover image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            // Delete old image if not in gallery
            if ($imageName && file_exists('uploads/projects/' . $imageName)) {
                $chkStmt = $conn->prepare("SELECT id FROM project_images WHERE image = ?");
                $chkStmt->bind_param("s", $imageName);
                $chkStmt->execute();
                $inGallery = $chkStmt->get_result()->fetch_assoc();
                $chkStmt->close();
                if (!$inGallery) {
                    @unlink('uploads/projects/' . $imageName);
                }
            }
            $imageName = $upload['filename'];

            // Also insert into project_images as cover
            $insPi = $conn->prepare("INSERT INTO project_images (project_id, image, caption) VALUES (?, ?, 'Cover Image')");
            $insPi->bind_param("is", $projectId, $imageName);
            $insPi->execute();
            $insPi->close();
        } else {
            $errors[] = $upload['error'];
        }
    }

    // Check if additional gallery images were also selected
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $galleryUpload = uploadMultipleImages($_FILES['gallery_images']);
        if (!empty($galleryUpload['uploaded'])) {
            $insGallery = $conn->prepare("INSERT INTO project_images (project_id, image, caption) VALUES (?, ?, 'Project Screenshot')");
            foreach ($galleryUpload['uploaded'] as $galImg) {
                $insGallery->bind_param("is", $projectId, $galImg);
                $insGallery->execute();
            }
            $insGallery->close();

            // If project has no cover, make the first gallery image cover
            if (empty($imageName)) {
                $imageName = $galleryUpload['uploaded'][0];
            }
        }
        if (!empty($galleryUpload['errors'])) {
            $errors = array_merge($errors, $galleryUpload['errors']);
        }
    }

    // If no errors, update project
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, category = ?, technologies = ?, skills = ?, image = ?, project_url = ?, github_url = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssssssii", $title, $description, $category, $technologies, $skills, $imageName, $projectUrl, $githubUrl, $projectId, $userId);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Project updated successfully!';
            $stmt->close();

            // Redirect based on user choice
            if (isset($_POST['save_action']) && $_POST['save_action'] === 'view') {
                header("Location: project-details.php?id=$projectId");
            } else {
                header("Location: edit-project.php?id=$projectId");
            }
            exit();
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch existing gallery images
$galleryImages = getProjectImages($conn, $projectId);

require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1><i class="bi bi-pencil-square me-2"></i>Edit Project</h1>
                <p>Update project information and manage images after publishing</p>
            </div>
            <div class="d-flex gap-2">
                <a href="project-details.php?id=<?php echo $projectId; ?>" class="btn btn-outline-light">
                    <i class="bi bi-eye me-1"></i>View Live Project
                </a>
                <a href="dashboard.php" class="btn btn-outline-light">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Edit Project Content -->
<section class="section-padding" style="background: var(--bg-alt);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">

                <!-- Flash Message -->
                <?php echo flashMessage('success'); ?>
                <?php echo flashMessage('error'); ?>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Left: Main Details Form -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius); background: var(--bg-card);">
                            <h4 class="mb-3 text-dark">
                                <i class="bi bi-pencil-fill me-2 accent-text"></i>Project Information
                            </h4>
                            <p class="text-muted small mb-4">Edit project details, primary cover image, and skills demonstrated.</p>

                            <form id="editProjectForm" method="POST" action="edit-project.php?id=<?php echo $projectId; ?>" enctype="multipart/form-data" novalidate>
                                <?php echo csrfField(); ?>

                                <div class="mb-3">
                                    <label for="project_title" class="form-label">Project Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="project_title" name="title"
                                           value="<?php echo sanitize($old['title']); ?>" required>
                                    <div class="form-error"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="project_description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="project_description" name="description" rows="6" required><?php echo sanitize($old['description']); ?></textarea>
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
                                                   value="<?php echo sanitize($old['technologies']); ?>" required>
                                            <div class="form-error"></div>
                                            <small class="text-muted">Separate with commas</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="project_skills" class="form-label">Skills Demonstrated</label>
                                    <input type="text" class="form-control" id="project_skills" name="skills"
                                           value="<?php echo sanitize($old['skills']); ?>">
                                    <small class="text-muted">Separate with commas (e.g. IoT, Circuit Design, PHP)</small>
                                </div>

                                <!-- Cover Image Field -->
                                <div class="mb-4 p-3 rounded border" style="background: var(--bg-alt);">
                                    <label for="project_image" class="form-label fw-semibold">Primary Cover Image</label>

                                    <?php if ($project['image'] && file_exists('uploads/projects/' . $project['image'])): ?>
                                        <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded bg-white border">
                                            <img src="uploads/projects/<?php echo sanitize($project['image']); ?>"
                                                 alt="Current cover" class="rounded" style="height: 70px; width: 100px; object-fit: cover;">
                                            <div>
                                                <span class="badge bg-primary mb-1"><i class="bi bi-star-fill text-warning me-1"></i>Current Cover</span>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="remove_cover">
                                                    <label class="form-check-label small text-danger" for="remove_cover">
                                                        <i class="bi bi-trash me-1"></i>Remove cover image
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <input type="file" class="form-control" id="project_image" name="image" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted d-block mt-1">Upload a new image to set or replace the cover (JPG, PNG, WEBP, max 5MB).</small>
                                    <img id="imagePreview" src="" alt="Preview" class="mt-2 rounded" style="display:none; max-height: 180px; width: auto;">
                                </div>

                                <!-- Optional Additional Gallery Images -->
                                <div class="mb-4">
                                    <label for="gallery_images" class="form-label fw-semibold">
                                        Upload Additional Screenshots / Photos <span class="badge bg-light text-dark border">Optional</span>
                                    </label>
                                    <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">You can select multiple files to add to the project gallery immediately.</small>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="project_url" class="form-label">Live Project / Demo URL</label>
                                            <input type="url" class="form-control" id="project_url" name="project_url"
                                                   value="<?php echo sanitize($old['project_url']); ?>" placeholder="https://myproject.example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="github_url" class="form-label">GitHub Repository URL</label>
                                            <input type="url" class="form-control" id="github_url" name="github_url"
                                                   value="<?php echo sanitize($old['github_url']); ?>" placeholder="https://github.com/user/repo">
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="d-grid gap-3 d-md-flex mt-3">
                                    <button type="submit" name="save_action" value="save" class="btn btn-accent flex-fill">
                                        <i class="bi bi-check-circle-fill me-2"></i>Save Changes
                                    </button>
                                    <button type="submit" name="save_action" value="view" class="btn btn-outline-accent flex-fill">
                                        <i class="bi bi-save me-1"></i>Save & View Live
                                    </button>
                                    <a href="project-details.php?id=<?php echo $projectId; ?>" class="btn btn-outline-secondary flex-fill">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Quick Gallery Manager (Post-Publish) -->
                    <div class="col-lg-5">
                        <!-- Quick Add Gallery Images Card -->
                        <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: var(--radius); background: var(--bg-card);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 text-dark">
                                    <i class="bi bi-images me-2 accent-text"></i>Add Gallery Images
                                </h5>
                                <span class="badge bg-light text-dark border">Instant Upload</span>
                            </div>
                            <p class="text-muted small mb-3">Add screenshots, circuit diagrams, or UI mockups directly to your project.</p>

                            <!-- Multi-image Upload Form -->
                            <form method="POST" action="upload-project-images.php" enctype="multipart/form-data">
                                <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                <input type="hidden" name="redirect" value="edit">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Select One or More Images <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control form-control-sm" name="project_images[]" multiple accept="image/jpeg,image/png,image/webp" required>
                                    <small class="text-muted">JPG, PNG, WEBP (up to 5MB each).</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Optional Caption / Label</label>
                                    <input type="text" class="form-control form-control-sm" name="caption" placeholder="e.g. Dashboard view / Circuit prototype">
                                </div>

                                <button type="submit" class="btn btn-sm btn-accent w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Upload to Gallery
                                </button>
                            </form>
                        </div>

                        <!-- Existing Project Gallery Showcase & Management -->
                        <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius); background: var(--bg-card);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 text-dark">
                                    <i class="bi bi-collection me-2 accent-text"></i>Gallery Photos
                                </h5>
                                <span class="badge bg-primary rounded-pill"><?php echo count($galleryImages); ?></span>
                            </div>

                            <?php if (!empty($galleryImages)): ?>
                                <div class="row g-2">
                                    <?php foreach ($galleryImages as $img):
                                        $isCover = ($project['image'] === $img['image']);
                                    ?>
                                        <div class="col-6">
                                            <div class="position-relative rounded border overflow-hidden bg-light" style="height: 130px;">
                                                <img src="uploads/projects/<?php echo sanitize($img['image']); ?>"
                                                     alt="<?php echo sanitize($img['caption'] ?? 'Project image'); ?>"
                                                     class="w-100 h-100" style="object-fit: cover;">

                                                <?php if ($isCover): ?>
                                                    <span class="badge bg-primary position-absolute top-0 start-0 m-1" style="font-size: 0.65rem;">
                                                        <i class="bi bi-star-fill text-warning me-1"></i>Cover
                                                    </span>
                                                <?php endif; ?>

                                                <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-75 d-flex justify-content-between align-items-center">
                                                    <?php if (!$isCover): ?>
                                                        <a href="set-cover-image.php?project_id=<?php echo $projectId; ?>&image=<?php echo urlencode($img['image']); ?>"
                                                           class="btn btn-sm btn-outline-light py-0 px-1" style="font-size: 0.7rem;" title="Make Cover Image">
                                                            <i class="bi bi-star"></i> Set Cover
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-white-50" style="font-size: 0.7rem;"><i class="bi bi-check2"></i> Active</span>
                                                    <?php endif; ?>

                                                    <a href="delete-project-image.php?id=<?php echo $img['id']; ?>&project_id=<?php echo $projectId; ?>"
                                                       class="btn btn-sm btn-danger py-0 px-1" style="font-size: 0.7rem;"
                                                       onclick="return confirm('Are you sure you want to delete this image?');" title="Delete Image">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <?php if (!empty($img['caption'])): ?>
                                                <small class="text-muted d-block text-truncate mt-1" style="font-size: 0.75rem;" title="<?php echo sanitize($img['caption']); ?>">
                                                    <?php echo sanitize($img['caption']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-images d-block mb-2" style="font-size: 2.2rem; opacity: 0.4;"></i>
                                    No gallery images uploaded yet.<br>Use the upload box above to add screenshots to this project.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
