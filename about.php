<?php
/**
 * About Page — StudentHub
 * Explains the purpose and features of the platform.
 */
$pageTitle = 'About';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-info-circle me-2"></i>About StudentHub</h1>
        <p>Learn about our platform and how it helps university students.</p>
    </div>
</section>

<!-- Mission Section -->
<section class="about-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 fade-in">
                <h2 class="section-title mb-3">Our Mission</h2>
                <p class="about-content">
                    StudentHub was built with one simple goal — to create a digital space where university students
                    can showcase their academic, technical and creative work to a wider audience.
                </p>
                <p class="about-content">
                    We believe that every student project, whether it is a web application, a research paper,
                    an IoT prototype or a creative design, deserves a place to be seen and appreciated.
                    StudentHub provides that platform.
                </p>
                <p class="about-content">
                    Our platform is designed for students of the Rajarata University of Sri Lanka,
                    specifically the Faculty of Technology, to present their work in a professional
                    and accessible manner.
                </p>
            </div>
            <div class="col-lg-6 fade-in delay-1">
                <div class="card border-0 shadow-sm p-4" style="border-radius: var(--radius-lg); background: var(--bg-alt);">
                    <div class="text-center">
                        <i class="bi bi-mortarboard-fill" style="font-size: 4rem; color: var(--accent); opacity: 0.8;"></i>
                        <h4 class="mt-3 mb-2">Rajarata University of Sri Lanka</h4>
                        <p class="text-muted mb-1">Faculty of Technology</p>
                        <p class="text-muted mb-0">Department of Materials</p>
                        <hr class="my-3">
                        <p class="text-muted mb-0" style="font-size: 0.88rem;">ICT 2209 — Web Technologies Mini Project</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What Students Can Do -->
<section class="section-padding section-alt">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fade-in">What Students Can Do</h2>
            <p class="section-subtitle fade-in">StudentHub offers a range of features to help students present their best work</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['icon' => 'bi-person-badge', 'title' => 'Create a Profile', 'desc' => 'Build a personal student profile with your skills, faculty and academic interests.'],
                ['icon' => 'bi-folder-plus', 'title' => 'Showcase Projects', 'desc' => 'Upload and describe your academic, technical and creative projects with images and links.'],
                ['icon' => 'bi-lightning', 'title' => 'Share Skills', 'desc' => 'List the programming languages, tools and technologies you have experience with.'],
                ['icon' => 'bi-binoculars', 'title' => 'Discover Projects', 'desc' => 'Browse projects created by other students across multiple categories and disciplines.'],
                ['icon' => 'bi-search', 'title' => 'Search & Filter', 'desc' => 'Find specific projects using search, category filters and sorting options.'],
                ['icon' => 'bi-file-earmark-person', 'title' => 'Build a Digital Portfolio', 'desc' => 'Create a sharable portfolio page that showcases your best work to future employers.'],
            ];
            $fi = 0;
            foreach ($features as $feat): $fi++;
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in delay-<?php echo min($fi, 6); ?>">
                        <div class="feature-icon">
                            <i class="bi <?php echo $feat['icon']; ?>"></i>
                        </div>
                        <h5 class="feature-title"><?php echo $feat['title']; ?></h5>
                        <p class="feature-desc"><?php echo $feat['desc']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="steps-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title fade-in">How It Works</h2>
            <p class="section-subtitle fade-in">Get started in just four simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="step-card fade-in delay-1">
                    <div class="step-number">1</div>
                    <h5 class="step-title">Create Account</h5>
                    <p class="step-desc">Register with your university email and set up your student profile.</p>
                    <div class="step-connector"></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="step-card fade-in delay-2">
                    <div class="step-number">2</div>
                    <h5 class="step-title">Build Profile</h5>
                    <p class="step-desc">Add your skills, faculty details and a brief introduction.</p>
                    <div class="step-connector"></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="step-card fade-in delay-3">
                    <div class="step-number">3</div>
                    <h5 class="step-title">Add Projects</h5>
                    <p class="step-desc">Upload your academic and technical projects with descriptions and images.</p>
                    <div class="step-connector"></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="step-card fade-in delay-4">
                    <div class="step-number">4</div>
                    <h5 class="step-title">Share Your Skills</h5>
                    <p class="step-desc">Let the world see what you have built and connect with opportunities.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content fade-in">
            <h2 class="cta-title">Ready to showcase your work?</h2>
            <p class="cta-text">Join StudentHub today and start building your digital portfolio.</p>
            <a href="register.php" class="btn btn-accent btn-lg">
                <i class="bi bi-person-plus me-2"></i>Get Started
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
