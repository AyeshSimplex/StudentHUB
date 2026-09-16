<?php
/**
 * Register Page — StudentHub
 * User registration with JS + PHP validation and password hashing.
 */
$pageTitle = 'Register';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$old = ['username' => '', 'full_name' => '', 'email' => '', 'faculty' => '', 'skills' => ''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $faculty = trim($_POST['faculty'] ?? '');
    $skills = trim($_POST['skills'] ?? '');

    // Save old values
    $old = compact('username', 'email', 'faculty', 'skills');
    $old['full_name'] = $fullName;

    // Server-side validation
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers and underscores.';
    }
    if (strlen($fullName) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Security: Block reserved admin credentials
    $restrictedEmails = ['admin@studenthub.lk', 'demo@studenthub.lk'];
    $restrictedUsernames = ['admin', 'ayesh'];

    if (in_array(strtolower($email), $restrictedEmails)) {
        $errors[] = 'This email address is restricted and cannot be registered.';
    }
    if (in_array(strtolower($username), $restrictedUsernames)) {
        $errors[] = 'This username is restricted and cannot be registered.';
    }

    // Check if username or email already exists
    if (empty($errors)) {
        ensureUserAvatarColumn($conn);
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = 'An account with this username or email already exists.';
        }
        $stmt->close();
    }

    // Handle optional profile photo upload
    $profileImage = null;
    if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $avatarUpload = uploadAvatar($_FILES['profile_image']);
        if ($avatarUpload['success']) {
            $profileImage = $avatarUpload['filename'];
        } else {
            $errors[] = $avatarUpload['error'];
        }
    }

    // If no errors, create account
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, faculty, skills, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $fullName, $email, $hashedPassword, $faculty, $skills, $profileImage);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Registration successful! You can now log in with your credentials.';
            $stmt->close();
            header('Location: login.php');
            exit();
        } else {
            // Delete uploaded avatar if insertion failed
            if ($profileImage) {
                deleteAvatarFile($profileImage);
            }
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<!-- Register Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-card fade-in">
            <div class="text-center mb-3">
                <i class="bi bi-mortarboard-fill" style="font-size: 2.5rem; color: var(--accent);"></i>
            </div>
            <h2 class="auth-title">Create Your Account</h2>
            <p class="auth-subtitle">Join StudentHub and start showcasing your projects</p>

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

            <form id="registerForm" method="POST" action="register.php" enctype="multipart/form-data" novalidate>
                <!-- Profile Photo (Optional) -->
                <div class="text-center mb-4">
                    <div class="avatar-upload-wrapper position-relative d-inline-block">
                        <div class="avatar-preview-circle mx-auto" id="avatarPreviewContainer">
                            <i class="bi bi-person-fill" id="avatarPlaceholderIcon"></i>
                            <img src="" alt="Profile Preview" id="avatarPreviewImg" class="d-none">
                        </div>
                        <label for="profile_image" class="avatar-upload-badge" title="Choose profile picture">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                        <input type="file" class="d-none" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="small fw-semibold mt-2">Profile Photo <span class="badge bg-light text-secondary border">Optional</span></div>
                    <div class="text-muted" style="font-size: 0.78rem;">Click camera icon to upload (JPG, PNG or WEBP, max 5MB)</div>
                    <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none d-none p-0 mt-1" id="removeAvatarBtn">
                        <i class="bi bi-x-circle me-1"></i>Remove chosen photo
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?php echo sanitize($old['username']); ?>" placeholder="e.g. ayesh" required>
                            <div class="form-error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?php echo sanitize($old['full_name']); ?>" placeholder="e.g. Ayesh Rathnayaka" required>
                            <div class="form-error"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo sanitize($old['email']); ?>" placeholder="e.g. ayesh@studenthub.lk" required>
                    <div class="form-error"></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Minimum 6 characters" required>
                            <div class="form-error"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Repeat password" required>
                            <div class="form-error"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="faculty" class="form-label">Faculty</label>
                    <select class="form-select" id="faculty" name="faculty">
                        <option value="">Select your faculty</option>
                        <option value="Faculty of Technology" <?php echo $old['faculty'] === 'Faculty of Technology' ? 'selected' : ''; ?>>Faculty of Technology</option>
                        <option value="Faculty of Applied Sciences" <?php echo $old['faculty'] === 'Faculty of Applied Sciences' ? 'selected' : ''; ?>>Faculty of Applied Sciences</option>
                        <option value="Faculty of Agriculture" <?php echo $old['faculty'] === 'Faculty of Agriculture' ? 'selected' : ''; ?>>Faculty of Agriculture</option>
                        <option value="Faculty of Management Studies" <?php echo $old['faculty'] === 'Faculty of Management Studies' ? 'selected' : ''; ?>>Faculty of Management Studies</option>
                        <option value="Faculty of Social Sciences" <?php echo $old['faculty'] === 'Faculty of Social Sciences' ? 'selected' : ''; ?>>Faculty of Social Sciences</option>
                        <option value="Faculty of Medicine" <?php echo $old['faculty'] === 'Faculty of Medicine' ? 'selected' : ''; ?>>Faculty of Medicine</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="skills" class="form-label">Skills</label>
                    <input type="text" class="form-control" id="skills" name="skills"
                           value="<?php echo sanitize($old['skills']); ?>"
                           placeholder="e.g. PHP, JavaScript, Python, HTML, CSS">
                    <small class="text-muted">Separate skills with commas</small>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Log in here</a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('profile_image');
    const previewImg = document.getElementById('avatarPreviewImg');
    const placeholderIcon = document.getElementById('avatarPlaceholderIcon');
    const removeBtn = document.getElementById('removeAvatarBtn');

    if (avatarInput && previewImg) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit. Please choose a smaller photo.');
                this.value = '';
                return;
            }

            const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                alert('Please choose a valid JPG, PNG, or WEBP image.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
                if (placeholderIcon) placeholderIcon.classList.add('d-none');
                if (removeBtn) removeBtn.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                avatarInput.value = '';
                previewImg.src = '';
                previewImg.classList.add('d-none');
                if (placeholderIcon) placeholderIcon.classList.remove('d-none');
                removeBtn.classList.add('d-none');
            });
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
