<?php
/**
 * TripWeave — auth/register.php
 * Handles new user registration:
 *  1. Renders the registration form (GET)
 *  2. Validates + inserts the user (POST)
 *
 * Security:
 *  - All inputs sanitized server-side
 *  - Password hashed with PASSWORD_BCRYPT via password_hash()
 *  - Prepared statements used; NO raw SQL concatenation
 *  - No plain-text passwords stored or logged
 */

// Start session before any output
session_start();

// Path adjustments — files are inside /auth/ subdirectory
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect already-logged-in users to the dashboard
if (isLoggedIn()) redirect('../dashboard.php');

$errors = [];
$old    = []; // Re-populate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── 1. Retrieve & sanitize raw inputs ──────────────────
    $username  = sanitize($_POST['username']  ?? '');
    $email     = sanitize($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';   // NOT sanitized — hashed as-is
    $password2 = $_POST['password2'] ?? '';

    $old = compact('username', 'email');

    // ── 2. Server-side validation ───────────────────────────
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 40) {
        $errors['username'] = 'Username must be 3–40 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username may only contain letters, numbers, and underscores.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($password !== $password2) {
        $errors['password2'] = 'Passwords do not match.';
    }

    // ── 3. Check for duplicate username / email ─────────────
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['general'] = 'That username or email is already registered. Try logging in.';
        }
        $stmt->close();
    }

    // ── 4. Insert new user ──────────────────────────────────
    if (empty($errors)) {
        // Hash the password — NEVER store plain text
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            'INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->bind_param('sss', $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;
            $stmt->close();

            // Log the user in immediately after registration
            $_SESSION['user_id']  = $newUserId;
            $_SESSION['username'] = $username;
            $_SESSION['email']    = $email;

            setFlash('success', "Welcome to TripWeave, {$username}! Start planning your first trip.");
            redirect('../dashboard.php');
        } else {
            $stmt->close();
            $errors['general'] = 'Registration failed due to a server error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — TripWeave</title>
  <meta name="description" content="Create your free TripWeave account and start planning beautiful travel itineraries.">
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
      <a href="../auth/login.php" class="btn-outline-teal btn" style="font-size:0.85rem;padding:0.5rem 1.2rem;">Log In</a>
    </div>
  </div>
</nav>

<!-- ── Registration Form ──────────────────────────── -->
<main class="auth-page">
  <div class="container">
    <div class="auth-card animate-popIn">

      <!-- Header -->
      <div class="text-center mb-4">
        <div class="logo-mark mx-auto mb-3" style="width:52px;height:52px;font-size:1.4rem;">T</div>
        <h1 class="auth-card-title">Create Account</h1>
        <p class="auth-card-sub">Join TripWeave and plan your dream journeys</p>
      </div>

      <!-- Flash / General error -->
      <?php renderFlash(); ?>
      <?php if (!empty($errors['general'])): ?>
        <div class="alert-flash error"><?= e($errors['general']) ?></div>
      <?php endif; ?>

      <!-- Form -->
      <form id="registerForm" method="POST" action="" novalidate>

        <!-- Username -->
        <div class="mb-3">
          <label class="form-label-custom" for="username">Username</label>
          <div class="datepicker-wrap" style="position:static;"><!-- reuse input wrap -->
            <input
              class="form-control-custom <?= !empty($errors['username']) ? 'error-field' : '' ?>"
              type="text"
              id="username"
              name="username"
              placeholder="e.g. travel_ninja"
              value="<?= e($old['username'] ?? '') ?>"
              required
              autocomplete="username">
          </div>
          <div class="form-error-msg <?= !empty($errors['username']) ? 'visible' : '' ?>">
            <?= e($errors['username'] ?? '') ?>
          </div>
        </div>

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
        <div class="mb-3">
          <label class="form-label-custom" for="password">Password</label>
          <div class="input-group" style="position:relative;">
            <input
              class="form-control-custom <?= !empty($errors['password']) ? 'error-field' : '' ?>"
              type="password"
              id="password"
              name="password"
              placeholder="Min. 8 characters"
              required
              autocomplete="new-password"
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

        <!-- Confirm Password -->
        <div class="mb-4">
          <label class="form-label-custom" for="password2">Confirm Password</label>
          <input
            class="form-control-custom <?= !empty($errors['password2']) ? 'error-field' : '' ?>"
            type="password"
            id="password2"
            name="password2"
            placeholder="Repeat password"
            required
            autocomplete="new-password">
          <div class="form-error-msg <?= !empty($errors['password2']) ? 'visible' : '' ?>">
            <?= e($errors['password2'] ?? '') ?>
          </div>
        </div>

        <button type="submit" class="btn-teal w-100 justify-content-center">
          <i class="bi bi-person-plus"></i> Create My Account
        </button>
      </form>

      <p class="text-center mt-3" style="font-size:0.88rem;color:var(--gray-600);">
        Already have an account?
        <a href="../auth/login.php" style="color:var(--teal);font-weight:600;">Log in here</a>
      </p>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/validate.js"></script>
<script>
/* ── Client-side registration form validation ───── */
document.getElementById('registerForm').addEventListener('submit', function(e) {
  const ok = validateForm(this, [
    { id: 'username', rules: [
        { type: 'required',   message: 'Username is required.' },
        { type: 'minLength',  value: 3, message: 'Username must be at least 3 characters.' },
        { type: 'pattern',    value: '^[a-zA-Z0-9_]+$', message: 'Only letters, numbers, underscores.' }
    ]},
    { id: 'email', rules: [
        { type: 'required', message: 'Email is required.' },
        { type: 'email',    message: 'Enter a valid email address.' }
    ]},
    { id: 'password', rules: [
        { type: 'required',  message: 'Password is required.' },
        { type: 'minLength', value: 8, message: 'Password must be at least 8 characters.' }
    ]},
    { id: 'password2', rules: [
        { type: 'required', message: 'Please confirm your password.' },
        { type: 'match',    value: 'password', message: 'Passwords do not match.' }
    ]}
  ]);
  if (!ok) e.preventDefault();
});

/* ── Password toggle ─────────────────────────────── */
document.getElementById('togglePwd').addEventListener('click', function() {
  const pwd  = document.getElementById('password');
  const icon = document.getElementById('togglePwdIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    pwd.type = 'password';
    icon.className = 'bi bi-eye';
  }
});
</script>
</body>
</html>
