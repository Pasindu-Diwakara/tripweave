<?php
/**
 * TripWeave — planner.php
 * Itinerary Planner Page.
 *
 * Behaviour:
 *  - Guest users: can build a trip in the UI but must log in to save it.
 *  - Logged-in users:
 *    a) POST trip_name/start_date/end_date → creates a new trip → redirect to ?trip_id=X
 *    b) GET ?trip_id=X → load existing trip + stops from DB, show add-stop form
 *    c) POST (add stop) with trip_id → insert stop → redirect back to ?trip_id=X
 *    d) POST (delete stop) with stop_id → delete stop → redirect back
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$loggedIn = isLoggedIn();
$userId   = $loggedIn ? (int)$_SESSION['user_id'] : 0;

$currentTrip  = null;   // Current trip row from DB (or null)
$tripStops    = [];     // Stops for current trip
$userTrips    = [];     // All user trips for dropdown
$errors       = [];

/* ══════════════════════════════════════════════════
   1. Create a new trip (POST action=create_trip)
══════════════════════════════════════════════════ */
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_trip') {
    $tripName  = sanitize($_POST['trip_name']   ?? '');
    $startDate = sanitize($_POST['start_date']  ?? '');
    $endDate   = sanitize($_POST['end_date']    ?? '');

    // Server-side validation
    if (empty($tripName)) $errors[] = 'Trip name is required.';
    if (empty($startDate)) $errors[] = 'Start date is required.';
    if (empty($endDate))   $errors[] = 'End date is required.';
    if (!empty($startDate) && !empty($endDate) && $endDate < $startDate)
        $errors[] = 'End date must be on or after start date.';

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'INSERT INTO trips (user_id, trip_name, start_date, end_date, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param('isss', $userId, $tripName, $startDate, $endDate);
        if ($stmt->execute()) {
            $newTripId = $stmt->insert_id;
            $stmt->close();
            setFlash('success', "Trip \"{$tripName}\" created! Now add your stops.");
            redirect("planner.php?trip_id={$newTripId}");
        } else {
            $stmt->close();
            $errors[] = 'Failed to create trip. Please try again.';
        }
    }
}

/* ══════════════════════════════════════════════════
   2. Add a stop (POST action=add_stop)
══════════════════════════════════════════════════ */
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_stop') {
    $tripId   = (int)($_POST['trip_id']    ?? 0);
    $dayNum   = (int)($_POST['day_number'] ?? 1);
    $stopName = sanitize($_POST['stop_name'] ?? '');
    $stopTime = sanitize($_POST['stop_time'] ?? '');
    $notes    = sanitize($_POST['notes']     ?? '');

    // Verify the trip belongs to this user (prevents IDOR)
    $stmt = $conn->prepare('SELECT id FROM trips WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $tripId, $userId);
    $stmt->execute();
    $stmt->store_result();
    $tripBelongsToUser = ($stmt->num_rows > 0);
    $stmt->close();

    if (!$tripBelongsToUser) {
        setFlash('error', 'Trip not found or access denied.');
        redirect('planner.php');
    }

    if (empty($stopName)) $errors[] = 'Stop name is required.';
    if ($dayNum < 1)      $errors[] = 'Day number must be at least 1.';

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'INSERT INTO itinerary_stops (trip_id, day_number, stop_name, notes, stop_time, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param('iisss', $tripId, $dayNum, $stopName, $notes, $stopTime);
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Stop added!');
        } else {
            $stmt->close();
            setFlash('error', 'Failed to add stop. Please try again.');
        }
    } else {
        setFlash('error', implode(' ', $errors));
    }

    redirect("planner.php?trip_id={$tripId}");
}

/* ══════════════════════════════════════════════════
   3. Delete a stop (POST action=delete_stop)
══════════════════════════════════════════════════ */
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_stop') {
    $stopId  = (int)($_POST['stop_id']  ?? 0);
    $tripId  = (int)($_POST['trip_id']  ?? 0);

    // Verify ownership via JOIN (prevents IDOR)
    $stmt = $conn->prepare(
        'DELETE s FROM itinerary_stops s
         JOIN trips t ON s.trip_id = t.id
         WHERE s.id = ? AND t.user_id = ?'
    );
    $stmt->bind_param('ii', $stopId, $userId);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Stop removed.');
    redirect("planner.php?trip_id={$tripId}");
}

/* ══════════════════════════════════════════════════
   4. Load trip data (GET ?trip_id=X)
══════════════════════════════════════════════════ */
$tripId = (int)($_GET['trip_id'] ?? 0);

if ($loggedIn && $tripId > 0) {
    // Load trip (must belong to current user)
    $stmt = $conn->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $tripId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentTrip = $result->fetch_assoc();
    $stmt->close();

    if ($currentTrip) {
        // Load stops ordered by day then time
        $stmt = $conn->prepare(
            'SELECT * FROM itinerary_stops WHERE trip_id = ? ORDER BY day_number ASC, stop_time ASC'
        );
        $stmt->bind_param('i', $tripId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $tripStops[] = $row; }
        $stmt->close();
    } else {
        setFlash('error', 'Trip not found or access denied.');
        $tripId = 0;
    }
}

// Load all trips for the logged-in user (for the "switch trip" select)
if ($loggedIn) {
    $stmt = $conn->prepare('SELECT id, trip_name FROM trips WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $userTrips[] = $row; }
    $stmt->close();
}

// Compute total days for the current trip
$totalDays = $currentTrip ? tripDays($currentTrip['start_date'], $currentTrip['end_date']) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $currentTrip ? e($currentTrip['trip_name']) . ' — ' : '' ?>Planner — TripWeave</title>
  <meta name="description" content="Build your day-by-day travel itinerary with TripWeave's interactive planner.">
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
        <li class="nav-item"><a class="nav-link active" href="planner.php">Planner</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        <?php if ($loggedIn): ?>
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <?php endif; ?>
      </ul>
      <div class="ms-lg-3 d-flex gap-2 mt-3 mt-lg-0">
        <?php if ($loggedIn): ?>
          <span style="font-size:0.85rem;color:var(--gray-600);align-self:center;">
            <i class="bi bi-person-circle text-teal"></i> <?= e($_SESSION['username']) ?>
          </span>
          <a href="auth/logout.php" class="btn-outline-teal btn" style="font-size:0.85rem;padding:0.45rem 1.1rem;">Log Out</a>
        <?php else: ?>
          <a href="auth/login.php"    class="btn-outline-teal btn">Log In</a>
          <a href="auth/register.php" class="btn-teal btn">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ── Planner Hero Header ────────────────────────── -->
<div class="planner-header">
  <div class="container">
    <div class="d-flex align-items-center gap-3 mb-3">
      <a href="<?= $loggedIn ? 'dashboard.php' : 'index.php' ?>" style="color:rgba(255,255,255,0.6);font-size:0.85rem;">
        <i class="bi bi-arrow-left"></i> <?= $loggedIn ? 'Dashboard' : 'Home' ?>
      </a>
      <span style="color:rgba(255,255,255,0.3);">›</span>
      <span style="color:rgba(255,255,255,0.6);font-size:0.85rem;">Planner</span>
    </div>
    <h1 class="planner-title">
      <?= $currentTrip ? '✈️ ' . e($currentTrip['trip_name']) : '🗺 Itinerary Planner' ?>
    </h1>
    <p class="planner-sub">
      <?php if ($currentTrip): ?>
        <?= fmtDate($currentTrip['start_date']) ?> → <?= fmtDate($currentTrip['end_date']) ?>
        &nbsp;·&nbsp; <?= $totalDays ?> day<?= $totalDays !== 1 ? 's' : '' ?>
        &nbsp;·&nbsp; <?= count($tripStops) ?> stop<?= count($tripStops) !== 1 ? 's' : '' ?>
      <?php else: ?>
        Build your day-by-day adventure
      <?php endif; ?>
    </p>

    <!-- Switch trip dropdown (logged-in, has trips) -->
    <?php if ($loggedIn && count($userTrips) > 0): ?>
    <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
      <label style="color:rgba(255,255,255,0.6);font-size:0.82rem;" for="switchTrip">Switch trip:</label>
      <select id="switchTrip" class="form-select form-select-sm" style="width:auto;min-width:200px;border-radius:50px;border:1px solid rgba(255,255,255,0.25);background:rgba(255,255,255,0.1);color:white;font-size:0.85rem;"
        onchange="if(this.value) window.location='planner.php?trip_id='+this.value">
        <option value="">— Choose a trip —</option>
        <?php foreach ($userTrips as $ut): ?>
          <option value="<?= (int)$ut['id'] ?>" <?= ($tripId === (int)$ut['id']) ? 'selected' : '' ?>>
            <?= e($ut['trip_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <a href="planner.php" class="btn-ghost btn" style="font-size:0.8rem;padding:0.4rem 1rem;">
        <i class="bi bi-plus"></i> New Trip
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Flash Messages ─────────────────────────────── -->
<div class="container mt-4">
  <?php renderFlash(); ?>
  <?php if (!empty($errors)): ?>
    <div class="alert-flash error"><?= e(implode(' ', $errors)) ?></div>
  <?php endif; ?>
</div>

<!-- ── Main Planner Layout ────────────────────────── -->
<div class="container py-4 pb-5">

  <!-- Guest notice -->
  <?php if (!$loggedIn): ?>
  <div class="alert-flash info mb-4">
    <i class="bi bi-info-circle"></i>
    You're in <strong>preview mode</strong>. You can build an itinerary below, but
    <a href="auth/register.php" style="color:var(--teal);font-weight:600;">create a free account</a>
    to save your trips!
  </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- ════ LEFT COLUMN: Trip Setup + Stop Form ════ -->
    <div class="col-lg-8">

      <!-- ─── Create Trip Form (only shown when no trip selected) ─── -->
      <?php if (!$currentTrip): ?>
      <div class="trip-form-card mb-4">
        <div class="section-label">Step 1</div>
        <h2 style="font-family:'Inter',sans-serif;font-size:1.25rem;font-weight:700;margin-bottom:1.5rem;">
          Set Up Your Trip
        </h2>

        <?php if ($loggedIn): ?>
        <!-- Logged-in: form POSTs to create a real trip in DB -->
        <form id="tripHeaderForm" method="POST" action="planner.php" novalidate>
          <input type="hidden" name="action" value="create_trip">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-custom" for="trip_name">Trip Name</label>
              <input class="form-control-custom" type="text" id="trip_name" name="trip_name"
                placeholder="e.g. Japan Cherry Blossom Tour" required>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label-custom" for="start_date">Start Date</label>
              <div class="datepicker-wrap">
                <input class="form-control-custom" type="text" id="start_date" name="start_date"
                  placeholder="YYYY-MM-DD" required>
              </div>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label-custom" for="end_date">End Date</label>
              <div class="datepicker-wrap">
                <input class="form-control-custom" type="text" id="end_date" name="end_date"
                  placeholder="YYYY-MM-DD" required>
              </div>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-teal btn">
                <i class="bi bi-plus-circle"></i> Create Trip &amp; Start Planning
              </button>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Guest: JS-only preview mode -->
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label-custom" for="tripName">Trip Name</label>
            <input class="form-control-custom" type="text" id="tripName" placeholder="e.g. Greece Getaway">
          </div>
          <div class="col-md-6">
            <label class="form-label-custom" for="startDate">Start Date</label>
            <div class="datepicker-wrap">
              <input class="form-control-custom" type="text" id="startDate" placeholder="YYYY-MM-DD">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label-custom" for="endDate">End Date</label>
            <div class="datepicker-wrap">
              <input class="form-control-custom" type="text" id="endDate" placeholder="YYYY-MM-DD">
            </div>
          </div>
          <div class="col-12">
            <a href="auth/register.php" class="btn-teal btn">
              <i class="bi bi-person-plus"></i> Sign Up to Save This Trip
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- ─── Day Tabs ─── -->
      <div class="trip-form-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <div class="section-label">Day-by-Day View</div>
            <h3 style="font-family:'Inter',sans-serif;font-size:1.1rem;font-weight:700;margin:0;">
              Itinerary Stops
            </h3>
          </div>
          <?php if ($currentTrip): ?>
          <span style="font-size:0.82rem;color:var(--gray-400);">
            <?= $totalDays ?> day<?= $totalDays !== 1 ? 's' : '' ?> total
          </span>
          <?php endif; ?>
        </div>

        <!-- Day Tab Buttons -->
        <div id="dayTabGroup" class="day-tab-group" role="tablist" aria-label="Day selector"></div>

        <!-- Stops Container -->
        <div id="stopsContainer">
          <?php if ($currentTrip): ?>
            <?php
            // Group stops by day for display
            $stopsByDay = [];
            foreach ($tripStops as $s) {
                $stopsByDay[$s['day_number']][] = $s;
            }
            // Render stops for day 1 initially (JS will handle tab switching)
            $day1Stops = $stopsByDay[1] ?? [];
            if (empty($day1Stops)): ?>
              <div class="empty-stops">
                <i class="bi bi-map"></i>No stops yet for Day 1 — add your first stop below!
              </div>
            <?php else: ?>
              <?php foreach ($day1Stops as $s): ?>
              <div class="stop-item" id="dbstop-<?= (int)$s['id'] ?>">
                <span class="stop-time-badge"><?= e($s['stop_time'] ?: '—') ?></span>
                <div class="stop-info">
                  <div class="stop-name"><?= e($s['stop_name']) ?></div>
                  <?php if (!empty($s['notes'])): ?>
                  <div class="stop-notes"><?= e($s['notes']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="stop-actions">
                  <!-- View button -->
                  <button class="stop-btn edit tw-tooltip"
                    onclick="openDbStopModal('<?= e(addslashes($s['stop_name'])) ?>','<?= (int)$s['day_number'] ?>','<?= e($s['stop_time'] ?: '—') ?>','<?= e(addslashes($s['notes'])) ?>')"
                    aria-label="View stop details">
                    <i class="bi bi-eye"></i>
                    <span class="tooltip-text">View</span>
                  </button>
                  <!-- Delete button (form POST) -->
                  <form method="POST" action="planner.php" style="display:inline;" onsubmit="return confirm('Delete this stop?')">
                    <input type="hidden" name="action"  value="delete_stop">
                    <input type="hidden" name="stop_id" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="trip_id" value="<?= (int)$tripId ?>">
                    <button type="submit" class="stop-btn delete tw-tooltip" aria-label="Delete stop">
                      <i class="bi bi-trash"></i>
                      <span class="tooltip-text">Delete</span>
                    </button>
                  </form>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          <?php else: ?>
            <!-- Guest/preview mode — JS renders stops -->
            <div class="empty-stops" id="emptyStops">
              <i class="bi bi-map"></i>No stops yet — add your first stop below!
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ─── Add Stop Form ─── -->
      <?php if ($currentTrip || !$loggedIn): ?>
      <div class="trip-form-card">
        <div class="section-label">Add a Stop</div>
        <h3 style="font-family:'Inter',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">
          New Itinerary Stop
        </h3>

        <?php if ($loggedIn && $currentTrip): ?>
        <!-- Logged-in with a trip: real PHP POST -->
        <form id="stopForm" method="POST" action="planner.php" novalidate>
          <input type="hidden" name="action"  value="add_stop">
          <input type="hidden" name="trip_id" value="<?= (int)$tripId ?>">
          <input type="hidden" id="currentTripId" value="<?= (int)$tripId ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-custom" for="stopName">Stop / Place Name *</label>
              <input class="form-control-custom" type="text" id="stopName" name="stop_name"
                placeholder="e.g. Eiffel Tower" required>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-3">
              <label class="form-label-custom" for="dayNumber">Day *</label>
              <select class="form-control-custom" id="dayNumber" name="day_number" required>
                <?php for ($d = 1; $d <= $totalDays; $d++): ?>
                <option value="<?= $d ?>">Day <?= $d ?></option>
                <?php endfor; ?>
              </select>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-3">
              <label class="form-label-custom" for="stopTime">Time</label>
              <input class="form-control-custom" type="time" id="stopTime" name="stop_time">
            </div>
            <div class="col-12">
              <label class="form-label-custom" for="stopNotes">Notes (optional)</label>
              <textarea class="form-control-custom" id="stopNotes" name="notes" rows="2"
                placeholder="e.g. Book tickets in advance, arrive by 9am"></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-teal btn">
                <i class="bi bi-plus-circle"></i> Add Stop
              </button>
            </div>
          </div>
        </form>
        <?php else: ?>
        <!-- Guest preview mode: JS-only -->
        <form id="stopForm" novalidate>
          <input type="hidden" id="currentTripId" value="">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-custom" for="stopName">Stop / Place Name *</label>
              <input class="form-control-custom" type="text" id="stopName" name="stop_name"
                placeholder="e.g. Colosseum" required>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-3">
              <label class="form-label-custom" for="dayNumber">Day *</label>
              <input class="form-control-custom" type="number" id="dayNumber" name="day_number"
                value="1" min="1" max="30" required>
              <div class="form-error-msg"></div>
            </div>
            <div class="col-md-3">
              <label class="form-label-custom" for="stopTime">Time</label>
              <input class="form-control-custom" type="time" id="stopTime" name="stop_time">
            </div>
            <div class="col-12">
              <label class="form-label-custom" for="stopNotes">Notes (optional)</label>
              <textarea class="form-control-custom" id="stopNotes" name="notes" rows="2"
                placeholder="e.g. Sunset view, book tickets in advance"></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn-teal btn">
                <i class="bi bi-plus-circle"></i> Add Stop (Preview)
              </button>
            </div>
          </div>
        </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div><!-- /left col -->

    <!-- ════ RIGHT COLUMN: Live Summary Panel ════ -->
    <div class="col-lg-4">
      <div class="summary-panel">
        <h5><i class="bi bi-clipboard2-check me-2"></i>Trip Summary</h5>

        <div class="summary-item">
          <span class="summary-key">Trip Name</span>
          <span class="summary-val" id="summaryTripName">
            <?= $currentTrip ? e($currentTrip['trip_name']) : '—' ?>
          </span>
        </div>
        <div class="summary-item">
          <span class="summary-key">Dates</span>
          <span class="summary-val" id="summaryDates" style="font-size:0.82rem;">
            <?php if ($currentTrip): ?>
              <?= fmtDate($currentTrip['start_date']) ?> → <?= fmtDate($currentTrip['end_date']) ?>
            <?php else: ?>—<?php endif; ?>
          </span>
        </div>
        <div class="summary-item">
          <span class="summary-key">Total Days</span>
          <span class="summary-val summary-total" id="summaryDays">
            <?= $currentTrip ? $totalDays : '1' ?>
          </span>
        </div>
        <div class="summary-item">
          <span class="summary-key">Total Stops</span>
          <span class="summary-val summary-total" id="summaryStops">
            <?= count($tripStops) ?>
          </span>
        </div>

        <?php if ($currentTrip && count($tripStops) > 0): ?>
        <div style="margin-top:1.5rem;">
          <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:0.75rem;">
            All Stops
          </div>
          <?php foreach ($tripStops as $s): ?>
          <div style="display:flex;align-items:center;gap:0.6rem;padding:0.4rem 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:0.82rem;">
            <span style="background:rgba(255,255,255,0.12);border-radius:50px;padding:0.15rem 0.6rem;font-size:0.7rem;color:rgba(255,255,255,0.6);white-space:nowrap;">
              Day <?= (int)$s['day_number'] ?>
            </span>
            <span style="color:rgba(255,255,255,0.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= e($s['stop_name']) ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($loggedIn && $currentTrip): ?>
        <div style="margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid rgba(255,255,255,0.1);">
          <a href="dashboard.php" class="btn-ghost btn w-100 justify-content-center" style="font-size:0.85rem;">
            <i class="bi bi-grid-1x2"></i> View All Trips
          </a>
        </div>
        <?php elseif (!$loggedIn): ?>
        <div style="margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid rgba(255,255,255,0.1);">
          <a href="auth/register.php" class="btn-orange btn w-100 justify-content-center" style="font-size:0.85rem;">
            <i class="bi bi-save"></i> Save This Trip
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div><!-- /right col -->
  </div><!-- /row -->
</div><!-- /container -->

<!-- ════════════════════════════════════════════════
     STOP DETAIL MODAL
════════════════════════════════════════════════ -->
<div class="modal fade modal-custom" id="stopDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Stop Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h4 id="modal-stop-name" style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:0.75rem;"></h4>
        <div class="d-flex gap-3 mb-3">
          <span style="background:rgba(20,145,155,0.1);color:var(--teal);font-size:0.82rem;font-weight:600;padding:0.3rem 0.8rem;border-radius:50px;" id="modal-stop-day"></span>
          <span style="background:rgba(224,123,84,0.1);color:var(--orange);font-size:0.82rem;font-weight:600;padding:0.3rem 0.8rem;border-radius:50px;" id="modal-stop-time"></span>
        </div>
        <div style="background:var(--surface);border-radius:var(--radius-sm);padding:1rem;">
          <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--gray-400);margin-bottom:0.4rem;">Notes</div>
          <p id="modal-stop-notes" style="font-size:0.9rem;color:var(--gray-600);margin:0;"></p>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ -->
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
<script src="js/datepicker.js"></script>
<script src="js/validate.js"></script>
<script src="js/planner.js"></script>
<script>
/* ── Initialise date pickers ────────────────────── */
<?php if (!$currentTrip): ?>
// Start date picker (range mode: selecting start auto-links to end)
const startPicker = new TripWeaveDatePicker('#start_date, #startDate', {
  mode: 'single',
  minDate: new Date(),
  onChange(d) {
    // Update state for guest mode
    if (typeof state !== 'undefined') {
      state.startDate = d.toISOString().split('T')[0];
      onDateChange && onDateChange();
    }
    // Set end picker min date
    if (endPicker) endPicker.options.minDate = d;
  }
});
const endPicker = new TripWeaveDatePicker('#end_date, #endDate', {
  mode: 'single',
  minDate: new Date(),
  onChange(d) {
    if (typeof state !== 'undefined') {
      state.endDate = d.toISOString().split('T')[0];
      onDateChange && onDateChange();
    }
  }
});
<?php endif; ?>

/* ── DB stop modal (for server-rendered stops) ──── */
function openDbStopModal(name, day, time, notes) {
  document.getElementById('modal-stop-name').textContent  = name;
  document.getElementById('modal-stop-day').textContent   = 'Day ' + day;
  document.getElementById('modal-stop-time').textContent  = time;
  document.getElementById('modal-stop-notes').textContent = notes || 'No notes added.';
  new bootstrap.Modal(document.getElementById('stopDetailModal')).show();
}

/* ── Build day tabs from DB trip data ─────────────
   For DB-backed trips, override the JS planner state
   so the day tabs reflect the actual trip. ───────── */
<?php if ($currentTrip): ?>
(function() {
  // Override JS state with real DB data
  state.tripName  = <?= json_encode($currentTrip['trip_name']) ?>;
  state.startDate = <?= json_encode($currentTrip['start_date']) ?>;
  state.endDate   = <?= json_encode($currentTrip['end_date']) ?>;

  // Build all stops from DB into JS state for client-side day tabs
  const dbStops = <?= json_encode($tripStops) ?>;
  state.stops = dbStops.map(s => ({
    id:    '_db_' + s.id,
    day:   parseInt(s.day_number),
    time:  s.stop_time || '',
    name:  s.stop_name,
    notes: s.notes || ''
  }));

  buildDayTabs(<?= $totalDays ?>);
  updateSummary();

  // Override renderStops to emit the server-rendered HTML for current day
  // (JS state is only used for summary + tab switching; actual HTML
  //  uses the server-rendered delete forms for security)
  const origRender = renderStops;
  window.renderStops = function() {
    const filtered = state.stops.filter(s => s.day === state.activeDay);
    const container = document.getElementById('stopsContainer');
    if (!container) return;
    container.innerHTML = '';

    if (filtered.length === 0) {
      const el = document.createElement('div');
      el.className = 'empty-stops';
      el.innerHTML = '<i class="bi bi-map"></i>No stops for Day ' + state.activeDay + '.';
      container.appendChild(el);
    } else {
      filtered.sort((a,b) => a.time.localeCompare(b.time)).forEach((stop, i) => {
        const dbId = stop.id.replace('_db_','');
        const item = document.createElement('div');
        item.className = 'stop-item';
        item.style.animationDelay = (i * 0.06) + 's';
        item.innerHTML = `
          <span class="stop-time-badge">${escapeHtml(stop.time || '—')}</span>
          <div class="stop-info">
            <div class="stop-name">${escapeHtml(stop.name)}</div>
            ${stop.notes ? '<div class="stop-notes">' + escapeHtml(stop.notes) + '</div>' : ''}
          </div>
          <div class="stop-actions">
            <button class="stop-btn edit tw-tooltip"
              onclick="openDbStopModal('${escapeHtml(stop.name)}','${stop.day}','${escapeHtml(stop.time||'—')}','${escapeHtml(stop.notes)}')"
              aria-label="View details">
              <i class="bi bi-eye"></i><span class="tooltip-text">View</span>
            </button>
            <form method="POST" action="planner.php" style="display:inline;" onsubmit="return confirm('Delete this stop?')">
              <input type="hidden" name="action"  value="delete_stop">
              <input type="hidden" name="stop_id" value="${dbId}">
              <input type="hidden" name="trip_id" value="<?= (int)$tripId ?>">
              <button type="submit" class="stop-btn delete tw-tooltip" aria-label="Delete">
                <i class="bi bi-trash"></i><span class="tooltip-text">Delete</span>
              </button>
            </form>
          </div>`;
        container.appendChild(item);
      });
    }
    updateSummary();
  };

  renderStops();
})();
<?php endif; ?>
</script>
</body>
</html>
