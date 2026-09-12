<?php
/**
 * Dashboard — StudentHub
 * User's personal dashboard showing their projects and stats.
 * Protected page — requires authentication.
 */
$pageTitle = 'Dashboard';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Get user data
$user = getUserById($conn, $userId);

// Get user's projects
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userProjects = $stmt->get_result();
$stmt->close();

// Count stats
$projectCount = countUserProjects($conn, $userId);
$skillsArray = $user['skills'] ? explode(',', $user['skills']) : [];
$skillCount = count(array_filter($skillsArray));

// Get recent contact inquiries (dispatched to ent2023048@tec.rjt.ac.lk)
$msgCountResult = $conn->query("SELECT COUNT(*) as count FROM messages");
$messageCount = $msgCountResult ? $msgCountResult->fetch_assoc()['count'] : 0;
$recentMessages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");

require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p>Manage your projects and profile</p>
    </div>
</section>

<!-- Dashboard Content -->
<section class="dashboard-section">
    <div class="container">

        <!-- Welcome -->
        <div class="dashboard-welcome fade-in d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar mb-0" style="width: 58px; height: 58px; font-size: 1.5rem; flex-shrink: 0;">
                    <?php if (!empty($user['profile_image']) && file_exists('uploads/avatars/' . $user['profile_image'])): ?>
                        <img src="uploads/avatars/<?php echo sanitize($user['profile_image']); ?>" alt="<?php echo sanitize($userName); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="mb-1">Welcome back, <?php echo sanitize($userName); ?>! 👋</h2>
                    <p class="mb-0 text-muted">Here's an overview of your StudentHub activity.</p>
                </div>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="settings.php" class="btn btn-outline-primary btn-sm me-2">
                    <i class="bi bi-gear me-1"></i>Account Settings
                </a>
                <a href="add-project.php" class="btn btn-accent btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Add Project
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-md-3">
                <div class="dashboard-stat-card fade-in delay-1">
                    <div class="dashboard-stat-icon">
                        <i class="bi bi-folder"></i>
                    </div>
                    <div class="dashboard-stat-number"><?php echo $projectCount; ?></div>
                    <div class="dashboard-stat-label">My Projects</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="dashboard-stat-card fade-in delay-2">
                    <div class="dashboard-stat-icon" style="background: rgba(247,201,72,0.12); color: #f7c948;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="dashboard-stat-number"><?php echo $projectCount; ?></div>
                    <div class="dashboard-stat-label">Published</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="dashboard-stat-card fade-in delay-3">
                    <div class="dashboard-stat-icon" style="background: rgba(124,77,255,0.1); color: #7c4dff;">
                        <i class="bi bi-lightning"></i>
                    </div>
                    <div class="dashboard-stat-number"><?php echo $skillCount; ?></div>
                    <div class="dashboard-stat-label">Skills</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="dashboard-stat-card fade-in delay-4">
                    <div class="dashboard-stat-icon" style="background: rgba(0, 188, 212, 0.12); color: var(--accent);">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div class="dashboard-stat-number"><?php echo $messageCount; ?></div>
                    <div class="dashboard-stat-label">Inquiries</div>
                </div>
            </div>
        </div>

        <!-- My Projects Table -->
        <div class="dashboard-table-card fade-in">
            <div class="dashboard-table-header">
                <h4><i class="bi bi-folder2-open me-2 accent-text"></i>My Projects</h4>
                <a href="add-project.php" class="btn btn-accent btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Add New Project
                </a>
            </div>

            <?php if ($userProjects->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($project = $userProjects->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($project['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo sanitize(truncateText($project['description'], 60)); ?></small>
                                    </td>
                                    <td><span class="project-category-badge"><?php echo sanitize($project['category']); ?></span></td>
                                    <td class="text-muted"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                                    <td>
                                        <a href="project-details.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-secondary action-btn me-1" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary action-btn me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete-project.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-danger action-btn delete-btn" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-folder-plus d-block"></i>
                    <h4>You haven't added any projects yet.</h4>
                    <p>Start building your portfolio by adding your first project.</p>
                    <a href="add-project.php" class="btn btn-accent">
                        <i class="bi bi-plus-lg me-2"></i>Add Your First Project
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact Inquiries Card -->
        <div class="dashboard-table-card fade-in mt-4">
            <div class="dashboard-table-header">
                <div>
                    <h4><i class="bi bi-envelope-paper me-2 accent-text"></i>Contact Messages</h4>
                    <small class="text-muted">Messages submitted through the public Contact page (dispatched to <strong>ent2023048@tec.rjt.ac.lk</strong>)</small>
                </div>
                <a href="contact.php" class="btn btn-outline-accent btn-sm">
                    <i class="bi bi-send me-1"></i>Contact Page
                </a>
            </div>

            <?php if ($recentMessages && $recentMessages->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($msg = $recentMessages->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitize($msg['name']); ?></strong><br>
                                        <small class="text-muted"><a href="mailto:<?php echo sanitize($msg['email']); ?>"><?php echo sanitize($msg['email']); ?></a></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo sanitize($msg['subject']); ?></span>
                                    </td>
                                    <td>
                                        <small><?php echo sanitize(truncateText($msg['message'], 90)); ?></small>
                                    </td>
                                    <td class="text-muted text-nowrap">
                                        <?php echo date('M j, Y g:ia', strtotime($msg['created_at'])); ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo sanitize($msg['email']); ?>?subject=<?php echo rawurlencode('Re: ' . $msg['subject']); ?>" 
                                           class="btn btn-sm btn-accent me-1" title="Reply to Sender">
                                            <i class="bi bi-reply-fill me-1"></i>Reply
                                        </a>
                                        <?php if ($userId == 1 || ($user['username'] ?? '') === 'ayesh' || in_array($_SESSION['email'] ?? '', ['demo@studenthub.lk', 'ent2023048@tec.rjt.ac.lk'])): ?>
                                            <a href="delete-message.php?id=<?php echo $msg['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger delete-msg-btn" 
                                               title="Delete Message"
                                               onclick="return confirm('Are you sure you want to delete this message?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state py-4">
                    <i class="bi bi-inbox d-block"></i>
                    <h5>No messages yet</h5>
                    <p class="text-muted mb-0">Messages submitted via the Contact page will appear here and are emailed to ent2023048@tec.rjt.ac.lk.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
