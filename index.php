<?php
/**
 * Home Page — StudentHub
 * Landing page with hero, stats, featured projects, categories, features, and CTA.
 */
$pageTitle = 'Home';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Fetch featured projects (latest 6)
ensureUserAvatarColumn($conn);
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.username, u.profile_image FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$featuredProjects = $stmt->get_result();
$stmt->close();

// Get counts for stats
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalProjects = $conn->query("SELECT COUNT(*) as c FROM projects")->fetch_assoc()['c'];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="bi bi-stars"></i>
                        Rajarata University of Sri Lanka
                    </div>
                    <h1 class="hero-title">
                        Turn Your Ideas Into Something <span class="highlight">Worth Sharing.</span>
                    </h1>
                    <p class="hero-description">
                        StudentHub gives university students a simple place to showcase projects, share skills and build a digital portfolio.
                    </p>
                    <div class="hero-buttons">
                        <a href="projects.php" class="btn btn-accent">
                            <i class="bi bi-grid me-2"></i>Explore Projects
                        </a>
                        <a href="register.php" class="btn btn-outline-accent">
                            <i class="bi bi-person-plus me-2"></i>Join StudentHub
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-illustration">
                    <div class="hero-visual">
                        <i class="bi bi-rocket-takeoff"></i>
                        <span class="visual-text">Innovation Starts Here</span>
                        <!-- Floating Cards -->
                        <div class="hero-float-card card-1">
                            <i class="bi bi-code-slash"></i>
                            <span>250+ Projects</span>
                        </div>
                        <div class="hero-float-card card-2">
                            <i class="bi bi-people"></i>
                            <span>500+ Students</span>
                        </div>
                        <div class="hero-float-card card-3">
                            <i class="bi bi-trophy"></i>
                            <span>Build Your Future</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section" id="stats">
    <div class="container">
        <div class="row">
            <div class="col-6 col-md-3">
                <div class="stat-card fade-in">
                    <div class="stat-number">
                        <span class="stat-counter" data-target="500" data-suffix="+">0</span>
                    </div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card fade-in delay-1">
                    <div class="stat-number">
                        <span class="stat-counter" data-target="250" data-suffix="+">0</span>
                    </div>
                    <div class="stat-label">Projects</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card fade-in delay-2">
                    <div class="stat-number">
                        <span class="stat-counter" data-target="40" data-suffix="+">0</span>
                    </div>
                    <div class="stat-label">Skills</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card fade-in delay-3">
                    <div class="stat-number">
                        <span class="stat-counter" data-target="15" data-suffix="+">0</span>
                    </div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Image Slider / Carousel -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title fade-in">Discover What Students Are Building</h2>
            <p class="section-subtitle fade-in">From IoT solutions to web applications — explore the latest innovations</p>
        </div>
        <div id="heroCarousel" class="carousel slide hero-carousel fade-in" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="carousel-slide-content">
                        <i class="bi bi-laptop"></i>
                        <h3>Web & Mobile Applications</h3>
                        <p>Full-stack projects built with modern technologies</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-slide-content">
                        <i class="bi bi-cpu"></i>
                        <h3>IoT & Embedded Systems</h3>
                        <p>Smart solutions powered by sensors and microcontrollers</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="carousel-slide-content">
                        <i class="bi bi-graph-up-arrow"></i>
                        <h3>Research & Data Analysis</h3>
                        <p>Academic research with real-world applications</p>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<!-- Featured Projects -->
<section class="featured-section" id="featured">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fade-in">Featured Projects</h2>
            <p class="section-subtitle fade-in">Explore the latest projects shared by our student community</p>
        </div>
        <div class="row g-4">
            <?php if ($featuredProjects->num_rows > 0): ?>
                <?php $i = 0; while ($project = $featuredProjects->fetch_assoc()): $i++; ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="project-card fade-in delay-<?php echo min($i, 6); ?>">
                            <!-- Project Image -->
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
                                    <span class="project-card-date"><?php echo getTimeAgo($project['created_at']); ?></span>
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
                        <p>Be the first to share your project!</p>
                        <a href="add-project.php" class="btn btn-accent">Add Project</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5 fade-in">
            <a href="projects.php" class="btn btn-outline-accent btn-lg">
                View All Projects <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section" id="categories">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fade-in">Explore by Category</h2>
            <p class="section-subtitle fade-in">Find projects that match your interests</p>
        </div>
        <div class="row g-3">
            <?php
            $categories = [
                ['name' => 'Information Technology', 'icon' => 'bi-laptop', 'slug' => 'Information Technology'],
                ['name' => 'Engineering', 'icon' => 'bi-gear', 'slug' => 'Engineering'],
                ['name' => 'Materials Technology', 'icon' => 'bi-boxes', 'slug' => 'Materials Technology'],
                ['name' => 'Science', 'icon' => 'bi-bezier2', 'slug' => 'Science'],
                ['name' => 'Business', 'icon' => 'bi-briefcase', 'slug' => 'Business'],
                ['name' => 'Design', 'icon' => 'bi-palette', 'slug' => 'Design'],
                ['name' => 'Agriculture', 'icon' => 'bi-tree', 'slug' => 'Agriculture'],
                ['name' => 'Other', 'icon' => 'bi-three-dots', 'slug' => 'Other'],
            ];
            $ci = 0;
            foreach ($categories as $cat): $ci++;
                // Count projects in this category
                $catStmt = $conn->prepare("SELECT COUNT(*) as count FROM projects WHERE category = ?");
                $catStmt->bind_param("s", $cat['name']);
                $catStmt->execute();
                $catCount = $catStmt->get_result()->fetch_assoc()['count'];
                $catStmt->close();
            ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="projects.php?category=<?php echo urlencode($cat['slug']); ?>" class="category-card fade-in delay-<?php echo min($ci, 6); ?>">
                        <div class="category-icon">
                            <i class="bi <?php echo $cat['icon']; ?>"></i>
                        </div>
                        <div class="category-name"><?php echo $cat['name']; ?></div>
                        <div class="category-count"><?php echo $catCount; ?> project<?php echo $catCount != 1 ? 's' : ''; ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why StudentHub / Features -->
<section class="features-section" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fade-in">Why StudentHub?</h2>
            <p class="section-subtitle fade-in">Everything you need to build your digital portfolio</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="feature-card fade-in delay-1">
                    <div class="feature-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h5 class="feature-title">Showcase</h5>
                    <p class="feature-desc">Present your academic and technical projects to a wider audience.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card fade-in delay-2">
                    <div class="feature-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="feature-title">Discover</h5>
                    <p class="feature-desc">Explore ideas created by fellow students across different disciplines.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card fade-in delay-3">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="feature-title">Connect</h5>
                    <p class="feature-desc">Find students with similar interests and collaborate on new ideas.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card fade-in delay-4">
                    <div class="feature-icon">
                        <i class="bi bi-file-earmark-person"></i>
                    </div>
                    <h5 class="feature-title">Build Your Portfolio</h5>
                    <p class="feature-desc">Create a professional digital profile to share with future employers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content fade-in">
            <h2 class="cta-title">Your next opportunity could start with one project.</h2>
            <p class="cta-text">Create your StudentHub profile and start showcasing what you can do.</p>
            <a href="register.php" class="btn btn-accent btn-lg">
                <i class="bi bi-person-plus me-2"></i>Create Your Profile
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
