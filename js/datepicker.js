/**
 * TripWeave — datepicker.js
 * Lightweight, custom vanilla-JS date picker.
 * Supports single date and date-range (start/end) mode.
 * Usage:
 *   new TripWeaveDatePicker('#myInput', { mode: 'single' | 'range', onChange: fn })
 */

class TripWeaveDatePicker {
  /**
   * @param {string|HTMLElement} inputEl - The input element or selector
   * @param {object} options
   * @param {'single'|'range'} options.mode
   * @param {function} options.onChange  - Called with (date|{start,end}) on selection
   * @param {Date}   options.minDate    - Earliest selectable date
   * @param {string} options.linkedEnd  - selector for the end-date input (range mode)
   */
  constructor(inputEl, options = {}) {
    this.input   = typeof inputEl === 'string' ? document.querySelector(inputEl) : inputEl;
    if (!this.input) return;

    this.options  = Object.assign({ mode: 'single', onChange: null, minDate: null }, options);
    this.today    = new Date(); this.today.setHours(0,0,0,0);
    this.viewDate = new Date(this.today.getFullYear(), this.today.getMonth(), 1);
    this.selected = null; // Date or {start, end}
    this.isOpen   = false;

    this._buildPopup();
    this._attachListeners();
    this._render();
  }

  /* ── DOM Construction ────────────────────────────── */
  _buildPopup() {
    this.wrap  = this.input.closest('.datepicker-wrap') || this._wrapInput();
    this.popup = document.createElement('div');
    this.popup.className = 'datepicker-popup';
    this.popup.innerHTML = `
      <div class="dp-header">
        <button class="dp-nav" id="dp-prev-${this._uid()}" aria-label="Previous month">&#8249;</button>
        <span class="dp-month-year"></span>
        <button class="dp-nav" id="dp-next-${this._uid()}" aria-label="Next month">&#8250;</button>
      </div>
      <div class="dp-grid dp-names"></div>
      <div class="dp-grid dp-days"></div>`;
    this.wrap.appendChild(this.popup);

    // Day-name row
    ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(d => {
      const el = document.createElement('div');
      el.className = 'dp-day-name'; el.textContent = d;
      this.popup.querySelector('.dp-names').appendChild(el);
    });

    this.headerLabel = this.popup.querySelector('.dp-month-year');
    this.daysGrid    = this.popup.querySelector('.dp-days');

    this.popup.querySelector('[id^="dp-prev"]').addEventListener('click', () => {
      this.viewDate.setMonth(this.viewDate.getMonth() - 1); this._render();
    });
    this.popup.querySelector('[id^="dp-next"]').addEventListener('click', () => {
      this.viewDate.setMonth(this.viewDate.getMonth() + 1); this._render();
    });
  }

  _wrapInput() {
    const wrap = document.createElement('div');
    wrap.className = 'datepicker-wrap';
    wrap.style.position = 'relative';
    this.input.parentNode.insertBefore(wrap, this.input);
    wrap.appendChild(this.input);
    return wrap;
  }

  /* ── Rendering ───────────────────────────────────── */
  _render() {
    const year  = this.viewDate.getFullYear();
    const month = this.viewDate.getMonth();
    const months = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

    this.headerLabel.textContent = `${months[month]} ${year}`;
    this.daysGrid.innerHTML = '';

    // First day of month (0=Sun)
    const firstDay = new Date(year, month, 1).getDay();
    // Days in month
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    // Days in prev month
    const prevDays = new Date(year, month, 0).getDate();

    // Blank cells from previous month
    for (let i = firstDay - 1; i >= 0; i--) {
      this._dayCell(prevDays - i, true, false, false, false);
    }
    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(year, month, d);
      const isToday    = this._sameDay(date, this.today);
      const isDisabled = this.options.minDate && date < this.options.minDate;
      const isSelected = this._isSelected(date);
      const inRange    = this._inRange(date);
      this._dayCell(d, false, isToday, isSelected, inRange, isDisabled, date);
    }
    // Fill trailing cells
    const total = firstDay + daysInMonth;
    const trailing = 7 - (total % 7 === 0 ? 7 : total % 7);
    for (let d = 1; d <= trailing; d++) {
      this._dayCell(d, true, false, false, false);
    }
  }

  _dayCell(num, otherMonth, isToday, isSelected, inRange, isDisabled = false, date = null) {
    const el = document.createElement('div');
    el.className = 'dp-day' +
      (otherMonth  ? ' other-month' : '') +
      (isToday     ? ' today'       : '') +
      (isSelected  ? ' selected'    : '') +
      (inRange     ? ' in-range'    : '') +
      (isDisabled  ? ' disabled'    : '');
    el.textContent = num;
    if (date && !isDisabled && !otherMonth) {
      el.addEventListener('click', () => this._selectDate(date));
    }
    this.daysGrid.appendChild(el);
  }

  /* ── Selection Logic ─────────────────────────────── */
  _selectDate(date) {
    if (this.options.mode === 'single') {
      this.selected = date;
      this.input.value = this._fmt(date);
      if (this.options.onChange) this.options.onChange(date);
      this.close();
    } else {
      // Range mode
      if (!this.selected || (this.selected.start && this.selected.end)) {
        // Start fresh
        this.selected = { start: date, end: null };
        this.input.value = this._fmt(date);
      } else if (this.selected.start && !this.selected.end) {
        if (date < this.selected.start) {
          this.selected = { start: date, end: this.selected.start };
        } else {
          this.selected.end = date;
        }
        // Fill end-date linked input if provided
        if (this.options.linkedEnd && this.selected.end) {
          const endEl = document.querySelector(this.options.linkedEnd);
          if (endEl) endEl.value = this._fmt(this.selected.end);
        }
        if (this.options.onChange) this.options.onChange(this.selected);
        this.close();
      }
    }
    this._render();
  }

  _isSelected(date) {
    if (!this.selected) return false;
    if (this.selected instanceof Date) return this._sameDay(date, this.selected);
    const { start, end } = this.selected;
    return (start && this._sameDay(date, start)) || (end && this._sameDay(date, end));
  }

  _inRange(date) {
    if (!this.selected || !(this.selected.start && this.selected.end)) return false;
    return date > this.selected.start && date < this.selected.end;
  }

  /* ── Open / Close ────────────────────────────────── */
  _attachListeners() {
    this.input.classList.add('datepicker-input');
    this.input.setAttribute('readonly', 'readonly');

    this.input.addEventListener('click', (e) => {
      e.stopPropagation();
      this.isOpen ? this.close() : this.open();
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!this.wrap.contains(e.target)) this.close();
    });
  }

  open() {
    this.popup.classList.add('open');
    this.isOpen = true;
  }
  close() {
    this.popup.classList.remove('open');
    this.isOpen = false;
  }

  /* ── Helpers ─────────────────────────────────────── */
  _fmt(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
  _sameDay(a, b) {
    return a.getFullYear() === b.getFullYear() &&
           a.getMonth()    === b.getMonth()    &&
           a.getDate()     === b.getDate();
  }
  _uid() {
    return Math.random().toString(36).slice(2, 7);
  }

  /* ── Public: set value programmatically ─────────── */
  setValue(dateStr) {
    if (!dateStr) return;
    const d = new Date(dateStr + 'T00:00:00');
    if (!isNaN(d)) {
      this.selected = d;
      this.input.value = this._fmt(d);
      this.viewDate = new Date(d.getFullYear(), d.getMonth(), 1);
      this._render();
    }
  }
}
