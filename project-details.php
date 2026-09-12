<?php
/**
 * Project Details Page — StudentHub
 * Displays full details for a single project, multi-image gallery, and author editing/management options.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get project ID from URL
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($projectId <= 0) {
    $_SESSION['error'] = 'Invalid project ID.';
    header('Location: projects.php');
    exit();
}

// Fetch project with user info
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.username, u.id as user_id FROM projects p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    $_SESSION['error'] = 'Project not found.';
    header('Location: projects.php');
    exit();
}

// Check if current logged-in user is the author
$currentUserId = $_SESSION['user_id'] ?? null;
$isAuthor = ($currentUserId && $currentUserId == $project['user_id']);

// Fetch gallery images
$galleryImages = getProjectImages($conn, $projectId);

// Build display image list
$allImages = [];
if (!empty($project['image']) && file_exists('uploads/projects/' . $project['image'])) {
    $allImages[] = [
        'image' => $project['image'],
        'caption' => 'Cover Image',
        'is_cover' => true
    ];
}
foreach ($galleryImages as $gImg) {
    if (!empty($project['image']) && $gImg['image'] === $project['image']) {
        continue; // Don't duplicate cover image
    }
    if (file_exists('uploads/projects/' . $gImg['image'])) {
        $allImages[] = [
            'id' => $gImg['id'],
            'image' => $gImg['image'],
            'caption' => $gImg['caption'] ?: 'Project Screenshot',
            'is_cover' => false
        ];
    }
}

$pageTitle = $project['title'];
require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1><?php echo sanitize($project['title']); ?></h1>
                <p><span class="project-category-badge" style="font-size: 0.85rem;"><?php echo sanitize($project['category']); ?></span></p>
            </div>
            
            <?php if ($isAuthor): ?>
                <div class="d-flex flex-wrap gap-2">
                    <a href="edit-project.php?id=<?php echo $projectId; ?>" class="btn btn-accent">
                        <i class="bi bi-pencil-square me-1"></i>Edit Project
                    </a>
                    <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#addImagesModal">
                        <i class="bi bi-images me-1"></i>+ Add Images
                    </button>
                    <a href="delete-project.php?id=<?php echo $projectId; ?>" class="btn btn-outline-danger" 
                       onclick="return confirm('Are you sure you want to delete this project? This cannot be undone.');">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Project Details Section -->
<section class="section-padding">
    <div class="container">

        <!-- Flash Messages -->
        <?php echo flashMessage('success'); ?>
        <?php echo flashMessage('error'); ?>

        <!-- Post-Publish Celebration Banner -->
        <?php if (isset($_GET['published']) && $_GET['published'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4 p-3" style="border-radius: var(--radius); background: linear-gradient(135deg, rgba(0, 188, 212, 0.15), rgba(76, 175, 80, 0.15)); border-left: 4px solid var(--accent) !important;" role="alert">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="fs-2 me-3 text-success">🎉</div>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold text-dark">Your project has been published!</h5>
                            <p class="mb-0 text-muted">You can now view your live project, add more screenshots or photos below, or edit the details anytime.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#addImagesModal">
                            <i class="bi bi-camera me-1"></i>Upload More Photos
                        </button>
                        <a href="edit-project.php?id=<?php echo $projectId; ?>" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-pencil me-1"></i>Edit Details
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Author Banner -->
        <?php if ($isAuthor && !isset($_GET['published'])): ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--radius); background: rgba(0, 188, 212, 0.08); border-left: 4px solid var(--accent) !important;">
                <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check-fill accent-text fs-4 me-3"></i>
                        <div>
                            <strong class="text-dark">You are viewing your own published project</strong>
                            <div class="text-muted small">Need to make updates or add project screenshots? Use the author controls anytime.</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="edit-project.php?id=<?php echo $projectId; ?>" class="btn btn-sm btn-accent">
                            <i class="bi bi-pencil-square me-1"></i>Edit Project
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addImagesModal">
                            <i class="bi bi-images me-1"></i>+ Add Images
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <!-- Left: Image Gallery Showcase -->
            <div class="col-lg-6 fade-in">
                <div class="project-gallery-wrapper">
                    <!-- Main Active Image -->
                    <div class="project-detail-image position-relative rounded overflow-hidden shadow-sm" style="background: #0f172a; min-height: 320px;">
                        <?php if (!empty($allImages)): ?>
                            <img id="mainGalleryImage" 
                                 src="uploads/projects/<?php echo sanitize($allImages[0]['image']); ?>" 
                                 alt="<?php echo sanitize($project['title']); ?>"
                                 class="w-100 h-100" 
                                 style="object-fit: cover; max-height: 420px; transition: opacity 0.3s ease; cursor: pointer;"
                                 onclick="openLightbox(this.src, document.getElementById('mainGalleryCaption').innerText);">
                            
                            <div id="mainGalleryCaption" class="position-absolute bottom-0 start-0 end-0 px-3 py-2 text-white small" 
                                 style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);">
                                <?php echo sanitize($allImages[0]['caption'] ?? $project['title']); ?>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center text-muted w-100 h-100">
                                <i class="bi bi-folder2-open" style="font-size: 4rem; opacity: 0.4;"></i>
                                <span class="mt-2">No images uploaded yet</span>
                                <?php if ($isAuthor): ?>
                                    <button type="button" class="btn btn-sm btn-accent mt-3" data-bs-toggle="modal" data-bs-target="#addImagesModal">
                                        <i class="bi bi-camera me-1"></i>Add Images to Project
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thumbnails Strip (if multiple images or author can add) -->
                    <?php if (count($allImages) > 1 || $isAuthor): ?>
                        <div class="d-flex gap-2 mt-3 overflow-auto pb-2 project-thumbnails-strip align-items-center">
                            <?php foreach ($allImages as $idx => $img): ?>
                                <div class="gallery-thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>" 
                                     style="width: 80px; height: 60px; flex-shrink: 0; cursor: pointer; border-radius: 8px; overflow: hidden; border: 2px solid <?php echo $idx === 0 ? 'var(--accent)' : 'transparent'; ?>;"
                                     onclick="switchGalleryImage('uploads/projects/<?php echo sanitize($img['image']); ?>', '<?php echo addslashes(sanitize($img['caption'])); ?>', this)">
                                    <img src="uploads/projects/<?php echo sanitize($img['image']); ?>" 
                                         alt="thumb" class="w-100 h-100" style="object-fit: cover;">
                                </div>
                            <?php endforeach; ?>

                            <?php if ($isAuthor): ?>
                                <div class="gallery-add-thumb d-flex flex-column align-items-center justify-content-center"
                                     style="width: 80px; height: 60px; flex-shrink: 0; cursor: pointer; border-radius: 8px; border: 2px dashed var(--accent); background: rgba(0,188,212,0.05); color: var(--accent);"
                                     data-bs-toggle="modal" data-bs-target="#addImagesModal" title="Upload more images">
                                    <i class="bi bi-plus-lg fs-5"></i>
                                    <span style="font-size: 0.65rem; font-weight: 600;">+ Add</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i>Click any thumbnail to preview, or click main image to enlarge.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Info -->
            <div class="col-lg-6 fade-in delay-1">
                <div class="project-detail-info">
                    <h2 class="project-detail-title"><?php echo sanitize($project['title']); ?></h2>

                    <div class="detail-meta">
                        <div class="detail-meta-item">
                            <i class="bi bi-tag"></i>
                            <div>
                                <div class="detail-meta-label">Category</div>
                                <div class="detail-meta-value"><?php echo sanitize($project['category']); ?></div>
                            </div>
                        </div>

                        <div class="detail-meta-item">
                            <i class="bi bi-person"></i>
                            <div>
                                <div class="detail-meta-label">Created By</div>
                                <div class="detail-meta-value">
                                    <a href="profile.php?id=<?php echo $project['user_id']; ?>">
                                        <?php echo sanitize($project['full_name']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="detail-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <div class="detail-meta-label">Date Published</div>
                                <div class="detail-meta-value"><?php echo date('F j, Y', strtotime($project['created_at'])); ?></div>
                            </div>
                        </div>

                        <?php if ($project['technologies']): ?>
                            <div class="detail-meta-item">
                                <i class="bi bi-code-slash"></i>
                                <div>
                                    <div class="detail-meta-label">Technologies</div>
                                    <div class="detail-meta-value">
                                        <?php
                                        $techs = explode(',', $project['technologies']);
                                        foreach ($techs as $tech): ?>
                                            <span class="tech-tag"><?php echo sanitize(trim($tech)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($project['skills']): ?>
                            <div class="detail-meta-item">
                                <i class="bi bi-lightbulb"></i>
                                <div>
                                    <div class="detail-meta-label">Skills</div>
                                    <div class="detail-meta-value">
                                        <?php
                                        $skills = explode(',', $project['skills']);
                                        foreach ($skills as $skill): ?>
                                            <span class="skill-tag"><?php echo sanitize(trim($skill)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Links -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if ($project['project_url']): ?>
                            <a href="<?php echo sanitize($project['project_url']); ?>" target="_blank" class="btn btn-accent">
                                <i class="bi bi-globe me-2"></i>View Live Demo
                            </a>
                        <?php endif; ?>
                        <?php if ($project['github_url']): ?>
                            <a href="<?php echo sanitize($project['github_url']); ?>" target="_blank" class="btn btn-outline-accent">
                                <i class="bi bi-github me-2"></i>View on GitHub
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($isAuthor): ?>
                        <div class="p-3 rounded border bg-light mt-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-semibold small text-dark"><i class="bi bi-gear-fill me-1 accent-text"></i>Author Actions</span>
                                <span class="badge bg-secondary">Owner</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a href="edit-project.php?id=<?php echo $projectId; ?>" class="btn btn-sm btn-accent flex-grow-1">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Details & Manage Gallery
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#addImagesModal">
                                    <i class="bi bi-images me-1"></i>+ Add Images
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="row mt-5">
            <div class="col-12 fade-in">
                <div class="card border-0 shadow-sm" style="border-radius: var(--radius);">
                    <div class="card-body p-4">
                        <h4 class="mb-3"><i class="bi bi-file-text me-2 accent-text"></i>Project Description</h4>
                        <p class="project-detail-desc mb-0">
                            <?php echo nl2br(sanitize($project['description'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="d-flex flex-wrap gap-3 mt-4 fade-in">
            <a href="projects.php" class="btn btn-outline-accent">
                <i class="bi bi-arrow-left me-2"></i>Back to Projects
            </a>
            <a href="profile.php?id=<?php echo $project['user_id']; ?>" class="btn btn-primary-custom">
                <i class="bi bi-person me-2"></i>View Student Profile
            </a>
            <?php if ($isAuthor): ?>
                <a href="edit-project.php?id=<?php echo $projectId; ?>" class="btn btn-accent">
                    <i class="bi bi-pencil-square me-2"></i>Edit Project
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Author Modal: Quick Add Images -->
<?php if ($isAuthor): ?>
<div class="modal fade" id="addImagesModal" tabindex="-1" aria-labelledby="addImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius);">
            <form method="POST" action="upload-project-images.php" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                <input type="hidden" name="redirect" value="details">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addImagesModalLabel">
                        <i class="bi bi-images me-2 accent-text"></i>Add Images to Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-muted small mb-3">
                        Upload screenshots, diagrams, UI mockups, or hardware photos to showcase your work after publishing.
                    </p>

                    <div class="mb-3">
                        <label for="modal_images" class="form-label fw-semibold">Select Images <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="modal_images" name="project_images[]" multiple accept="image/jpeg,image/png,image/webp" required>
                        <small class="text-muted">You can select multiple files at once (JPG, PNG, WEBP, up to 5MB each).</small>
                    </div>

                    <div class="mb-3">
                        <label for="modal_caption" class="form-label fw-semibold">Caption / Label (Optional)</label>
                        <input type="text" class="form-control" id="modal_caption" name="caption" placeholder="e.g. Dashboard view / Circuit prototype">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-cloud-arrow-up me-1"></i>Upload Images
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Fullscreen Image Lightbox Modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="lightboxImg" src="" alt="Enlarged view" class="img-fluid rounded shadow-lg" style="max-height: 85vh;">
                <div id="lightboxCaption" class="text-white mt-2 small fw-semibold"></div>
            </div>
        </div>
    </div>
</div>

<script>
function switchGalleryImage(imgSrc, caption, thumbEl) {
    const mainImg = document.getElementById('mainGalleryImage');
    const capEl = document.getElementById('mainGalleryCaption');
    if (!mainImg) return;

    mainImg.style.opacity = '0.3';
    setTimeout(() => {
        mainImg.src = imgSrc;
        if (capEl) capEl.innerText = caption || '';
        mainImg.style.opacity = '1';
    }, 150);

    // Update active border
    document.querySelectorAll('.gallery-thumb-item').forEach(item => {
        item.style.borderColor = 'transparent';
    });
    if (thumbEl) {
        thumbEl.style.borderColor = 'var(--accent)';
    }
}

function openLightbox(src, caption) {
    const lbImg = document.getElementById('lightboxImg');
    const lbCap = document.getElementById('lightboxCaption');
    if (!lbImg) return;
    lbImg.src = src;
    if (lbCap) lbCap.innerText = caption || '';
    const modal = new bootstrap.Modal(document.getElementById('lightboxModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
