<?php
/**
 * TripWeave — auth/login.php
 * Handles user login:
 *  1. Renders the login form (GET)
 *  2. Verifies credentials and starts session (POST)
 *
 * Security:
 *  - Inputs sanitized server-side
 *  - password_verify() used — NEVER plain-text comparison
 *  - Prepared statements — no raw SQL concatenation
 *  - Session regenerated on login to prevent session fixation
 */

session_start();

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Already logged in → go to dashboard
if (isLoggedIn()) redirect('../dashboard.php');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── 1. Sanitize inputs ──────────────────────────────────
    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';  // NOT sanitized — must match stored hash

    $old = ['email' => $email];

    // ── 2. Basic validation ─────────────────────────────────
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    // ── 3. Look up user by email ────────────────────────────
    if (empty($errors)) {
        $stmt = $conn->prepare(
            'SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        // ── 4. Verify password hash ─────────────────────────
        if (!$user || !password_verify($password, $user['password'])) {
            // Generic message prevents username enumeration
            $errors['general'] = 'Invalid email or password. Please try again.';
        } else {
            // ── 5. Regenerate session ID (prevents session fixation)
            session_regenerate_id(true);

            // Store user info in session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];

            setFlash('success', "Welcome back, {$user['username']}!");
            redirect('../dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — TripWeave</title>
  <meta name="description" content="Log in to your TripWeave account to view and manage your travel itineraries.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">
      <div class="logo-mark">T</div>
      <span class="brand-text">TripWeave</span>
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="../auth/register.php" class="btn-teal btn" style="font-size:0.85rem;padding:0.5rem 1.2rem;">Sign Up Free</a>
    </div>
  </div>
</nav>

<!-- ── Login Form ─────────────────────────────────── -->
<main class="auth-page">
  <div class="container">
    <div class="auth-card animate-popIn">

      <!-- Header -->
      <div class="text-center mb-4">
        <div class="logo-mark mx-auto mb-3" style="width:52px;height:52px;font-size:1.4rem;">T</div>
        <h1 class="auth-card-title">Welcome Back</h1>
        <p class="auth-card-sub">Log in to continue planning your adventures</p>
      </div>

      <!-- Flash / General error -->
      <?php renderFlash(); ?>
      <?php if (!empty($errors['general'])): ?>
        <div class="alert-flash error"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <!-- Form -->
      <form id="loginForm" method="POST" action="" novalidate>

        <!-- Email -->
        <div class="mb-3">
          <label class="form-label-custom" for="email">Email Address</label>
          <input
            class="form-control-custom <?= !empty($errors['email']) ? 'error-field' : '' ?>"
            type="email"
            id="email"
            name="email"
            placeholder="you@example.com"
            value="<?= e($old['email'] ?? '') ?>"
            required
            autocomplete="email">
          <div class="form-error-msg <?= !empty($errors['email']) ? 'visible' : '' ?>">
            <?= e($errors['email'] ?? '') ?>
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label-custom mb-0" for="password">Password</label>
          </div>
          <div style="position:relative;">
            <input
              class="form-control-custom <?= !empty($errors['password']) ? 'error-field' : '' ?>"
              type="password"
              id="password"
              name="password"
              placeholder="Your password"
              required
              autocomplete="current-password"
              style="padding-right:2.5rem;">
            <button type="button" id="togglePwd"
              style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);z-index:5;"
              aria-label="Toggle password visibility">
              <i class="bi bi-eye" id="togglePwdIcon"></i>
            </button>
          </div>
          <div class="form-error-msg <?= !empty($errors['password']) ? 'visible' : '' ?>">
            <?= e($errors['password'] ?? '') ?>
          </div>
        </div>

        <button type="submit" class="btn-teal w-100 justify-content-center">
          <i class="bi bi-box-arrow-in-right"></i> Log In
        </button>
      </form>

      <p class="text-center mt-3" style="font-size:0.88rem;color:var(--gray-600);">
        Don't have an account?
        <a href="../auth/register.php" style="color:var(--teal);font-weight:600;">Sign up free</a>
      </p>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/validate.js"></script>
<script>
/* ── Client-side login validation ────────────────── */
document.getElementById('loginForm').addEventListener('submit', function(e) {
  const ok = validateForm(this, [
    { id: 'email',    rules: [
        { type: 'required', message: 'Email is required.' },
        { type: 'email',    message: 'Enter a valid email address.' }
    ]},
    { id: 'password', rules: [
        { type: 'required', message: 'Password is required.' }
    ]}
  ]);
  if (!ok) e.preventDefault();
});

/* ── Password toggle ─────────────────────────────── */
document.getElementById('togglePwd').addEventListener('click', function() {
  const pwd  = document.getElementById('password');
  const icon = document.getElementById('togglePwdIcon');
  pwd.type   = pwd.type === 'password' ? 'text' : 'password';
  icon.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});
</script>
</body>
</html>
