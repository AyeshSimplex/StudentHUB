<?php
/**
 * Header / Navbar — StudentHub
 *
 * This file is included at the top of every page.
 * It outputs the HTML head and the navigation bar.
 *
 * Before including this file, set:
 *   $pageTitle = "Page Title";  (optional)
 */

// Include functions if not already loaded
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}

// Default page title
if (!isset($pageTitle)) {
    $pageTitle = 'StudentHub';
}

$currentPage = getCurrentPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php if (isset($ogDescription)): ?>
        <meta name="description" content="<?php echo sanitize($ogDescription); ?>">
    <?php else: ?>
        <meta name="description" content="StudentHub - Showcase Your Skills. Share Your Projects. Build Your Future. A platform for university students.">
    <?php endif; ?>

    <title><?php echo sanitize($pageTitle); ?> — StudentHub</title>

    <!-- Open Graph / Link Previews -->
    <?php if (isset($ogTitle)): ?>
        <meta property="og:title" content="<?php echo sanitize($ogTitle); ?>">
    <?php else: ?>
        <meta property="og:title" content="<?php echo sanitize($pageTitle); ?> — StudentHub">
    <?php endif; ?>
    
    <?php if (isset($ogDescription)): ?>
        <meta property="og:description" content="<?php echo sanitize($ogDescription); ?>">
    <?php else: ?>
        <meta property="og:description" content="StudentHub - Showcase Your Skills. Share Your Projects. Build Your Future.">
    <?php endif; ?>

    <?php if (isset($ogImage)): ?>
        <meta property="og:image" content="<?php echo sanitize($ogImage); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?php echo sanitize($ogImage); ?>">
    <?php endif; ?>

    <?php if (isset($ogUrl)): ?>
        <meta property="og:url" content="<?php echo sanitize($ogUrl); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNavbar">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-mortarboard-fill me-2"></i>Student<span class="brand-accent">Hub</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'projects.php' ? 'active' : ''; ?>" href="projects.php">
                        <i class="bi bi-grid me-1"></i>Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <i class="bi bi-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                        <i class="bi bi-envelope me-1"></i>Contact
                    </a>
                </li>
            </ul>

            <!-- Right Side Nav -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isLoggedIn()): ?>
                    <!-- Logged In Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?> d-inline-flex align-items-center" href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                            <?php if (!empty($_SESSION['profile_image']) && file_exists(__DIR__ . '/../uploads/avatars/' . $_SESSION['profile_image'])): ?>
                                <img src="uploads/avatars/<?php echo sanitize($_SESSION['profile_image']); ?>" alt="Profile" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover; margin-right: 6px; border: 1.5px solid var(--accent);">
                            <?php else: ?>
                                <i class="bi bi-person me-1"></i>
                            <?php endif; ?>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                            <i class="bi bi-gear me-1"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-logout" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-accent btn-sm ms-lg-2 mt-2 mt-lg-0 nav-register-btn" href="register.php">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div class="container mt-3">
    <?php
    echo flashMessage('success');
    echo flashMessage('error');
    echo flashMessage('warning');
    echo flashMessage('info');
    ?>
</div>
