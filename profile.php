<?php
/**
 * Profile Page — StudentHub
 * Public student portfolio page showing profile and their projects.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get user ID from URL
$profileId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no ID provided and user is logged in, show own profile
if ($profileId <= 0 && isLoggedIn()) {
    $profileId = $_SESSION['user_id'];
}

if ($profileId <= 0) {
    $_SESSION['error'] = 'Please log in to view your profile.';
    header('Location: login.php');
    exit();
}

// Get user data
$user = getUserById($conn, $profileId);
if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header('Location: index.php');
    exit();
}

$pageTitle = $user['full_name'] . "'s Profile";

// Get user's projects
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$userProjects = $stmt->get_result();
$stmt->close();

$projectCount = countUserProjects($conn, $profileId);
$isOwnProfile = (isLoggedIn() && $_SESSION['user_id'] == $profileId);

require_once 'includes/header.php';
?>

<!-- Profile Section -->
<section class="profile-section">
    <div class="container">
        <!-- Profile Header Card -->
        <div class="profile-header-card fade-in">
            <div class="profile-avatar position-relative">
                <?php if (!empty($user['profile_image']) && file_exists('uploads/avatars/' . $user['profile_image'])): ?>
                    <img src="uploads/avatars/<?php echo sanitize($user['profile_image']); ?>" alt="<?php echo sanitize($user['full_name']); ?>">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                <?php endif; ?>

                <?php if ($isOwnProfile): ?>
                    <a href="settings.php?tab=avatar" class="avatar-edit-overlay-btn" title="Change profile photo">
                        <i class="bi bi-camera-fill"></i>
                    </a>
                <?php endif; ?>
            </div>
            <h2 class="profile-name"><?php echo sanitize($user['full_name']); ?></h2>
            <p class="profile-username">@<?php echo sanitize($user['username']); ?></p>

            <div class="profile-meta">
                <?php if ($user['faculty']): ?>
                    <div class="profile-meta-item">
                        <i class="bi bi-building"></i>
                        <span><?php echo sanitize($user['faculty']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="profile-meta-item">
                    <i class="bi bi-folder"></i>
                    <span><?php echo $projectCount; ?> Project<?php echo $projectCount != 1 ? 's' : ''; ?></span>
                </div>
                <div class="profile-meta-item">
                    <i class="bi bi-calendar3"></i>
                    <span>Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>

            <?php if ($user['skills']): ?>
                <div class="profile-skills">
                    <?php
                    $skills = explode(',', $user['skills']);
                    foreach ($skills as $skill):
                        $skill = trim($skill);
                        if ($skill):
                    ?>
                        <span class="skill-tag"><?php echo sanitize($skill); ?></span>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isLoggedIn() && $_SESSION['user_id'] == $profileId): ?>
                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 w-100">
                    <a href="settings.php" class="btn btn-outline-accent btn-sm d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-gear me-1"></i>Account Settings
                    </a>
                    <a href="add-project.php" class="btn btn-accent btn-sm d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-plus-lg me-1"></i>Add Project
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- User's Projects -->
        <div class="fade-in" id="projects">
            <h3 class="mb-4">
                <i class="bi bi-grid me-2 accent-text"></i>
                Projects by <?php echo sanitize($user['full_name']); ?>
            </h3>
        </div>

        <div class="row g-4">
            <?php if ($userProjects->num_rows > 0): ?>
                <?php $pi = 0; while ($project = $userProjects->fetch_assoc()): $pi++; ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="project-card fade-in delay-<?php echo min($pi, 6); ?>">
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
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="project-card-footer">
                                    <span class="project-card-date">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                    </span>
                                    <div class="d-flex gap-2">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $project['user_id']): ?>
                                            <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-accent btn-sm" title="Edit Project">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                        <?php endif; ?>
                                        <a href="project-details.php?id=<?php echo $project['id']; ?>" class="btn btn-accent btn-sm">
                                            View <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-folder2-open d-block"></i>
                        <h4>No projects yet</h4>
                        <p>This student hasn't added any projects yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
