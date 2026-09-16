<?php
/**
 * Forgot Password — StudentHub
 * Allows users to request a password recovery link via email.
 */
$pageTitle = 'Forgot Password';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$successMessage = '';
$previewResetLink = '';
$submittedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $submittedEmail = $email;

    if (empty($email)) {
        $errors[] = 'Please enter your registered email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        // Look up user
        $stmt = $conn->prepare("SELECT id, username, full_name, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Generate secure token (64 hex characters)
            $token = bin2hex(random_bytes(32));

            // Store token in database with 1-hour expiration
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $updateStmt->bind_param("si", $token, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();

            // Construct reset link
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $resetLink = "$protocol://$host$dir/reset-password.php?token=" . urlencode($token);

            // Send password reset email
            sendPasswordResetEmail($user['full_name'], $user['email'], $resetLink);

            $previewResetLink = $resetLink;
        }

        // Generic message for security (prevents account enumeration)
        $successMessage = 'If an account exists for ' . htmlspecialchars($email) . ', a password reset link has been dispatched. Please check your inbox (and spam folder).';
    }
}

require_once 'includes/header.php';
?>

<!-- Forgot Password Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in" style="max-width: 480px;">
            <div class="text-center mb-3">
                <div class="dashboard-stat-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; background: rgba(0,188,212,0.12); color: var(--accent);">
                    <i class="bi bi-shield-lock"></i>
                </div>
            </div>
            <h2 class="auth-title">Recover Account</h2>
            <p class="auth-subtitle">Enter your registered email and we'll send you a password reset link.</p>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo sanitize($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Success -->
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <!-- Local Dev / Viva Practical Helper Link -->
                <?php if (!empty($previewResetLink)): ?>
                    <div class="alert alert-info border" role="alert">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-link-45deg me-1 fs-5"></i>
                            <strong>Localhost Quick Reset Link:</strong>
                        </div>
                        <p class="small text-muted mb-2">For local testing and viva demonstration, click directly below:</p>
                        <a href="<?php echo htmlspecialchars($previewResetLink); ?>" class="btn btn-sm btn-accent w-100 text-truncate">
                            <i class="bi bi-arrow-right-circle me-1"></i>Open Password Reset Page
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php" novalidate>
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo sanitize($submittedEmail); ?>"
                               placeholder="e.g. student@studenthub.lk" required>
                    </div>
                    <div class="form-text">We will send a secure one-time link valid for 1 hour.</div>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">
                    <i class="bi bi-envelope-check me-2"></i>Send Reset Link
                </button>
            </form>

            <div class="auth-footer mt-4">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
