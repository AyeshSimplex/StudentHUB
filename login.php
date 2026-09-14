<?php
/**
 * Login Page — StudentHub
 * User authentication with password_verify() and sessions.
 */
$pageTitle = 'Login';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$oldEmail = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) === 0) {
        $errors[] = 'Please enter your password.';
    }

    // Authenticate user
    if (empty($errors)) {
        ensureUserAvatarColumn($conn);
        $stmt = $conn->prepare("SELECT id, username, full_name, email, password, faculty, skills, profile_image FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful — start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_image'] = $user['profile_image'] ?? null;

            $_SESSION['success'] = 'Welcome back, ' . $user['full_name'] . '!';
            header('Location: dashboard.php');
            exit();
        } else {
            // Generic error message to prevent account enumeration
            $errors[] = 'Invalid email or password. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<!-- Login Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in" style="max-width: 450px;">
            <div class="text-center mb-3">
                <i class="bi bi-mortarboard-fill" style="font-size: 2.5rem; color: var(--accent);"></i>
            </div>
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Log in to your StudentHub account</p>

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

            <form id="loginForm" method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo sanitize($oldEmail); ?>" placeholder="Enter your email" required>
                    <div class="form-error"></div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password <span class="text-danger">*</span></label>
                        <a href="forgot-password.php" class="small text-muted text-decoration-none">
                            <i class="bi bi-question-circle me-1"></i>Forgot password?
                        </a>
                    </div>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-error"></div>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
