<?php
/**
 * Account Settings — StudentHub
 * Allows users to change email, password, and update profile details.
 * Protected page — requires authentication.
 */
$pageTitle = 'Account Settings';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch current user record from database
ensureUserAvatarColumn($conn);
$stmt = $conn->prepare("SELECT id, username, full_name, email, password, faculty, skills, profile_image, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentUser) {
    $_SESSION['error'] = 'User account not found.';
    header('Location: logout.php');
    exit();
}

$emailErrors = [];
$passwordErrors = [];
$profileErrors = [];
$avatarErrors = [];
$deleteErrors = [];
$activeTab = 'avatar'; // Default to avatar/profile tab if not specified

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // ===================================
    // 1. CHANGE EMAIL
    // ===================================
    if ($action === 'change_email') {
        $activeTab = 'email';
        $newEmail = isset($_POST['new_email']) ? trim($_POST['new_email']) : '';
        $confirmEmail = isset($_POST['confirm_email']) ? trim($_POST['confirm_email']) : '';
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';

        // Validation
        if (empty($newEmail)) {
            $emailErrors[] = 'Please enter a new email address.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $emailErrors[] = 'Please enter a valid email address.';
        } elseif (strtolower($newEmail) === strtolower($currentUser['email'])) {
            $emailErrors[] = 'New email address must be different from your current email.';
        }

        if (empty($confirmEmail)) {
            $emailErrors[] = 'Please confirm your new email address.';
        } elseif (strtolower($newEmail) !== strtolower($confirmEmail)) {
            $emailErrors[] = 'Email confirmation does not match.';
        }

        if (empty($currentPassword)) {
            $emailErrors[] = 'Please enter your current password to authorize this change.';
        } elseif (!password_verify($currentPassword, $currentUser['password'])) {
            $emailErrors[] = 'Current password is incorrect.';
        }

        // Check for duplicate email in database
        if (empty($emailErrors)) {
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkStmt->bind_param("si", $newEmail, $userId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $emailErrors[] = 'This email address is already in use by another account.';
            }
            $checkStmt->close();
        }

        // Update email
        if (empty($emailErrors)) {
            $updateStmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newEmail, $userId);
            if ($updateStmt->execute()) {
                $_SESSION['email'] = $newEmail;
                $currentUser['email'] = $newEmail;
                $_SESSION['success'] = 'Your email address has been updated successfully to ' . htmlspecialchars($newEmail) . '.';
                header('Location: settings.php?tab=email');
                exit();
            } else {
                $emailErrors[] = 'Failed to update email. Please try again later.';
            }
            $updateStmt->close();
        }
    }

    // ===================================
    // 2. CHANGE PASSWORD
    // ===================================
    if ($action === 'change_password') {
        $activeTab = 'password';
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validation
        if (empty($currentPassword)) {
            $passwordErrors[] = 'Please enter your current password.';
        } elseif (!password_verify($currentPassword, $currentUser['password'])) {
            $passwordErrors[] = 'Current password is incorrect.';
        }

        if (empty($newPassword)) {
            $passwordErrors[] = 'Please enter your new password.';
        } elseif (strlen($newPassword) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters long.';
        } elseif ($newPassword === $currentPassword) {
            $passwordErrors[] = 'New password cannot be the same as your current password.';
        }

        if (empty($confirmPassword)) {
            $passwordErrors[] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'New password and confirmation do not match.';
        }

        // Update password
        if (empty($passwordErrors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            if ($updateStmt->execute()) {
                $_SESSION['success'] = 'Password changed successfully! You can now use your new password.';
                header('Location: settings.php?tab=password');
                exit();
            } else {
                $passwordErrors[] = 'Failed to change password. Please try again.';
            }
            $updateStmt->close();
        }
    }

    // ===================================
    // 3. UPDATE PROFILE DETAILS
    // ===================================
    if ($action === 'update_profile') {
        $activeTab = 'profile';
        $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $faculty = isset($_POST['faculty']) ? trim($_POST['faculty']) : '';
        $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';

        if (empty($fullName)) {
            $profileErrors[] = 'Full name cannot be empty.';
        }

        if (empty($profileErrors)) {
            $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, faculty = ?, skills = ? WHERE id = ?");
            $updateStmt->bind_param("sssi", $fullName, $faculty, $skills, $userId);
            if ($updateStmt->execute()) {
                $_SESSION['full_name'] = $fullName;
                $currentUser['full_name'] = $fullName;
                $currentUser['faculty'] = $faculty;
                $currentUser['skills'] = $skills;
                $_SESSION['success'] = 'Profile details updated successfully!';
                header('Location: settings.php?tab=profile');
                exit();
            } else {
                $profileErrors[] = 'Failed to update profile details. Please try again.';
            }
            $updateStmt->close();
        }
    }

    // ===================================
    // 4. UPDATE AVATAR
    // ===================================
    if ($action === 'update_avatar') {
        $activeTab = 'avatar';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $avatarUpload = uploadAvatar($_FILES['profile_image']);
            if ($avatarUpload['success']) {
                $newAvatar = $avatarUpload['filename'];
                // Delete previous avatar file if exists
                if (!empty($currentUser['profile_image'])) {
                    deleteAvatarFile($currentUser['profile_image']);
                }

                $updateStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newAvatar, $userId);
                if ($updateStmt->execute()) {
                    $currentUser['profile_image'] = $newAvatar;
                    $_SESSION['profile_image'] = $newAvatar;
                    $_SESSION['success'] = 'Profile photo updated successfully!';
                    header('Location: settings.php?tab=avatar');
                    exit();
                } else {
                    deleteAvatarFile($newAvatar);
                    $avatarErrors[] = 'Failed to update database. Please try again.';
                }
                $updateStmt->close();
            } else {
                $avatarErrors[] = $avatarUpload['error'];
            }
        } else {
            $avatarErrors[] = 'Please select an image file to upload.';
        }
    }

    // ===================================
    // 5. DELETE AVATAR
    // ===================================
    if ($action === 'delete_avatar') {
        $activeTab = 'avatar';
        if (!empty($currentUser['profile_image'])) {
            deleteAvatarFile($currentUser['profile_image']);
            $updateStmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
            $updateStmt->bind_param("i", $userId);
            if ($updateStmt->execute()) {
                $currentUser['profile_image'] = null;
                $_SESSION['profile_image'] = null;
                $_SESSION['success'] = 'Profile photo removed successfully.';
                header('Location: settings.php?tab=avatar');
                exit();
            } else {
                $avatarErrors[] = 'Failed to remove photo from account.';
            }
            $updateStmt->close();
        } else {
            $avatarErrors[] = 'No custom profile photo is currently set.';
        }
    }

    // ===================================
    // 6. DELETE ACCOUNT
    // ===================================
    if ($action === 'delete_account') {
        $activeTab = 'delete';
        $currentPassword = $_POST['current_password'] ?? '';
        $confirmText = trim($_POST['confirm_delete_text'] ?? '');
        $confirmCheck = isset($_POST['confirm_delete_checkbox']);

        if (empty($currentPassword)) {
            $deleteErrors[] = 'Please enter your current password to authorize account deletion.';
        } elseif (!password_verify($currentPassword, $currentUser['password'])) {
            $deleteErrors[] = 'Incorrect password. Account deletion was not authorized.';
        }

        if (strtoupper($confirmText) !== 'DELETE') {
            $deleteErrors[] = 'Please type DELETE in the confirmation box to proceed.';
        }

        if (!$confirmCheck) {
            $deleteErrors[] = 'You must confirm that you understand this action is permanent and irreversible.';
        }

        if (empty($deleteErrors)) {
            // 1. Delete all project cover images from disk
            $projStmt = $conn->prepare("SELECT id, image FROM projects WHERE user_id = ?");
            $projStmt->bind_param("i", $userId);
            $projStmt->execute();
            $projRes = $projStmt->get_result();
            $projectIds = [];
            while ($p = $projRes->fetch_assoc()) {
                $projectIds[] = $p['id'];
                if (!empty($p['image'])) {
                    $covPath = 'uploads/projects/' . $p['image'];
                    if (file_exists($covPath) && is_file($covPath)) {
                        @unlink($covPath);
                    }
                }
            }
            $projStmt->close();

            // 2. Delete all gallery images for these projects from disk
            if (!empty($projectIds)) {
                $inList = implode(',', array_map('intval', $projectIds));
                $galRes = $conn->query("SELECT image FROM project_images WHERE project_id IN ($inList)");
                if ($galRes) {
                    while ($g = $galRes->fetch_assoc()) {
                        if (!empty($g['image'])) {
                            $galPath = 'uploads/projects/' . $g['image'];
                            if (file_exists($galPath) && is_file($galPath)) {
                                @unlink($galPath);
                            }
                        }
                    }
                }
            }

            // 3. Delete user avatar from disk
            if (!empty($currentUser['profile_image'])) {
                deleteAvatarFile($currentUser['profile_image']);
            }

            // 4. Delete user from database (Foreign Key ON DELETE CASCADE will remove projects and gallery rows)
            $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delStmt->bind_param("i", $userId);
            if ($delStmt->execute()) {
                $delStmt->close();

                // Clear session and destroy
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();

                session_start();
                $_SESSION['success'] = 'Your account and all associated projects and images have been permanently deleted.';
                header('Location: index.php');
                exit();
            } else {
                $deleteErrors[] = 'Failed to delete account. Please try again.';
                $delStmt->close();
            }
        }
    }
}

// Preserve active tab from GET query if present
if (isset($_GET['tab']) && in_array($_GET['tab'], ['avatar', 'email', 'password', 'profile', 'delete'])) {
    $activeTab = $_GET['tab'];
}

require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-gear-fill me-2"></i>Account Settings</h1>
        <p>Update your login credentials, password, and profile information</p>
    </div>
</section>

<!-- Settings Content -->
<section class="section-padding" style="background: var(--bg-alt);">
    <div class="container">
        <div class="row g-4 justify-content-center">

            <!-- Left Sidebar / User Snapshot Card -->
            <div class="col-lg-4 col-md-5">
                <div class="auth-card fade-in" style="max-width: none;">
                    <div class="text-center mb-4">
                        <div class="profile-avatar mx-auto mb-3 position-relative" style="width: 88px; height: 88px; font-size: 2.2rem;">
                            <?php if (!empty($currentUser['profile_image']) && file_exists('uploads/avatars/' . $currentUser['profile_image'])): ?>
                                <img src="uploads/avatars/<?php echo sanitize($currentUser['profile_image']); ?>" alt="<?php echo sanitize($currentUser['full_name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                            <button type="button" class="avatar-edit-overlay-btn" title="Change profile photo" onclick="document.getElementById('avatar-tab').click();">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                        </div>
                        <h4 class="mb-1"><?php echo sanitize($currentUser['full_name']); ?></h4>
                        <p class="text-muted mb-2">@<?php echo sanitize($currentUser['username']); ?></p>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            <i class="bi bi-envelope me-1"></i><?php echo sanitize($currentUser['email']); ?>
                        </span>
                    </div>

                    <hr class="my-3">

                    <ul class="list-unstyled mb-4 small text-muted">
                        <li class="mb-2">
                            <i class="bi bi-building me-2 text-primary"></i>
                            <strong>Faculty:</strong> <?php echo sanitize($currentUser['faculty'] ?: 'Not specified'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-calendar3 me-2 text-primary"></i>
                            <strong>Member since:</strong> <?php echo date('M d, Y', strtotime($currentUser['created_at'])); ?>
                        </li>
                    </ul>

                    <!-- Navigation Pills -->
                    <div class="nav flex-column nav-pills settings-nav" id="settings-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link text-start mb-2 py-3 px-3 <?php echo $activeTab === 'avatar' ? 'active' : ''; ?>" 
                                id="avatar-tab" data-bs-toggle="pill" data-bs-target="#tab-avatar" type="button" role="tab">
                            <i class="bi bi-camera-fill me-2"></i>Profile Photo
                        </button>
                        <button class="nav-link text-start mb-2 py-3 px-3 <?php echo $activeTab === 'email' ? 'active' : ''; ?>" 
                                id="email-tab" data-bs-toggle="pill" data-bs-target="#tab-email" type="button" role="tab">
                            <i class="bi bi-envelope-at me-2"></i>Change Email Address
                        </button>
                        <button class="nav-link text-start mb-2 py-3 px-3 <?php echo $activeTab === 'password' ? 'active' : ''; ?>" 
                                id="password-tab" data-bs-toggle="pill" data-bs-target="#tab-password" type="button" role="tab">
                            <i class="bi bi-shield-lock me-2"></i>Change Password
                        </button>
                        <button class="nav-link text-start py-3 px-3 <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" 
                                id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">
                            <i class="bi bi-person-lines-fill me-2"></i>Edit Profile Details
                        </button>
                        <hr class="my-2 border-secondary opacity-25">
                        <button class="nav-link text-start text-danger py-3 px-3 <?php echo $activeTab === 'delete' ? 'active' : ''; ?>" 
                                id="delete-tab" data-bs-toggle="pill" data-bs-target="#tab-delete" type="button" role="tab">
                            <i class="bi bi-trash3-fill me-2"></i>Delete Account
                        </button>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <a href="dashboard.php" class="btn btn-outline-secondary w-100 btn-sm mb-2">
                            <i class="bi bi-speedometer2 me-1"></i>Back to Dashboard
                        </a>
                        <a href="profile.php?id=<?php echo $userId; ?>" class="btn btn-outline-accent w-100 btn-sm">
                            <i class="bi bi-eye me-1"></i>View Public Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Forms Card -->
            <div class="col-lg-8 col-md-7">
                <div class="auth-card fade-in" style="max-width: none;">

                    <div class="tab-content" id="settings-tabContent">

                        <!-- ===================================
                             TAB: PROFILE PHOTO
                             =================================== -->
                        <div class="tab-pane fade <?php echo $activeTab === 'avatar' ? 'show active' : ''; ?>" 
                             id="tab-avatar" role="tabpanel" aria-labelledby="avatar-tab">
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="dashboard-stat-icon me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: rgba(0,188,212,0.1); color: var(--accent);">
                                    <i class="bi bi-camera-fill"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0">Profile Photo</h4>
                                    <small class="text-muted">Upload or update your profile picture visible across StudentHub</small>
                                </div>
                            </div>

                            <!-- Alert Errors -->
                            <?php if (!empty($avatarErrors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($avatarErrors as $err): ?>
                                            <li><?php echo sanitize($err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Current Avatar Display Box -->
                            <div class="card bg-light border-0 p-4 mb-4 rounded-3">
                                <div class="d-flex flex-column flex-sm-row align-items-center gap-4">
                                    <div class="profile-avatar mb-0 flex-shrink-0" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                        <?php if (!empty($currentUser['profile_image']) && file_exists('uploads/avatars/' . $currentUser['profile_image'])): ?>
                                            <img src="uploads/avatars/<?php echo sanitize($currentUser['profile_image']); ?>" alt="Current Avatar" id="currentAvatarPreview">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center text-sm-start flex-grow-1">
                                        <h5 class="mb-1"><?php echo sanitize($currentUser['full_name']); ?></h5>
                                        <p class="text-muted small mb-2">
                                            Status: <?php echo !empty($currentUser['profile_image']) ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Custom photo active</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Default initial</span>'; ?>
                                        </p>
                                        <?php if (!empty($currentUser['profile_image']) && file_exists('uploads/avatars/' . $currentUser['profile_image'])): ?>
                                            <form action="settings.php" method="POST" onsubmit="return confirm('Are you sure you want to remove your profile photo?');" class="d-inline">
                                                <input type="hidden" name="action" value="delete_avatar">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash3 me-1"></i>Remove Current Photo
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <form action="settings.php" method="POST" enctype="multipart/form-data" id="changeAvatarForm">
                                <input type="hidden" name="action" value="update_avatar">

                                <div class="mb-4">
                                    <label for="settings_avatar_input" class="form-label fw-semibold">Select New Profile Photo</label>
                                    
                                    <div class="avatar-dropzone p-4 text-center rounded-3 border border-2 border-dashed" id="settingsDropZone">
                                        <div id="settingsNewPreviewWrapper" class="d-none mb-3">
                                            <img src="" alt="New Photo Preview" id="settingsNewPreviewImg" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid var(--accent);">
                                            <div class="small text-success fw-semibold mt-2"><i class="bi bi-check2-circle me-1"></i>Photo ready to save!</div>
                                        </div>

                                        <i class="bi bi-cloud-arrow-up fs-1 text-muted d-block mb-2" id="settingsUploadIcon"></i>
                                        <p class="mb-1 fw-semibold small">Click below to choose an image</p>
                                        <p class="text-muted small mb-3">Supports JPG, JPEG, PNG, and WEBP formats up to 5MB.</p>
                                        
                                        <input type="file" class="d-none" id="settings_avatar_input" name="profile_image" accept="image/jpeg,image/png,image/webp" required>
                                        
                                        <button type="button" class="btn btn-outline-accent btn-sm px-3" onclick="document.getElementById('settings_avatar_input').click();">
                                            <i class="bi bi-image me-1"></i>Choose Image File
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Square images work best.</small>
                                    <button type="submit" class="btn btn-accent px-4" id="saveAvatarBtn" disabled>
                                        <i class="bi bi-upload me-1"></i>Upload & Save Photo
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ===================================
                             TAB 1: CHANGE EMAIL
                             =================================== -->
                        <div class="tab-pane fade <?php echo $activeTab === 'email' ? 'show active' : ''; ?>" 
                             id="tab-email" role="tabpanel" aria-labelledby="email-tab">
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="dashboard-stat-icon me-3" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                    <i class="bi bi-envelope-at"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0">Change Email Address</h4>
                                    <small class="text-muted">Update the email address associated with your StudentHub account</small>
                                </div>
                            </div>

                            <!-- Alert Errors -->
                            <?php if (!empty($emailErrors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($emailErrors as $err): ?>
                                            <li><?php echo sanitize($err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form action="settings.php" method="POST" novalidate id="changeEmailForm">
                                <input type="hidden" name="action" value="change_email">

                                <div class="mb-3">
                                    <label class="form-label text-muted">Current Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                        <input type="text" class="form-control bg-light" value="<?php echo sanitize($currentUser['email']); ?>" readonly disabled>
                                    </div>
                                    <div class="form-text">This is the email you currently use to log in.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_email" class="form-label">New Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input type="email" class="form-control" id="new_email" name="new_email" 
                                               placeholder="name@example.com" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_email" class="form-label">Confirm New Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-check2-circle"></i></span>
                                        <input type="email" class="form-control" id="confirm_email" name="confirm_email" 
                                               placeholder="Re-enter your new email" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="email_current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" class="form-control" id="email_current_password" name="current_password" 
                                               placeholder="Enter current password to verify identity" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="email_current_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">For security, enter your current password to confirm this change.</div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-accent px-4">
                                        <i class="bi bi-check-lg me-1"></i>Save New Email
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ===================================
                             TAB 2: CHANGE PASSWORD
                             =================================== -->
                        <div class="tab-pane fade <?php echo $activeTab === 'password' ? 'show active' : ''; ?>" 
                             id="tab-password" role="tabpanel" aria-labelledby="password-tab">
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="dashboard-stat-icon me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: rgba(124,77,255,0.1); color: #7c4dff;">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0">Change Password</h4>
                                    <small class="text-muted">Ensure your account is using a secure and memorable password</small>
                                </div>
                            </div>

                            <!-- Alert Errors -->
                            <?php if (!empty($passwordErrors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($passwordErrors as $err): ?>
                                            <li><?php echo sanitize($err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form action="settings.php" method="POST" novalidate id="changePasswordForm">
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" 
                                               placeholder="Enter your current password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               placeholder="Enter new password (min. 6 characters)" minlength="6" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 6 characters. Use letters, numbers, and symbols for better security.</div>
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

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-accent px-4">
                                        <i class="bi bi-shield-check me-1"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ===================================
                             TAB 3: EDIT PROFILE
                             =================================== -->
                        <div class="tab-pane fade <?php echo $activeTab === 'profile' ? 'show active' : ''; ?>" 
                             id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="dashboard-stat-icon me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: rgba(25,135,84,0.1); color: #198754;">
                                    <i class="bi bi-person-lines-fill"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0">Edit Profile Details</h4>
                                    <small class="text-muted">Customize your public portfolio name, faculty, and skills</small>
                                </div>
                            </div>

                            <!-- Alert Errors -->
                            <?php if (!empty($profileErrors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($profileErrors as $err): ?>
                                            <li><?php echo sanitize($err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form action="settings.php" method="POST" novalidate id="editProfileForm">
                                <input type="hidden" name="action" value="update_profile">

                                <!-- Quick Avatar Banner -->
                                <div class="p-3 bg-light rounded-3 border mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="profile-avatar mb-0" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                            <?php if (!empty($currentUser['profile_image']) && file_exists('uploads/avatars/' . $currentUser['profile_image'])): ?>
                                                <img src="uploads/avatars/<?php echo sanitize($currentUser['profile_image']); ?>" alt="Avatar">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">Profile Photo</div>
                                            <div class="text-muted" style="font-size: 0.8rem;">Change your photo in the Profile Photo tab</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-accent btn-sm" onclick="document.getElementById('avatar-tab').click();">
                                        <i class="bi bi-camera me-1"></i>Change Photo
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">@</span>
                                        <input type="text" class="form-control bg-light" value="<?php echo sanitize($currentUser['username']); ?>" readonly disabled>
                                    </div>
                                    <div class="form-text">Usernames are permanent and cannot be changed.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo sanitize($currentUser['full_name']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="faculty" class="form-label">Faculty</label>
                                    <select class="form-select" id="faculty" name="faculty">
                                        <option value="">Select Faculty</option>
                                        <?php
                                        $faculties = [
                                            'Faculty of Technology',
                                            'Faculty of Applied Sciences',
                                            'Faculty of Management Studies',
                                            'Faculty of Social Sciences & Humanities',
                                            'Faculty of Agriculture',
                                            'Faculty of Medicine & Allied Sciences'
                                        ];
                                        foreach ($faculties as $fac):
                                            $selected = ($currentUser['faculty'] === $fac) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $fac; ?>" <?php echo $selected; ?>><?php echo $fac; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="skills" class="form-label">Skills</label>
                                    <input type="text" class="form-control" id="skills" name="skills" 
                                           value="<?php echo sanitize($currentUser['skills']); ?>" 
                                           placeholder="e.g. PHP, MySQL, JavaScript, Python, Arduino">
                                    <div class="form-text">Separate skills with commas. These appear on your student profile.</div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-accent px-4">
                                        <i class="bi bi-save me-1"></i>Save Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ===================================
                             TAB 5: DELETE ACCOUNT (DANGER ZONE)
                             =================================== -->
                        <div class="tab-pane fade <?php echo $activeTab === 'delete' ? 'show active' : ''; ?>" 
                             id="tab-delete" role="tabpanel" aria-labelledby="delete-tab">
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="dashboard-stat-icon me-3" style="width: 48px; height: 48px; font-size: 1.25rem; background: rgba(220,53,69,0.1); color: #dc3545;">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 text-danger">Delete Account</h4>
                                    <small class="text-muted">Permanently delete your profile, projects, and all associated data</small>
                                </div>
                            </div>

                            <!-- Alert Errors -->
                            <?php if (!empty($deleteErrors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($deleteErrors as $err): ?>
                                            <li><?php echo sanitize($err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Danger Warning Box -->
                            <div class="card border-danger bg-danger-subtle p-4 mb-4 rounded-3">
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-shield-exclamation text-danger fs-2 flex-shrink-0"></i>
                                    <div>
                                        <h5 class="text-danger fw-bold mb-2">Warning: This action is permanent and irreversible!</h5>
                                        <p class="small text-danger-emphasis mb-2">
                                            Once you delete your account, there is no going back. Please be absolutely certain before proceeding:
                                        </p>
                                        <ul class="small text-danger-emphasis mb-0 ps-3">
                                            <li>Your public student profile and username will be removed.</li>
                                            <li>All your shared projects, gallery images, and descriptions will be permanently erased.</li>
                                            <li>Your profile photo will be permanently removed from our server.</li>
                                            <li>You will immediately be logged out and cannot recover this account.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <form action="settings.php" method="POST" id="deleteAccountForm" novalidate>
                                <input type="hidden" name="action" value="delete_account">

                                <div class="mb-3">
                                    <label for="delete_current_password" class="form-label fw-semibold">
                                        Confirm Your Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" class="form-control" id="delete_current_password" name="current_password" 
                                               placeholder="Enter your current password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="delete_current_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Required to authenticate your identity.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_delete_text" class="form-label fw-semibold">
                                        Type <span class="badge bg-danger">DELETE</span> to confirm <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="confirm_delete_text" name="confirm_delete_text" 
                                           placeholder="Type DELETE" required autocomplete="off">
                                </div>

                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="confirm_delete_checkbox" name="confirm_delete_checkbox" required>
                                    <label class="form-check-label small fw-semibold text-danger" for="confirm_delete_checkbox">
                                        I understand that deleting my account will permanently erase all my data and cannot be recovered.
                                    </label>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                                    </a>
                                    <button type="button" class="btn btn-danger px-4" id="deleteAccountBtn">
                                        <i class="bi bi-trash3 me-1"></i>Delete Account Permanently
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Bootstrap Modal for Final Confirmation (Placed at root level outside containers to avoid backdrop stacking bug) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>Final Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-trash3 text-danger" style="font-size: 3.5rem;"></i>
                <h5 class="mt-3">Are you absolutely certain?</h5>
                <p class="text-muted small">
                    This will delete your account (<strong>@<?php echo sanitize($currentUser['username']); ?></strong>) along with all your uploaded projects and images. This action cannot be reversed.
                </p>
            </div>
            <div class="modal-footer justify-content-center bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="confirmDeleteSubmitBtn">
                    Yes, Permanently Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Avatar Dropzone & Preview ---
    const avatarInput = document.getElementById('settings_avatar_input');
    const previewWrapper = document.getElementById('settingsNewPreviewWrapper');
    const previewImg = document.getElementById('settingsNewPreviewImg');
    const defaultIcon = document.getElementById('settingsUploadIcon');
    const saveBtn = document.getElementById('saveAvatarBtn');
    const dropZone = document.getElementById('settingsDropZone');

    function handleFile(file) {
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller photo.');
            avatarInput.value = '';
            return;
        }

        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            alert('Please choose a valid JPG, PNG, or WEBP image.');
            avatarInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            if (previewWrapper) previewWrapper.classList.remove('d-none');
            if (defaultIcon) defaultIcon.classList.add('d-none');
            if (saveBtn) saveBtn.removeAttribute('disabled');
        };
        reader.readAsDataURL(file);
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            handleFile(this.files[0]);
        });
    }

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('border-primary', 'bg-white');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-primary', 'bg-white');
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                avatarInput.files = files;
                handleFile(files[0]);
            }
        }, false);
    }

    // --- Delete Account Button & Modal Validation ---
    const deleteBtn = document.getElementById('deleteAccountBtn');
    const deleteForm = document.getElementById('deleteAccountForm');
    const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
    const confirmDeleteSubmitBtn = document.getElementById('confirmDeleteSubmitBtn');
    const passwordInput = document.getElementById('delete_current_password');
    const confirmTextInput = document.getElementById('confirm_delete_text');
    const checkboxInput = document.getElementById('confirm_delete_checkbox');

    if (deleteBtn && confirmDeleteModalEl) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (!passwordInput || !passwordInput.value.trim()) {
                alert('Please enter your current password to proceed.');
                if (passwordInput) passwordInput.focus();
                return;
            }

            if (!confirmTextInput || confirmTextInput.value.trim().toUpperCase() !== 'DELETE') {
                alert('Please type DELETE in capital letters in the confirmation box.');
                if (confirmTextInput) confirmTextInput.focus();
                return;
            }

            if (!checkboxInput || !checkboxInput.checked) {
                alert('Please check the confirmation box acknowledging permanent data loss.');
                if (checkboxInput) checkboxInput.focus();
                return;
            }

            const modalInstance = bootstrap.Modal.getOrCreateInstance(confirmDeleteModalEl);
            modalInstance.show();
        });
    }

    if (confirmDeleteSubmitBtn && deleteForm) {
        confirmDeleteSubmitBtn.addEventListener('click', function() {
            deleteForm.submit();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
