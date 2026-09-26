<?php
/**
 * TripWeave — contact.php
 * Contact page with a form that submits messages to the DB.
 *
 * Features:
 *  - Server-side validation (name, email, message required)
 *  - Prepared statement insert into `messages` table
 *  - PHPMailer stub commented out for future email notification
 *  - Client-side validation runs alongside server-side
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$errors = [];
$old    = [];
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Sanitize inputs ─────────────────────────────────
    $name    = sanitize($_POST['name']    ?? '');
    $email   = sanitize($_POST['email']   ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    $old = compact('name', 'email', 'subject', 'message');

    // ── Server-side validation ──────────────────────────
    if (empty($name))    $errors['name']    = 'Your name is required.';
    if (empty($email))   $errors['email']   = 'Your email is required.';
    elseif (!isValidEmail($email)) $errors['email'] = 'Enter a valid email address.';
    if (empty($message)) $errors['message'] = 'Message cannot be empty.';
    elseif (strlen($message) < 10) $errors['message'] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        // ── Insert into messages table ───────────────────
        $stmt = $conn->prepare(
            'INSERT INTO messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param('ssss', $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $stmt->close();

            /*
             * ── PHPMailer stub ──────────────────────────────
             * Uncomment and configure to send email notifications.
             * Requires: composer require phpmailer/phpmailer
             *
             * use PHPMailer\PHPMailer\PHPMailer;
             * require 'vendor/autoload.php';
             *
             * $mail = new PHPMailer(true);
             * $mail->isSMTP();
             * $mail->Host       = 'smtp.gmail.com';
             * $mail->SMTPAuth   = true;
             * $mail->Username   = 'your@gmail.com';
             * $mail->Password   = 'your-app-password';
             * $mail->SMTPSecure = 'tls';
             * $mail->Port       = 587;
             * $mail->setFrom('noreply@tripweave.com', 'TripWeave');
             * $mail->addAddress('admin@tripweave.com');
             * $mail->Subject    = 'New contact: ' . $subject;
             * $mail->Body       = "From: $name <$email>\n\n$message";
             * $mail->send();
             */

            $sent = true;
            $old  = []; // Clear form on success
            setFlash('success', "Thanks, {$name}! We received your message and will get back to you soon.");
        } else {
            $stmt->close();
            $errors['general'] = 'Failed to send message. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — TripWeave</title>
  <meta name="description" content="Get in touch with the TripWeave team. We'd love to hear from you!">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <div class="logo-mark">T</div>
      <span class="brand-text">TripWeave</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Toggle navigation">
      <i class="bi bi-list" style="font-size:1.5rem;color:var(--teal);"></i>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="planner.php">Planner</a></li>
        <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <div class="ms-lg-3 d-flex gap-2 mt-3 mt-lg-0">
        <?php if (isLoggedIn()): ?>
          <a href="dashboard.php" class="btn-teal btn" style="font-size:0.85rem;">
            <i class="bi bi-grid-1x2"></i> Dashboard
          </a>
          <a href="auth/logout.php" class="btn-outline-teal btn" style="font-size:0.85rem;">Log Out</a>
        <?php else: ?>
          <a href="auth/login.php"    class="btn-outline-teal btn">Log In</a>
          <a href="auth/register.php" class="btn-teal btn">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ── Contact Hero ───────────────────────────────── -->
<div class="contact-hero">
  <div class="container">
    <div class="section-label" style="color:var(--sand);">We'd Love to Hear From You</div>
    <h1 style="color:white;font-size:clamp(1.8rem,4vw,2.8rem);margin-bottom:0.5rem;">Contact TripWeave</h1>
    <p style="color:rgba(255,255,255,0.65);max-width:500px;">
      Questions, suggestions, or just want to say hello? Drop us a message and we'll get back to you shortly.
    </p>
  </div>
</div>

<!-- ── Contact Body ───────────────────────────────── -->
<div class="container py-5">
  <div class="row g-4 align-items-start">

    <!-- Left: Info card -->
    <div class="col-lg-4 aos-item">
      <div class="contact-info-card">
        <h3 style="font-family:'Inter',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:1.75rem;color:white;">
          Get in Touch
        </h3>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Email</div>
            <div style="color:white;font-weight:600;font-size:0.9rem;">hello@tripweave.com</div>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <div>
            <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Location</div>
            <div style="color:white;font-weight:600;font-size:0.9rem;">Singapore</div>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="bi bi-clock-fill"></i></div>
          <div>
            <div style="font-size:0.78rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Response Time</div>
            <div style="color:white;font-weight:600;font-size:0.9rem;">Within 24 hours</div>
          </div>
        </div>

        <hr style="border-color:rgba(255,255,255,0.15);margin:1.5rem 0;">

        <div style="font-size:0.85rem;color:rgba(255,255,255,0.6);">Follow our adventures:</div>
        <div class="mt-2">
          <a href="#" class="social-icon" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
    </div>

    <!-- Right: Contact Form -->
    <div class="col-lg-8 aos-item stagger-1">
      <?php renderFlash(); ?>
      <?php if (!empty($errors['general'])): ?>
        <div class="alert-flash error"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <div class="form-card">
        <h2 style="font-family:'Inter',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:0.25rem;">Send a Message</h2>
        <p style="font-size:0.88rem;color:var(--gray-400);margin-bottom:1.75rem;">All fields marked * are required.</p>

        <form id="contactForm" method="POST" action="contact.php" novalidate>

          <div class="row g-3">
            <!-- Name -->
            <div class="col-md-6">
              <label class="form-label-custom" for="name">Full Name *</label>
              <input class="form-control-custom <?= !empty($errors['name']) ? 'error-field' : '' ?>"
                type="text" id="name" name="name"
                placeholder="Your full name"
                value="<?= e($old['name'] ?? '') ?>" required>
              <div class="form-error-msg <?= !empty($errors['name']) ? 'visible' : '' ?>">
                <?= e($errors['name'] ?? '') ?>
              </div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label class="form-label-custom" for="email">Email Address *</label>
              <input class="form-control-custom <?= !empty($errors['email']) ? 'error-field' : '' ?>"
                type="email" id="email" name="email"
                placeholder="you@example.com"
                value="<?= e($old['email'] ?? '') ?>" required>
              <div class="form-error-msg <?= !empty($errors['email']) ? 'visible' : '' ?>">
                <?= e($errors['email'] ?? '') ?>
              </div>
            </div>

            <!-- Subject -->
            <div class="col-12">
              <label class="form-label-custom" for="subject">Subject</label>
              <input class="form-control-custom"
                type="text" id="subject" name="subject"
                placeholder="e.g. Feature request, Bug report, General inquiry"
                value="<?= e($old['subject'] ?? '') ?>">
            </div>

            <!-- Message -->
            <div class="col-12">
              <label class="form-label-custom" for="message">Message *</label>
              <textarea class="form-control-custom <?= !empty($errors['message']) ? 'error-field' : '' ?>"
                id="message" name="message" rows="6"
                placeholder="Tell us what's on your mind…" required><?= e($old['message'] ?? '') ?></textarea>
              <div class="form-error-msg <?= !empty($errors['message']) ? 'visible' : '' ?>">
                <?= e($errors['message'] ?? '') ?>
              </div>
            </div>

            <div class="col-12">
              <button type="submit" class="btn-teal btn" id="contactSubmitBtn">
                <i class="bi bi-send"></i> Send Message
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ── Footer ─────────────────────────────────────── -->
<footer class="footer">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-md-6">
        <a class="navbar-brand" href="index.php" style="display:inline-flex;">
          <div class="logo-mark">T</div>
          <span class="brand-text ms-2">TripWeave</span>
        </a>
      </div>
      <div class="col-md-6 text-md-end">
        <p class="footer-copy mb-0">&copy; <?= date('Y') ?> TripWeave. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script src="js/validate.js"></script>
<script>
/* ── Client-side contact form validation ─────────── */
document.getElementById('contactForm').addEventListener('submit', function(e) {
  const ok = validateForm(this, [
    { id: 'name',    rules: [{ type: 'required', message: 'Your name is required.' }] },
    { id: 'email',   rules: [
        { type: 'required', message: 'Email is required.' },
        { type: 'email',    message: 'Enter a valid email address.' }
    ]},
    { id: 'message', rules: [
        { type: 'required',  message: 'Message cannot be empty.' },
        { type: 'minLength', value: 10, message: 'Please write at least 10 characters.' }
    ]}
  ]);
  if (!ok) {
    e.preventDefault();
    return;
  }
  // Show loading state on submit button
  const btn  = document.getElementById('contactSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending…';
});
</script>
</body>
</html>
