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

// Ensure tables exist
ensureMessagesUserIdColumn($conn);
ensureMessageRepliesTable($conn);

// Get inquiries count - only current user's messages
$msgCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE user_id = ?");
$msgCountStmt->bind_param("i", $userId);
$msgCountStmt->execute();
$messageCount = $msgCountStmt->get_result()->fetch_assoc()['count'];
$msgCountStmt->close();

$adminCheck = isAdmin();

// Get recent contact messages (Admins see all, users see their own)
if ($adminCheck) {
    $recentMessages = $conn->query("SELECT m.*, u.username as sender_username FROM messages m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 10");
} else {
    $stmt = $conn->prepare("SELECT m.*, u.username as sender_username FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE m.user_id = ? ORDER BY m.created_at DESC LIMIT 10");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $recentMessages = $stmt->get_result();
    $stmt->close();
}

// Get replies for messages
$allReplies = [];
$repliesResult = $conn->query("SELECT r.*, u.full_name, u.username FROM message_replies r JOIN users u ON r.user_id = u.id ORDER BY r.created_at ASC");
if ($repliesResult) {
    while ($reply = $repliesResult->fetch_assoc()) {
        $allReplies[$reply['message_id']][] = $reply;
    }
}


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
        <div class="dashboard-welcome fade-in d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div class="d-flex align-items-center gap-3 flex-grow-1 flex-md-grow-0">
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
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-md-3">
                <a href="#my-projects" class="text-decoration-none">
                    <div class="dashboard-stat-card fade-in delay-1" style="cursor: pointer;">
                        <div class="dashboard-stat-icon">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div class="dashboard-stat-number"><?php echo $projectCount; ?></div>
                        <div class="dashboard-stat-label">My Projects</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="profile.php#projects" class="text-decoration-none">
                    <div class="dashboard-stat-card fade-in delay-2" style="cursor: pointer;">
                        <div class="dashboard-stat-icon" style="background: rgba(247,201,72,0.12); color: #f7c948;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="dashboard-stat-number"><?php echo $projectCount; ?></div>
                        <div class="dashboard-stat-label">Published</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="settings.php?tab=profile" class="text-decoration-none">
                    <div class="dashboard-stat-card fade-in delay-3" style="cursor: pointer;">
                        <div class="dashboard-stat-icon" style="background: rgba(124,77,255,0.1); color: #7c4dff;">
                            <i class="bi bi-lightning"></i>
                        </div>
                        <div class="dashboard-stat-number"><?php echo $skillCount; ?></div>
                        <div class="dashboard-stat-label">Skills</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="#public-messages" class="text-decoration-none">
                    <div class="dashboard-stat-card fade-in delay-4" style="cursor: pointer;">
                        <div class="dashboard-stat-icon" style="background: rgba(0, 188, 212, 0.12); color: var(--accent);">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="dashboard-stat-number"><?php echo $messageCount; ?></div>
                        <div class="dashboard-stat-label">Inquiries</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- My Projects Table -->
        <div class="dashboard-table-card fade-in" id="my-projects">
            <div class="dashboard-table-header">
                <h4><i class="bi bi-folder2-open me-2 accent-text"></i>My Projects</h4>
                <a href="add-project.php" class="btn btn-accent btn-sm d-inline-flex align-items-center justify-content-center">
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

        <!-- Contact Messages Card -->
        <div class="dashboard-table-card fade-in mt-4" id="public-messages">
            <div class="dashboard-table-header">
                <div>
                    <h4><i class="bi bi-envelope-paper me-2 accent-text"></i>Public Messages</h4>
                    <small class="text-muted">Messages submitted through the Contact page</small>
                </div>
                <a href="contact.php" class="btn btn-outline-accent btn-sm d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-send me-1"></i>Contact Page
                </a>
            </div>

            <?php if ($recentMessages && $recentMessages->num_rows > 0): ?>
                <div class="messages-list">
                    <?php while ($msg = $recentMessages->fetch_assoc()): ?>
                        <div class="message-card" id="message-<?php echo $msg['id']; ?>">
                            <div class="message-header">
                                <div class="message-sender-info">
                                    <div class="message-avatar">
                                        <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong class="message-sender-name"><?php echo sanitize($msg['name']); ?></strong>
                                        <?php if ($adminCheck): ?>
                                            <br><small class="text-muted"><?php echo sanitize($msg['email']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="message-meta">
                                    <span class="badge bg-light text-dark border me-2"><?php echo sanitize($msg['subject']); ?></span>
                                    <span class="text-muted small"><?php echo date('M j, Y g:ia', strtotime($msg['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="message-body">
                                <p class="mb-0"><?php echo sanitize($msg['message']); ?></p>
                            </div>
                            <div class="message-actions">
                                <!-- Reply button for any logged-in user -->
                                <?php if (isLoggedIn()): ?>
                                    <button type="button" class="btn btn-sm btn-accent reply-toggle-btn"
                                            onclick="toggleReplyForm(<?php echo $msg['id']; ?>)">
                                        <i class="bi bi-reply-fill me-1"></i>Reply
                                    </button>
                                <?php endif; ?>

                                <!-- Edit button - only for own messages -->
                                <?php if (isset($msg['user_id']) && $msg['user_id'] == $userId): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="toggleEditForm(<?php echo $msg['id']; ?>)">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>
                                <?php endif; ?>

                                <!-- Delete button - own messages OR admin -->
                                <?php if ((isset($msg['user_id']) && $msg['user_id'] == $userId) || $adminCheck): ?>
                                    <a href="delete-message.php?id=<?php echo $msg['id']; ?>"
                                       class="btn btn-sm btn-outline-danger delete-msg-btn d-inline-flex align-items-center justify-content-center"
                                       onclick="return confirm('Are you sure you want to delete this message?');">
                                        <i class="bi bi-trash m-0"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Edit Form (hidden by default) -->
                            <?php if (isset($msg['user_id']) && $msg['user_id'] == $userId): ?>
                                <div class="edit-message-form" id="editForm-<?php echo $msg['id']; ?>" style="display: none;">
                                    <form method="POST" action="edit-message.php">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <div class="mb-2">
                                            <input type="text" name="subject" class="form-control form-control-sm"
                                                   value="<?php echo sanitize($msg['subject']); ?>" placeholder="Subject" required>
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="message" class="form-control form-control-sm" rows="3"
                                                      placeholder="Message" required><?php echo sanitize($msg['message']); ?></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-accent">
                                                <i class="bi bi-check-lg me-1"></i>Save
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="toggleEditForm(<?php echo $msg['id']; ?>)">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Reply Form (hidden by default) -->
                            <?php if (isLoggedIn()): ?>
                                <div class="reply-form-container" id="replyForm-<?php echo $msg['id']; ?>" style="display: none;">
                                    <form method="POST" action="reply-message.php">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <div class="d-flex gap-2 align-items-start">
                                            <textarea name="reply_text" class="form-control form-control-sm" rows="2"
                                                      placeholder="Write your reply..." required></textarea>
                                            <button type="submit" class="btn btn-sm btn-accent" style="white-space: nowrap;">
                                                <i class="bi bi-send me-1"></i>Send
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Replies -->
                            <?php if (!empty($allReplies[$msg['id']])): ?>
                                <div class="message-replies">
                                    <div class="replies-header">
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        <?php echo count($allReplies[$msg['id']]); ?>
                                        Repl<?php echo count($allReplies[$msg['id']]) === 1 ? 'y' : 'ies'; ?>
                                    </div>
                                    <?php foreach ($allReplies[$msg['id']] as $reply): ?>
                                        <div class="reply-item">
                                            <div class="reply-header">
                                                <div class="reply-avatar">
                                                    <?php
                                                    // Admin replies show as StudentHub Team
                                                    $isReplyAdmin = ($reply['user_id'] == 1 || $reply['username'] === 'ayesh');
                                                    $replyDisplayName = $isReplyAdmin ? 'StudentHub Team' : $reply['full_name'];
                                                    echo strtoupper(substr($replyDisplayName, 0, 1));
                                                    ?>
                                                </div>
                                                <strong>
                                                    <?php echo sanitize($replyDisplayName); ?>
                                                    <?php if ($isReplyAdmin): ?>
                                                        <span class="badge bg-accent-subtle text-accent ms-1" style="font-size: 0.65rem;">Team</span>
                                                    <?php endif; ?>
                                                </strong>
                                                <div class="d-flex align-items-center gap-2 ms-auto">
                                                    <span class="text-muted small"><?php echo getTimeAgo($reply['created_at']); ?></span>
                                                    <?php if ($_SESSION['user_id'] == $reply['user_id'] || isAdmin()): ?>
                                                        <a href="delete-reply.php?id=<?php echo $reply['id']; ?>"
                                                           class="btn btn-sm btn-outline-danger border-0"
                                                           style="padding: 2px 6px; font-size: 0.75rem;"
                                                           onclick="return confirm('Delete this reply?');"
                                                           title="Delete Reply">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="reply-body">
                                                <?php echo sanitize($reply['reply_text']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state py-4">
                    <i class="bi bi-inbox d-block"></i>
                    <h5>No messages yet</h5>
                    <p class="text-muted mb-0">Messages submitted via the Contact page will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
function toggleReplyForm(msgId) {
    var form = document.getElementById('replyForm-' + msgId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}

function toggleEditForm(msgId) {
    var form = document.getElementById('editForm-' + msgId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
