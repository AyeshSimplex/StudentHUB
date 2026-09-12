<!-- Footer -->
<footer class="footer-section">
    <div class="container">
        <div class="row gy-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <h4><i class="bi bi-mortarboard-fill me-2"></i>Student<span class="brand-accent">Hub</span></h4>
                    <p class="footer-tagline">Showcase Your Skills. Share Your Projects. Build Your Future.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="#" aria-label="GitHub"><i class="bi bi-github"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="projects.php">Projects</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Student Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Student</h5>
                <ul class="footer-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo isLoggedIn() ? 'profile.php?id=' . $_SESSION['user_id'] : 'login.php'; ?>">Profile</a></li>
                    <li><a href="add-project.php">Add Project</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-title">Get In Touch</h5>
                <ul class="footer-links footer-contact">
                    <li><i class="bi bi-geo-alt me-2"></i>Rajarata University of Sri Lanka</li>
                    <li><i class="bi bi-building me-2"></i>Faculty of Technology</li>
                    <li><i class="bi bi-envelope me-2"></i><a href="mailto:ent2023048@tec.rjt.ac.lk" class="text-reset">ent2023048@tec.rjt.ac.lk</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <p>&copy; 2026 StudentHub. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>"></script>
</body>
</html>
