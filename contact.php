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
        $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $subject, $message, $msgUserId);

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

                    <!-- Public Message Notice -->
                    <div class="public-message-notice">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Public Message Notice:</strong> Messages submitted here are publicly visible to other StudentHub users and may receive replies. Please do not include private or sensitive information such as passwords, phone numbers, or personal addresses.
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="name" 
                                           value="<?php echo sanitize($old['name']); ?>" required>
                                    <div class="form-error">Please enter your name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="contact_email" name="email" 
                                           value="<?php echo sanitize($old['email']); ?>" required>
                                    <div class="form-error">Please enter a valid email.</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_subject" name="subject" 
                                   value="<?php echo sanitize($old['subject']); ?>" required>
                            <div class="form-error">Please enter a subject.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="contact_message" name="message" rows="5" required><?php echo sanitize($old['message']); ?></textarea>
                            <div class="form-error">Message must be at least 10 characters.</div>
                        </div>
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-send me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
