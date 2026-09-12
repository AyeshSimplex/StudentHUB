<?php
/**
 * Projects Page — StudentHub
 * Browse, search, filter, and sort all student projects.
 */
$pageTitle = 'Projects';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Get category from URL if provided (from category card click)
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

// Fetch all projects with user info
ensureUserAvatarColumn($conn);
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.username, u.profile_image FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$stmt->execute();
$allProjects = $stmt->get_result();
$stmt->close();
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-grid me-2"></i>Explore Student Projects</h1>
        <p>Discover ideas, technologies and innovations created by university students.</p>
    </div>
</section>

<!-- Projects Content -->
<section class="section-padding">
    <div class="container">

        <!-- Filters Bar -->
        <div class="filters-bar fade-in">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5 col-md-12">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                        <input type="text" id="projectSearch" class="form-control filter-input" style="padding-left: 40px;" 
                               placeholder="Search projects, technologies, students..." aria-label="Search projects">
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <select id="categoryFilter" class="form-select filter-select" aria-label="Filter by category">
                        <option value="all" <?php echo $selectedCategory === '' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="Information Technology" <?php echo $selectedCategory === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Engineering" <?php echo $selectedCategory === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                        <option value="Materials Technology" <?php echo $selectedCategory === 'Materials Technology' ? 'selected' : ''; ?>>Materials Technology</option>
                        <option value="Science" <?php echo $selectedCategory === 'Science' ? 'selected' : ''; ?>>Science</option>
                        <option value="Business" <?php echo $selectedCategory === 'Business' ? 'selected' : ''; ?>>Business</option>
                        <option value="Design" <?php echo $selectedCategory === 'Design' ? 'selected' : ''; ?>>Design</option>
                        <option value="Agriculture" <?php echo $selectedCategory === 'Agriculture' ? 'selected' : ''; ?>>Agriculture</option>
                        <option value="Other" <?php echo $selectedCategory === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select id="sortFilter" class="form-select filter-select" aria-label="Sort projects">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="az">A — Z</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Project Count -->
        <div class="projects-count" id="projectCount">
            Showing <strong><?php echo $allProjects->num_rows; ?></strong> project<?php echo $allProjects->num_rows != 1 ? 's' : ''; ?>
        </div>

        <!-- Projects Grid -->
        <div class="row g-4" id="projectsGrid">
            <?php if ($allProjects->num_rows > 0): ?>
                <?php while ($project = $allProjects->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 project-filter-item"
                         data-title="<?php echo sanitize($project['title']); ?>"
                         data-description="<?php echo sanitize(substr($project['description'], 0, 200)); ?>"
                         data-category="<?php echo sanitize($project['category']); ?>"
                         data-technologies="<?php echo sanitize($project['technologies']); ?>"
                         data-author="<?php echo sanitize($project['full_name']); ?>"
                         data-date="<?php echo $project['created_at']; ?>">
                        
                        <div class="project-card">
                            <!-- Image -->
                            <?php if ($project['image'] && file_exists('uploads/projects/' . $project['image'])): ?>
                                <div class="project-card-image">
                                    <img src="uploads/projects/<?php echo sanitize($project['image']); ?>" 
                                         alt="<?php echo sanitize($project['title']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="project-card-placeholder">
                                    <i class="bi bi-folder2-open"></i>
                                </div>
                            <?php endif; ?>

                            <div class="project-card-body">
                                <span class="project-category-badge"><?php echo sanitize($project['category']); ?></span>
                                <h5 class="project-card-title">
                                    <a href="project-details.php?id=<?php echo $project['id']; ?>">
                                        <?php echo sanitize($project['title']); ?>
                                    </a>
                                </h5>
                                <p class="project-card-desc">
                                    <?php echo sanitize(truncateText($project['description'], 120)); ?>
                                </p>

                                <?php if ($project['technologies']): ?>
                                    <div class="project-card-tech">
                                        <?php
                                        $techs = explode(',', $project['technologies']);
                                        $shown = array_slice($techs, 0, 3);
                                        foreach ($shown as $tech): ?>
                                            <span class="tech-tag"><?php echo sanitize(trim($tech)); ?></span>
                                        <?php endforeach;
                                        if (count($techs) > 3): ?>
                                            <span class="tech-tag">+<?php echo count($techs) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="project-card-footer">
                                    <div class="project-card-author">
                                        <div class="author-avatar">
                                            <?php if (!empty($project['profile_image']) && file_exists('uploads/avatars/' . $project['profile_image'])): ?>
                                                <img src="uploads/avatars/<?php echo sanitize($project['profile_image']); ?>" alt="<?php echo sanitize($project['full_name']); ?>">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($project['full_name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="author-name"><?php echo sanitize($project['full_name']); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $project['user_id']): ?>
                                            <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-accent py-0 px-2" style="font-size: 0.75rem;" title="Edit Project">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                        <?php endif; ?>
                                        <span class="project-card-date"><?php echo getTimeAgo($project['created_at']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- No Results Message -->
        <div class="no-results" id="noResults" style="display: none;">
            <i class="bi bi-search d-block"></i>
            <h4>No projects found</h4>
            <p>Try another keyword or category.</p>
        </div>

        <?php if ($allProjects->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-folder2-open d-block"></i>
                <h4>No projects available yet</h4>
                <p>Be the first to share your project with the community!</p>
                <a href="add-project.php" class="btn btn-accent">Add Your First Project</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Auto-trigger filter if category was passed via URL -->
<?php if ($selectedCategory): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            categoryFilter.value = '<?php echo addslashes($selectedCategory); ?>';
            categoryFilter.dispatchEvent(new Event('change'));
        }
    });
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
