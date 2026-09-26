/**
 * TripWeave — validate.js
 * Reusable client-side form validation utilities.
 * Called from inline scripts on auth pages and contact page.
 */

/**
 * Validate a single field and show/hide its error message.
 * @param {HTMLElement} field
 * @param {boolean}     condition - true = valid, false = show error
 * @param {string}      msg       - Error message to display
 * @returns {boolean}
 */
function validateField(field, condition, msg) {
  const err = field.parentElement.querySelector('.form-error-msg');
  if (condition) {
    field.classList.remove('error-field');
    if (err) err.classList.remove('visible');
    return true;
  } else {
    field.classList.add('error-field');
    if (err) { err.textContent = msg; err.classList.add('visible'); }
    return false;
  }
}

/**
 * Generic form validator.
 * @param {HTMLFormElement} form
 * @param {Array<{id, rules: [{type, value, message}]}>} schema
 * @returns {boolean} true if all fields valid
 */
function validateForm(form, schema) {
  let allValid = true;
  schema.forEach(({ id, rules }) => {
    const field = form.querySelector('#' + id);
    if (!field) return;
    let fieldOk = true;
    for (const rule of rules) {
      let ok = true;
      switch (rule.type) {
        case 'required':
          ok = field.value.trim().length > 0; break;
        case 'email':
          ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim()); break;
        case 'minLength':
          ok = field.value.length >= rule.value; break;
        case 'match':
          const other = form.querySelector('#' + rule.value);
          ok = other && field.value === other.value; break;
        case 'pattern':
          ok = new RegExp(rule.value).test(field.value); break;
        case 'date':
          ok = !isNaN(new Date(field.value).getTime()); break;
        case 'afterField':
          const ref = form.querySelector('#' + rule.value);
          ok = ref && new Date(field.value) >= new Date(ref.value); break;
      }
      if (!ok) {
        validateField(field, false, rule.message);
        fieldOk = false;
        break;
      }
    }
    if (fieldOk) validateField(field, true, '');
    if (!fieldOk) allValid = false;
  });
  return allValid;
}

/* ── Live validation: clear error on input ────────── */
document.querySelectorAll('.form-control-custom').forEach(field => {
  field.addEventListener('input', () => {
    if (field.classList.contains('error-field')) {
      field.classList.remove('error-field');
      const err = field.parentElement.querySelector('.form-error-msg');
      if (err) err.classList.remove('visible');
    }
  });
});
