/**
 * TripWeave — main.js
 * Global JS: scroll progress, back-to-top, smooth scroll,
 * navbar shrink, hover tooltips, animation on scroll (AOS-lite).
 */

/* ── Scroll Progress Bar ───────────────────────────── */
(function initScrollProgress() {
  const bar = document.createElement('div');
  bar.className = 'scroll-progress';
  bar.id = 'scrollProgress';
  document.body.prepend(bar);

  window.addEventListener('scroll', () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const progress  = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    bar.style.width = progress + '%';
  }, { passive: true });
})();

/* ── Back-to-Top Button ────────────────────────────── */
(function initBackToTop() {
  const btn = document.createElement('button');
  btn.className = 'back-to-top';
  btn.id  = 'backToTop';
  btn.setAttribute('aria-label', 'Back to top');
  btn.innerHTML = '<i class="bi bi-chevron-up"></i>';
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();

/* ── Smooth Scrolling for Anchor Links ────────────── */
document.querySelectorAll('a[href^="#"]').forEach(link => {
  link.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (!target) return;
    e.preventDefault();
    const offset = 72; // navbar height
    const top = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top, behavior: 'smooth' });
  });
});

/* ── Navbar Scroll Behaviour ───────────────────────── */
(function initNavbar() {
  const navbar = document.querySelector('.navbar-custom');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar.style.padding = '0.45rem 0';
      navbar.style.boxShadow = '0 4px 24px rgba(0,0,0,0.12)';
    } else {
      navbar.style.padding = '0.75rem 0';
      navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.08)';
    }
  }, { passive: true });
})();

/* ── Intersection Observer — animate on scroll ─────── */
(function initAOS() {
  const els = document.querySelectorAll('.aos-item');
  if (!els.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-slideUp');
        entry.target.style.opacity = '1';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  els.forEach(el => {
    el.style.opacity = '0';
    observer.observe(el);
  });
})();

/* ── Flash message auto-dismiss ─────────────────────── */
document.querySelectorAll('.alert-flash').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s ease';
    alert.style.opacity    = '0';
    setTimeout(() => alert.remove(), 500);
  }, 4500);
});

/* ── Active nav link highlight ──────────────────────── */
(function setActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.navbar-custom .nav-link').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href === path || (path === '' && href === 'index.php')) {
      link.classList.add('active');
    }
  });
})();
