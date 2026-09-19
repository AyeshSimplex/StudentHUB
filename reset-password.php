<?php
/**
 * Reset Password Page — StudentHub
 * Verifies reset token and updates user password.
 */
$pageTitle = 'Reset Password';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$isValidToken = false;
$user = null;

if (empty($token)) {
    $errors[] = 'Password reset token is missing. Please use the link sent to your email.';
} else {
    // Validate token and expiration against MySQL
    $stmt = $conn->prepare("SELECT id, username, full_name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        $isValidToken = true;
    } else {
        $errors[] = 'This password reset link is invalid or has expired. Please request a new one.';
    }
}

// Process new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    requireCsrfToken('reset-password.php?token=' . urlencode($token));
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword)) {
        $errors[] = 'Please enter your new password.';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    }

    if (empty($confirmPassword)) {
        $errors[] = 'Please confirm your new password.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Hash new password and clear token
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $user['id']);

        if ($updateStmt->execute()) {
            $_SESSION['success'] = 'Password reset successful! You can now log in with your new password.';
            $updateStmt->close();
            header('Location: login.php');
            exit();
        } else {
            $errors[] = 'Failed to reset password. Please try again.';
        }
        $updateStmt->close();
    }
}

require_once 'includes/header.php';
?>

<!-- Reset Password Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in" style="max-width: 480px;">
            <div class="text-center mb-3">
                <div class="dashboard-stat-icon mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; background: rgba(0,188,212,0.12); color: var(--accent);">
                    <i class="bi bi-key-fill"></i>
                </div>
            </div>
            <h2 class="auth-title">Create New Password</h2>

            <?php if ($isValidToken && $user): ?>
                <p class="auth-subtitle">
                    Resetting password for <strong><?php echo sanitize($user['full_name']); ?></strong> (<em><?php echo sanitize($user['email']); ?></em>)
                </p>
            <?php else: ?>
                <p class="auth-subtitle">Verify your recovery token</p>
            <?php endif; ?>

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

                <?php if (!$isValidToken): ?>
                    <div class="text-center mt-3">
                        <a href="forgot-password.php" class="btn btn-accent btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Request New Reset Link
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($isValidToken): ?>
                <form method="POST" action="reset-password.php" novalidate id="resetPasswordForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                   placeholder="Enter new password (min. 6 characters)" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters. Use letters and numbers.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-check2-circle"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Re-enter your new password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-accent w-100 py-2">
                        <i class="bi bi-check2-circle me-2"></i>Save New Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer mt-4">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
