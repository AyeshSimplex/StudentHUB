<?php
/**
 * Contact Page — StudentHub
 * Contact form that saves messages to the database as public messages.
 */
$pageTitle = 'Contact';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Ensure messages table has user_id column
ensureMessagesUserIdColumn($conn);

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('contact.php');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Save old values
    $old = compact('name', 'email', 'subject', 'message');

    // Server-side validation
    if (strlen($name) < 2) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($subject) < 3) {
        $errors[] = 'Please enter a subject.';
    }
    if (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $msgUserId = $_SESSION['user_id'] ?? null;
        if ($msgUserId !== null) {
            $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $subject, $message, $msgUserId);
        } else {
            $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Thank you! Your message has been submitted successfully.';
            $stmt->close();
            header('Location: contact.php');
            exit();
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-envelope me-2"></i>Contact Us</h1>
        <p>Have a question or suggestion? We would love to hear from you.</p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="row g-4">
            <!-- Contact Info -->
            <div class="col-lg-5 fade-in">
                <div class="contact-info-card">
                    <h3><i class="bi bi-chat-dots me-2"></i>Get In Touch</h3>
                    <p>We are here to help. Reach out to us through any of the following channels.</p>

                    <div class="contact-info-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <div class="info-label">Address</div>
                            <div class="info-value">Rajarata University of Sri Lanka<br>Mihintale, Sri Lanka</div>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <i class="bi bi-building"></i>
                        <div>
                            <div class="info-label">Department</div>
                            <div class="info-value">Faculty of Technology<br>Department of Materials</div>
                        </div>
                    </div>

                    <div class="contact-info-item">
                        <i class="bi bi-clock"></i>
                        <div>
                            <div class="info-label">Hours</div>
                            <div class="info-value">Mon — Fri: 8:00 AM — 5:00 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-7 fade-in delay-1">
                <div class="contact-form-card">
                    <h3><i class="bi bi-send me-2 accent-text"></i>Send a Message</h3>

                    <!-- Private Message Notice -->
                    <div class="public-message-notice" style="background: rgba(0, 255, 136, 0.1); border-left-color: var(--accent);">
                        <i class="bi bi-shield-lock-fill" style="color: var(--accent);"></i>
                        <div>
                            <strong>Privacy Assured:</strong> Your messages are sent directly to the StudentHub administrative team and are not visible to the public.
                        </div>
                    </div>

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

                    <form id="contactForm" method="POST" action="contact.php" novalidate>
                        <?php echo csrfField(); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="name"
                                        value="<?php echo sanitize($old['name']); ?>" required>
                                    <div class="form-error">Please enter your name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="contact_email" name="email"
                                        value="<?php echo sanitize($old['email']); ?>" required>
                                    <div class="form-error">Please enter a valid email.</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label">Subject <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_subject" name="subject"
                                value="<?php echo sanitize($old['subject']); ?>" required>
                            <div class="form-error">Please enter a subject.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_message" class="form-label">Message <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="contact_message" name="message" rows="5"
                                required><?php echo sanitize($old['message']); ?></textarea>
                            <div class="form-error">Message must be at least 10 characters.</div>
                        </div>
                        <div class="d-grid gap-3 d-md-flex mt-1">
                            <button type="submit" class="btn btn-accent flex-fill d-flex align-items-center justify-content-center">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                            <a href="dashboard.php#inquiries" class="btn btn-outline-primary flex-fill d-flex align-items-center justify-content-center">
                                <i class="bi bi-chat-square-text me-2"></i>View Inquiries
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
