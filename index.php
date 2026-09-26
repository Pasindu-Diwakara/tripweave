<?php
/**
 * TripWeave — index.php
 * Home / Landing Page
 * Sections: Hero, Features, Destinations, How It Works, CTA
 */
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TripWeave — Weave Your Perfect Journey</title>
  <meta name="description" content="TripWeave is the smart travel itinerary planner that helps you design day-by-day adventures, organize stops, and travel with confidence.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ════════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top" id="mainNav">
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
        <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#destinations">Destinations</a></li>
        <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="planner.php">Planner</a></li>
      </ul>
      <div class="ms-lg-3 d-flex gap-2 mt-3 mt-lg-0">
        <?php if (isLoggedIn()): ?>
          <a href="dashboard.php" class="btn-teal btn">
            <i class="bi bi-grid-1x2"></i> Dashboard
          </a>
          <a href="auth/logout.php" class="btn-outline-teal btn">Log Out</a>
        <?php else: ?>
          <a href="auth/login.php"    class="btn-outline-teal btn">Log In</a>
          <a href="auth/register.php" class="btn-teal btn">Sign Up Free</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ════════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════ -->
<section class="hero-section" id="home">
  <div class="hero-bg-pattern"></div>
  <div class="hero-grid-lines"></div>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center">
      <!-- Left: Copy -->
      <div class="col-lg-6 animate-slideUp">
        <div class="section-label" style="color:var(--sand);">✦ Your Journey Starts Here</div>
        <h1 class="hero-title">
          Weave Your<br>
          <span class="highlight">Perfect Journey</span><br>
          Day by Day
        </h1>
        <p class="hero-subtitle">
          TripWeave turns travel dreams into beautifully organized itineraries.
          Pick destinations, schedule stops, and travel with total confidence —
          all in one place.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="auth/register.php" class="btn-orange btn" id="heroCtaSignup">
            <i class="bi bi-compass"></i> Start Planning Free
          </a>
          <a href="#how-it-works" class="btn-ghost btn" id="heroCtaLearnMore">
            <i class="bi bi-play-circle"></i> See How It Works
          </a>
        </div>

        <!-- Stats -->
        <div class="hero-stats animate-slideUp stagger-2">
          <div class="stat-item">
            <div class="stat-num">50+</div>
            <div class="stat-label">Destinations</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">Day-by-Day</div>
            <div class="stat-label">Planning</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">100%</div>
            <div class="stat-label">Free to Use</div>
          </div>
        </div>
      </div>

      <!-- Right: Visual card -->
      <div class="col-lg-6 hero-visual animate-slideUp stagger-3">
        <div class="hero-map-card">
          <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:rgba(255,255,255,0.45);margin-bottom:1.25rem;">
            📍 Sample Itinerary — Kyoto, Japan
          </div>

          <!-- Route stops -->
          <?php
          $stops = [
            ['time' => '08:00', 'name' => 'Fushimi Inari Shrine',    'sub' => 'Day 1 · Morning'],
            ['time' => '12:30', 'name' => 'Nishiki Market Lunch',    'sub' => 'Day 1 · Afternoon'],
            ['time' => '15:00', 'name' => 'Gion Historic District',  'sub' => 'Day 1 · Evening'],
            ['time' => '09:00', 'name' => 'Arashiyama Bamboo Grove', 'sub' => 'Day 2 · Morning'],
          ];
          foreach ($stops as $i => $s): ?>
          <div class="d-flex align-items-center" style="margin-bottom:<?= $i < count($stops)-1 ? '0' : '' ?>;">
            <div style="display:flex;flex-direction:column;align-items:center;margin-right:0;width:22px;">
              <div class="route-dot <?= $i===0 ? 'start' : ($i===count($stops)-1 ? 'end' : 'mid') ?>"></div>
              <?php if ($i < count($stops)-1): ?><div class="route-line"></div><?php endif; ?>
            </div>
            <div style="margin-left:0.85rem;padding-bottom:<?= $i < count($stops)-1 ? '0.75rem' : '0' ?>;">
              <div class="route-label"><?= e($s['name']) ?></div>
              <div class="route-sub"><?= e($s['sub']) ?> · <?= e($s['time']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>

          <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.1);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.78rem;color:rgba(255,255,255,0.5);">2 days · 4 stops</span>
            <a href="planner.php" style="background:var(--teal);color:white;font-size:0.78rem;font-weight:700;padding:0.4rem 1rem;border-radius:50px;text-decoration:none;">
              Build Yours →
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scroll hint -->
  <div style="position:absolute;bottom:2rem;left:50%;transform:translateX(-50%);text-align:center;z-index:2;">
    <a href="#features" style="color:rgba(255,255,255,0.4);font-size:0.75rem;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;">
      <div style="animation:float 2s ease-in-out infinite;"><i class="bi bi-chevron-down" style="font-size:1.2rem;"></i></div>
    </a>
  </div>
</section>

<!-- ════════════════════════════════════════════════
     FEATURES SECTION
════════════════════════════════════════════════ -->
<section class="features-section" id="features">
  <div class="container">
    <div class="text-center mb-5 aos-item">
      <div class="section-label">What We Offer</div>
      <h2 class="section-title">Everything You Need to Travel Smarter</h2>
      <p class="lead-text mx-auto">TripWeave gives you intuitive tools to plan, organize, and enjoy every moment of your trip.</p>
    </div>

    <div class="row g-4">
      <?php
      $features = [
        ['icon'=>'bi-calendar3',        'color'=>'teal',  'title'=>'Day-by-Day Scheduling',
         'desc'=>'Break your trip into days. Add stops with custom times and notes for a perfectly organized journey.'],
        ['icon'=>'bi-geo-alt',           'color'=>'sand',  'title'=>'Smart Destination Cards',
         'desc'=>'Browse curated destinations, explore highlights, and add them to your itinerary in one click.'],
        ['icon'=>'bi-phone',             'color'=>'coral', 'title'=>'Fully Responsive',
         'desc'=>'Access your plans on any device. TripWeave looks great on desktop, tablet, and mobile.'],
        ['icon'=>'bi-shield-lock',       'color'=>'dark',  'title'=>'Secure & Private',
         'desc'=>'Your trips are tied to your account with secure authentication. Only you can see your itineraries.'],
        ['icon'=>'bi-arrow-left-right',  'color'=>'teal',  'title'=>'Live Itinerary Updates',
         'desc'=>'Add, reorder, or delete stops and watch your trip summary update in real time — no page reload.'],
        ['icon'=>'bi-cloud-check',       'color'=>'sand',  'title'=>'Save & Return',
         'desc'=>'All trips saved to your account. Pick up planning exactly where you left off, any time.'],
      ];
      foreach ($features as $f): ?>
      <div class="col-md-6 col-lg-4 aos-item">
        <div class="card-custom h-100">
          <div class="card-body-custom">
            <div class="feature-icon-wrap <?= $f['color'] ?>">
              <i class="bi <?= $f['icon'] ?>"></i>
            </div>
            <div class="feature-title"><?= e($f['title']) ?></div>
            <p class="feature-desc"><?= e($f['desc']) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════
     DESTINATIONS SECTION
════════════════════════════════════════════════ -->
<section class="destinations-section" id="destinations">
  <div class="container">
    <div class="d-flex flex-wrap align-items-end justify-content-between mb-5 gap-3">
      <div class="aos-item">
        <div class="section-label">Inspire Your Next Trip</div>
        <h2 class="section-title mb-0">Popular Destinations</h2>
      </div>
      <a href="planner.php" class="btn-outline-teal btn aos-item">
        Plan a Custom Trip <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php
      // Original TripWeave destination cards — using gradient overlays + emoji backgrounds
      // (Replace with actual photos in /images/ for a production site)
      $destinations = [
        ['name'=>'Kyoto, Japan',         'tag'=>'Culture',    'grad'=>'linear-gradient(135deg,#1A1A2E,#0D4F5C)',  'icon'=>'🏯', 'stops'=>'12 popular stops'],
        ['name'=>'Santorini, Greece',    'tag'=>'Beach',      'grad'=>'linear-gradient(135deg,#1e3a5f,#3a7bd5)',  'icon'=>'🌊', 'stops'=>'9 popular stops'],
        ['name'=>'Banff, Canada',        'tag'=>'Nature',     'grad'=>'linear-gradient(135deg,#1a3a2a,#2d6a4f)',  'icon'=>'🏔️', 'stops'=>'11 popular stops'],
        ['name'=>'Marrakech, Morocco',   'tag'=>'Adventure',  'grad'=>'linear-gradient(135deg,#3d1f00,#c47a1d)',  'icon'=>'🕌', 'stops'=>'8 popular stops'],
        ['name'=>'Amalfi Coast, Italy',  'tag'=>'Romance',    'grad'=>'linear-gradient(135deg,#0a3a4a,#1a7a6a)',  'icon'=>'🍋', 'stops'=>'10 popular stops'],
        ['name'=>'Patagonia, Chile',     'tag'=>'Wilderness', 'grad'=>'linear-gradient(135deg,#2a1a3a,#5a3a7a)',  'icon'=>'🦅', 'stops'=>'7 popular stops'],
      ];
      foreach ($destinations as $d): ?>
      <div class="col-md-6 col-lg-4 aos-item">
        <div class="dest-card" role="button" tabindex="0"
          onclick="openDestModal('<?= e($d['name']) ?>', '<?= e($d['tag']) ?>', '<?= e($d['stops']) ?>')"
          aria-label="View details for <?= e($d['name']) ?>">

          <!-- Gradient background with emoji icon -->
          <div style="background:<?= $d['grad'] ?>;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;">
            <?= $d['icon'] ?>
          </div>

          <div class="dest-card-overlay">
            <span class="card-badge"><?= e($d['tag']) ?></span>
            <div>
              <div class="dest-name"><?= e($d['name']) ?></div>
              <div class="dest-sub">
                <i class="bi bi-geo-alt-fill"></i> <?= e($d['stops']) ?>
              </div>
              <div class="dest-card-detail">
                <a href="planner.php" style="color:var(--sand);font-size:0.82rem;font-weight:600;text-decoration:none;">
                  Add to Itinerary <i class="bi bi-plus-circle"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════
     HOW IT WORKS SECTION
════════════════════════════════════════════════ -->
<section style="padding:6rem 0;background:var(--surface);" id="how-it-works">
  <div class="container">
    <div class="text-center mb-5 aos-item">
      <div class="section-label">Get Started in Minutes</div>
      <h2 class="section-title">How TripWeave Works</h2>
    </div>
    <div class="row g-4 align-items-center">
      <?php
      $steps = [
        ['num'=>'01','title'=>'Create Your Account','desc'=>'Sign up free in under a minute. No credit card needed.','icon'=>'bi-person-plus'],
        ['num'=>'02','title'=>'Name Your Trip','desc'=>'Give your adventure a name and pick your travel dates.','icon'=>'bi-journal-text'],
        ['num'=>'03','title'=>'Add Stops Day by Day','desc'=>'Plan each day with stops, times, and personal notes.','icon'=>'bi-pin-map'],
        ['num'=>'04','title'=>'Travel & Enjoy','desc'=>'Your itinerary is always with you — on any device, anytime.','icon'=>'bi-airplane-fill'],
      ];
      foreach ($steps as $i => $step): ?>
      <div class="col-md-6 col-lg-3 text-center aos-item" style="animation-delay:<?= $i*0.1 ?>s;">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-dark));display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;box-shadow:0 8px 24px rgba(20,145,155,0.35);">
          <i class="bi <?= $step['icon'] ?>" style="color:white;font-size:1.6rem;"></i>
        </div>
        <div style="font-family:'Playfair Display',serif;font-size:0.8rem;color:var(--orange);font-weight:700;letter-spacing:0.1em;margin-bottom:0.4rem;"><?= $step['num'] ?></div>
        <h4 style="font-family:'Inter',sans-serif;font-size:1rem;font-weight:700;margin-bottom:0.5rem;"><?= e($step['title']) ?></h4>
        <p style="font-size:0.88rem;color:var(--gray-600);"><?= e($step['desc']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════
     CTA SECTION
════════════════════════════════════════════════ -->
<section style="background:linear-gradient(135deg,var(--teal-dark),var(--dark));padding:5rem 0;text-align:center;">
  <div class="container">
    <div class="aos-item" style="color:var(--sand);font-size:2rem;margin-bottom:1rem;">✈️</div>
    <h2 style="font-family:'Playfair Display',serif;color:white;font-size:clamp(1.75rem,4vw,3rem);margin-bottom:1rem;" class="aos-item">
      Ready to Weave Your Adventure?
    </h2>
    <p style="color:rgba(255,255,255,0.65);max-width:480px;margin:0 auto 2.5rem;" class="aos-item">
      Join TripWeave today and turn your travel wishlist into a real, organized plan.
    </p>
    <div class="d-flex justify-content-center flex-wrap gap-3 aos-item">
      <a href="auth/register.php" class="btn-orange btn" id="footerCtaSignup">
        <i class="bi bi-compass"></i> Start Planning Free
      </a>
      <a href="contact.php" class="btn-ghost btn">
        <i class="bi bi-envelope"></i> Get in Touch
      </a>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ -->
<footer class="footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 footer-brand">
        <div class="navbar-brand mb-3" style="display:inline-flex;">
          <div class="logo-mark">T</div>
          <span class="brand-text ms-2">TripWeave</span>
        </div>
        <p style="font-size:0.88rem;color:rgba(255,255,255,0.55);max-width:300px;line-height:1.7;">
          Helping travellers plan smarter, explore further, and remember every moment — one itinerary at a time.
        </p>
        <div class="mt-3">
          <a href="#" class="social-icon" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2 offset-lg-1">
        <div class="footer-heading">Navigate</div>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="planner.php">Planner</a></li>
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <div class="footer-heading">Account</div>
        <ul class="footer-links">
          <li><a href="auth/register.php">Sign Up</a></li>
          <li><a href="auth/login.php">Log In</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <div class="footer-heading">Quick Plan</div>
        <p style="font-size:0.85rem;color:rgba(255,255,255,0.5);margin-bottom:1rem;">Start your next adventure right now.</p>
        <a href="planner.php" class="btn-teal btn" style="font-size:0.85rem;">
          <i class="bi bi-plus-circle"></i> New Itinerary
        </a>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p class="footer-copy mb-0">&copy; <?= date('Y') ?> TripWeave. Built for ICT2206 Web Technologies.</p>
      <p class="footer-copy mb-0">Crafted with <span style="color:var(--orange);">♥</span> and vanilla PHP</p>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════════════
     DESTINATION MODAL
════════════════════════════════════════════════ -->
<div class="modal fade modal-custom" id="destModal" tabindex="-1" aria-labelledby="destModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="destModalLabel">Destination Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h4 id="modalDestName" style="font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:0.25rem;"></h4>
        <p style="color:var(--teal);font-size:0.85rem;font-weight:600;margin-bottom:1rem;" id="modalDestTag"></p>
        <p style="font-size:0.9rem;color:var(--gray-600);margin-bottom:1.25rem;" id="modalDestStops"></p>
        <p style="font-size:0.88rem;color:var(--gray-600);">
          TripWeave lets you build a custom itinerary for any destination. Add stops, set times, and keep notes for each day of your journey.
        </p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="planner.php" class="btn-teal btn">
          <i class="bi bi-plus-circle"></i> Plan This Trip
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
/* ── Flash message (logged-out redirect) ────────── */
<?php renderFlash(); ?>

/* ── Destination modal handler ───────────────────── */
function openDestModal(name, tag, stops) {
  document.getElementById('modalDestName').textContent  = name;
  document.getElementById('modalDestTag').textContent   = '🏷 ' + tag;
  document.getElementById('modalDestStops').textContent = '📍 ' + stops;
  new bootstrap.Modal(document.getElementById('destModal')).show();
}

/* ── Keyboard: open dest modal on Enter ─────────── */
document.querySelectorAll('.dest-card').forEach(card => {
  card.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') card.click();
  });
});
</script>
</body>
</html>
