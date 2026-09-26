/**
 * TripWeave — planner.js
 * Handles all client-side itinerary planner interactivity:
 *  • Dynamic stop add/remove/reorder
 *  • Live trip summary updates
 *  • Day-tab switching
 *  • Form validation
 *  • Stop detail modal
 */

// ── State ────────────────────────────────────────────
const state = {
  stops: [],        // [{id, day, time, name, notes}, ...]
  tripName: '',
  startDate: null,
  endDate: null,
  activeDay: 1,
};

// ── DOM refs ─────────────────────────────────────────
const stopsContainer = document.getElementById('stopsContainer');
const dayTabGroup    = document.getElementById('dayTabGroup');
const summaryStops   = document.getElementById('summaryStops');
const summaryDays    = document.getElementById('summaryDays');
const summaryTrip    = document.getElementById('summaryTripName');
const summaryDates   = document.getElementById('summaryDates');
const emptyStops     = document.getElementById('emptyStops');
const stopForm       = document.getElementById('stopForm');
const tripNameEl     = document.getElementById('tripName');
const startDateEl    = document.getElementById('startDate');
const endDateEl      = document.getElementById('endDate');

// ── Helpers ───────────────────────────────────────────
function generateId() {
  return '_' + Math.random().toString(36).slice(2, 9);
}

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str + 'T00:00:00');
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function daysBetween(start, end) {
  if (!start || !end) return 1;
  const s = new Date(start + 'T00:00:00');
  const e = new Date(end   + 'T00:00:00');
  const diff = Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1;
  return diff > 0 ? diff : 1;
}

// ── Update live summary panel ─────────────────────────
function updateSummary() {
  if (summaryTrip)   summaryTrip.textContent   = state.tripName || '—';
  if (summaryStops)  summaryStops.textContent  = state.stops.length;
  if (summaryDays)   summaryDays.textContent   = daysBetween(state.startDate, state.endDate);
  if (summaryDates) {
    const s = formatDate(state.startDate);
    const e = formatDate(state.endDate);
    summaryDates.textContent = (state.startDate && state.endDate) ? `${s} → ${e}` : '—';
  }
}

// ── Render stop list ──────────────────────────────────
function renderStops() {
  if (!stopsContainer) return;
  const filtered = state.stops.filter(s => s.day === state.activeDay);

  stopsContainer.innerHTML = '';

  if (filtered.length === 0) {
    const el = document.createElement('div');
    el.className = 'empty-stops';
    el.innerHTML = '<i class="bi bi-map"></i>No stops yet — add your first stop below!';
    stopsContainer.appendChild(el);
    return;
  }

  // Sort by time
  filtered.sort((a, b) => a.time.localeCompare(b.time));

  filtered.forEach((stop, idx) => {
    const item = document.createElement('div');
    item.className = 'stop-item';
    item.dataset.id = stop.id;
    item.innerHTML = `
      <span class="stop-time-badge">${stop.time || '—'}</span>
      <div class="stop-info">
        <div class="stop-name">${escapeHtml(stop.name)}</div>
        ${stop.notes ? `<div class="stop-notes">${escapeHtml(stop.notes)}</div>` : ''}
      </div>
      <div class="stop-actions">
        <button class="stop-btn edit tw-tooltip" data-id="${stop.id}" aria-label="View details">
          <i class="bi bi-eye"></i>
          <span class="tooltip-text">View</span>
        </button>
        <button class="stop-btn delete tw-tooltip" data-id="${stop.id}" aria-label="Delete stop">
          <i class="bi bi-trash"></i>
          <span class="tooltip-text">Delete</span>
        </button>
      </div>`;

    // Stagger animation
    item.style.animationDelay = `${idx * 0.06}s`;

    // Delete handler
    item.querySelector('.stop-btn.delete').addEventListener('click', () => {
      deleteStop(stop.id);
    });
    // View/Edit handler
    item.querySelector('.stop-btn.edit').addEventListener('click', () => {
      openStopModal(stop);
    });

    stopsContainer.appendChild(item);
  });

  updateSummary();
}

// ── Escape HTML to prevent XSS in DOM rendering ───────
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
            .replace(/'/g,'&#39;');
}

// ── Add stop ──────────────────────────────────────────
function addStop(day, time, name, notes) {
  const stop = { id: generateId(), day, time, name, notes };
  state.stops.push(stop);
  renderStops();
  updateSummary();
  return stop;
}

// ── Delete stop ───────────────────────────────────────
function deleteStop(id) {
  const item = stopsContainer.querySelector(`[data-id="${id}"]`);
  if (item) {
    item.style.transition = 'opacity 0.25s, transform 0.25s';
    item.style.opacity = '0';
    item.style.transform = 'translateX(20px)';
    setTimeout(() => {
      state.stops = state.stops.filter(s => s.id !== id);
      renderStops();
      updateSummary();
    }, 260);
  }
}

// ── Stop detail modal ─────────────────────────────────
function openStopModal(stop) {
  const modal = document.getElementById('stopDetailModal');
  if (!modal) return;
  modal.querySelector('#modal-stop-name').textContent  = stop.name;
  modal.querySelector('#modal-stop-day').textContent   = `Day ${stop.day}`;
  modal.querySelector('#modal-stop-time').textContent  = stop.time || '—';
  modal.querySelector('#modal-stop-notes').textContent = stop.notes || 'No notes added.';
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}

// ── Day tabs ──────────────────────────────────────────
function buildDayTabs(numDays) {
  if (!dayTabGroup) return;
  dayTabGroup.innerHTML = '';
  for (let d = 1; d <= numDays; d++) {
    const tab = document.createElement('button');
    tab.type = 'button';
    tab.className = 'day-tab' + (d === state.activeDay ? ' active' : '');
    tab.textContent = `Day ${d}`;
    tab.dataset.day  = d;
    tab.addEventListener('click', () => {
      document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      state.activeDay = d;
      renderStops();
    });
    dayTabGroup.appendChild(tab);
  }
}

// ── Listen for date changes to rebuild tabs ───────────
function onDateChange() {
  if (startDateEl) state.startDate = startDateEl.value;
  if (endDateEl)   state.endDate   = endDateEl.value;
  const num = daysBetween(state.startDate, state.endDate);
  buildDayTabs(Math.min(num, 30)); // cap at 30 days
  updateSummary();
}

if (startDateEl) startDateEl.addEventListener('change', onDateChange);
if (endDateEl)   endDateEl.addEventListener('change', onDateChange);
if (tripNameEl)  tripNameEl.addEventListener('input', () => {
  state.tripName = tripNameEl.value;
  updateSummary();
});

// ── Stop Form submission ───────────────────────────────
if (stopForm) {
  stopForm.addEventListener('submit', function (e) {
    // We intercept here only for pure-client-side preview;
    // the actual POST to PHP is handled on planner.php's form submission.
    // (For logged-in users, the form submits to PHP directly.)
    // However, if trip_id is present (existing trip), we also add to state for live UI.
    const stopName  = document.getElementById('stopName');
    const stopTime  = document.getElementById('stopTime');
    const stopNotes = document.getElementById('stopNotes');
    const dayNum    = document.getElementById('dayNumber');

    let valid = true;

    // Client-side validation
    if (!stopName || !stopName.value.trim()) {
      showError(stopName, 'Stop name is required.');
      valid = false;
    } else { clearError(stopName); }

    if (!dayNum || !dayNum.value) {
      showError(dayNum, 'Day number is required.');
      valid = false;
    } else { clearError(dayNum); }

    if (!valid) { e.preventDefault(); return; }

    // If we're in "preview" mode (no trip ID yet), just show in UI
    const tripIdField = document.getElementById('currentTripId');
    if (tripIdField && !tripIdField.value) {
      e.preventDefault();
      addStop(
        parseInt(dayNum.value),
        stopTime ? stopTime.value : '',
        stopName.value.trim(),
        stopNotes ? stopNotes.value.trim() : ''
      );
      stopForm.reset();
      if (dayNum) dayNum.value = state.activeDay;
    }
    // Otherwise let form POST normally to PHP
  });
}

// ── Client-side validation helpers ────────────────────
function showError(el, msg) {
  if (!el) return;
  el.classList.add('error-field');
  let err = el.parentElement.querySelector('.form-error-msg');
  if (!err) {
    err = document.createElement('div');
    err.className = 'form-error-msg';
    el.parentElement.appendChild(err);
  }
  err.textContent = msg;
  err.classList.add('visible');
}
function clearError(el) {
  if (!el) return;
  el.classList.remove('error-field');
  const err = el.parentElement.querySelector('.form-error-msg');
  if (err) err.classList.remove('visible');
}

// ── Trip header form validation ────────────────────────
const tripHeaderForm = document.getElementById('tripHeaderForm');
if (tripHeaderForm) {
  tripHeaderForm.addEventListener('submit', function(e) {
    let valid = true;
    const name  = document.getElementById('trip_name');
    const start = document.getElementById('start_date');
    const end   = document.getElementById('end_date');

    if (!name || !name.value.trim()) {
      showError(name, 'Trip name is required.'); valid = false;
    } else { clearError(name); }

    if (!start || !start.value) {
      showError(start, 'Start date is required.'); valid = false;
    } else { clearError(start); }

    if (!end || !end.value) {
      showError(end, 'End date is required.'); valid = false;
    } else if (new Date(end.value) < new Date(start.value)) {
      showError(end, 'End date must be after start date.'); valid = false;
    } else { clearError(end); clearError(start); }

    if (!valid) e.preventDefault();
  });
}

// ── Initialise ────────────────────────────────────────
(function init() {
  buildDayTabs(1);
  renderStops();
  updateSummary();
  // Pre-populate day number field with active day
  const dayNum = document.getElementById('dayNumber');
  if (dayNum) dayNum.value = state.activeDay;
})();
