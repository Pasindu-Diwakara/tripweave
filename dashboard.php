<?php
/**
 * TripWeave — dashboard.php
 * Logged-in user's trip dashboard.
 * Shows: greeting, all their trips (with stop previews), and quick actions.
 * Requires login — non-logged-in users are redirected to login page.
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Guard: must be logged in
requireLogin();

$userId   = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// ── Fetch all trips for this user ────────────────────
$trips = [];
$stmt  = $conn->prepare(
    'SELECT id, trip_name, start_date, end_date, created_at
     FROM trips WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $trips[] = $row; }
$stmt->close();

// ── For each trip, fetch a preview of its stops ───────
$tripPreviews = [];
foreach ($trips as $trip) {
    $tid  = (int)$trip['id'];
    $stmt = $conn->prepare(
        'SELECT stop_name, day_number, stop_time
         FROM itinerary_stops WHERE trip_id = ? ORDER BY day_number, stop_time LIMIT 4'
    );
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $res = $stmt->get_result();
    $stops = [];
    while ($s = $res->fetch_assoc()) { $stops[] = $s; }
    $stmt->close();

    // Count total stops
    $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM itinerary_stops WHERE trip_id = ?');
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $countRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $tripPreviews[$tid] = [
        'stops'      => $stops,
        'totalStops' => (int)$countRow['cnt'],
    ];
}

// ── Handle trip delete ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_trip') {
    $delId = (int)($_POST['trip_id'] ?? 0);
    // Verify ownership before delete
    $stmt  = $conn->prepare('DELETE FROM trips WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $delId, $userId);
    $stmt->execute();
    $stmt->close();
    // Cascade deletes itinerary_stops via FK ON DELETE CASCADE (defined in schema)
    setFlash('success', 'Trip deleted.');
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — TripWeave</title>
  <meta name="description" content="View and manage all your TripWeave travel itineraries from your personal dashboard.">
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
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
      </ul>
      <div class="ms-lg-3 d-flex gap-2 mt-3 mt-lg-0">
        <span style="font-size:0.85rem;color:var(--gray-600);align-self:center;">
          <i class="bi bi-person-circle text-teal"></i> <?= e($username) ?>
        </span>
        <a href="auth/logout.php" class="btn-outline-teal btn" style="font-size:0.85rem;padding:0.45rem 1.1rem;">Log Out</a>
      </div>
    </div>
  </div>
</nav>

<!-- ── Dashboard Header ───────────────────────────── -->
<div class="dashboard-header">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <div class="section-label" style="color:var(--sand);">Your Space</div>
        <h1 style="color:white;font-size:clamp(1.6rem,4vw,2.4rem);margin-bottom:0.4rem;">
          Welcome back, <?= e($username) ?>
        </h1>
        <p style="color:rgba(255,255,255,0.65);font-size:0.95rem;margin:0;">
          You have <strong><?= count($trips) ?></strong> trip<?= count($trips) !== 1 ? 's' : '' ?> planned.
        </p>
      </div>
      <a href="planner.php" class="btn-orange btn">
        <i class="bi bi-plus-circle"></i> New Trip
      </a>
    </div>

    <!-- Quick stats row -->
    <?php
    $totalStops = array_sum(array_column($tripPreviews, 'totalStops'));
    $totalDaysAll = 0;
    foreach ($trips as $t) $totalDaysAll += tripDays($t['start_date'], $t['end_date']);
    ?>
    <div class="hero-stats mt-4">
      <div class="stat-item">
        <div class="stat-num"><?= count($trips) ?></div>
        <div class="stat-label">Trips</div>
      </div>
      <div class="stat-item">
        <div class="stat-num"><?= $totalStops ?></div>
        <div class="stat-label">Stops Planned</div>
      </div>
      <div class="stat-item">
        <div class="stat-num"><?= $totalDaysAll ?></div>
        <div class="stat-label">Days of Adventure</div>
      </div>
    </div>
  </div>
</div>

<!-- ── Flash Messages ─────────────────────────────── -->
<div class="container mt-4">
  <?php renderFlash(); ?>
</div>

<!-- ── Trip Cards Grid ────────────────────────────── -->
<div class="container py-4 pb-5">

  <?php if (empty($trips)): ?>
  <!-- Empty state -->
  <div class="empty-dashboard text-center">
    <span class="icon-big"><i class="bi bi-map"></i></span>
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:0.75rem;">No Trips Yet</h3>
    <p style="color:var(--gray-400);max-width:380px;margin:0 auto 2rem;font-size:0.95rem;">
      Your adventure awaits! Create your first trip and start adding destinations.
    </p>
    <a href="planner.php" class="btn-teal btn">
      <i class="bi bi-compass"></i> Plan My First Trip
    </a>
  </div>

  <?php else: ?>
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h2 style="font-family:'Inter',sans-serif;font-size:1.15rem;font-weight:700;color:var(--dark);">
      <i class="bi bi-suitcase-lg text-teal me-2"></i>Your Trips
    </h2>
    <a href="planner.php" class="btn-outline-teal btn" style="font-size:0.85rem;padding:0.45rem 1.2rem;">
      <i class="bi bi-plus"></i> New Trip
    </a>
  </div>

  <div class="row g-4">
    <?php foreach ($trips as $trip):
      $tid   = (int)$trip['id'];
      $prev  = $tripPreviews[$tid];
      $days  = tripDays($trip['start_date'], $trip['end_date']);
    ?>
    <div class="col-md-6 col-xl-4 aos-item">
      <div class="trip-card h-100">
        <!-- Card header gradient -->
        <div class="trip-card-header">
          <div class="trip-card-name"><?= e($trip['trip_name']) ?></div>
          <div class="trip-card-dates">
            <i class="bi bi-calendar3 me-1"></i>
            <?= fmtDate($trip['start_date']) ?> → <?= fmtDate($trip['end_date']) ?>
          </div>
          <div style="margin-top:0.5rem;font-size:0.78rem;color:rgba(255,255,255,0.6);">
            <?= $days ?> day<?= $days !== 1 ? 's' : '' ?> &nbsp;·&nbsp;
            <?= $prev['totalStops'] ?> stop<?= $prev['totalStops'] !== 1 ? 's' : '' ?>
          </div>
        </div>

        <!-- Card body: stop previews -->
        <div class="trip-card-body">
          <?php if (!empty($prev['stops'])): ?>
            <?php foreach ($prev['stops'] as $s): ?>
            <div class="trip-stop-preview">
              <i class="bi bi-geo-alt-fill"></i>
              <span style="flex:1;font-weight:500;"><?= e($s['stop_name']) ?></span>
              <span style="font-size:0.75rem;color:var(--gray-400);">Day <?= (int)$s['day_number'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if ($prev['totalStops'] > 4): ?>
            <div style="font-size:0.8rem;color:var(--gray-400);margin-top:0.5rem;padding-top:0.5rem;border-top:1px solid var(--gray-100);">
              + <?= $prev['totalStops'] - 4 ?> more stop<?= ($prev['totalStops'] - 4 !== 1) ? 's' : '' ?>…
            </div>
            <?php endif; ?>
          <?php else: ?>
            <p style="font-size:0.85rem;color:var(--gray-400);margin:0;">No stops added yet.</p>
          <?php endif; ?>

          <!-- Action buttons -->
          <div class="d-flex gap-2 mt-3 pt-3" style="border-top:1px solid var(--gray-100);">
            <a href="planner.php?trip_id=<?= $tid ?>" class="btn-teal btn" style="font-size:0.82rem;padding:0.45rem 1rem;flex:1;justify-content:center;">
              <i class="bi bi-pencil"></i> Edit
            </a>
            <!-- Delete trip button triggers modal -->
            <button type="button" class="btn-outline-teal btn"
              style="font-size:0.82rem;padding:0.45rem 0.85rem;color:#DC2626;border-color:#DC2626;"
              onclick="confirmDelete(<?= $tid ?>, '<?= e(addslashes($trip['trip_name'])) ?>')"
              aria-label="Delete trip <?= e($trip['trip_name']) ?>">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Delete Confirmation Modal ──────────────────── -->
<div class="modal fade modal-custom" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header" style="background:linear-gradient(135deg,#DC2626,#991B1B);">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Trip</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-4">
        <p style="color:var(--gray-600);margin:0;">
          Are you sure you want to delete <strong id="deleteTripName"></strong>?
          All stops will be permanently removed.
        </p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="POST" action="dashboard.php" id="deleteTripForm">
          <input type="hidden" name="action"  value="delete_trip">
          <input type="hidden" name="trip_id" id="deleteTripId" value="">
          <button type="submit" class="btn btn-danger">Yes, Delete</button>
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
<script>
/* ── Delete trip modal helper ────────────────────── */
function confirmDelete(tripId, tripName) {
  document.getElementById('deleteTripId').value   = tripId;
  document.getElementById('deleteTripName').textContent = '"' + tripName + '"';
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>
