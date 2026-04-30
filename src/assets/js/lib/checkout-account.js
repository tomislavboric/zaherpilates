const updateCheckoutPasswordMeter = (input) => {
  if (!input) {
    return;
  }

  const checkoutInput = input.closest('.mepr-checkout-container .mepr-password');
  if (!checkoutInput) {
    return;
  }

  input = checkoutInput;

  const row = input.closest('.mepr_password');
  const meter = row ? row.querySelector('[data-password-meter]') : null;
  if (!meter) {
    return;
  }

  const value = input.value || '';
  let level = 0;

  if (value.length >= 8) {
    level += 1;
  }
  if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
    level += 1;
  }
  if (/\d/.test(value)) {
    level += 1;
  }
  if (/[^A-Za-z0-9]/.test(value)) {
    level += 1;
  }

  meter.classList.remove('is-level-0', 'is-level-1', 'is-level-2', 'is-level-3', 'is-level-4');
  meter.classList.add(`is-level-${level}`);

  const label = meter.querySelector('.mepr-checkout-password-label');
  if (!label) {
    return;
  }

  if (!value) {
    label.textContent = 'Unesi lozinku';
  } else if (level <= 1) {
    label.textContent = 'Slaba lozinka';
  } else if (level === 2) {
    label.textContent = 'Dobra lozinka';
  } else {
    label.textContent = 'Jaka lozinka';
  }
};

document.addEventListener('input', (event) => {
  if (!(event.target instanceof Element)) {
    return;
  }

  updateCheckoutPasswordMeter(event.target);
});

document.addEventListener('DOMContentLoaded', () => {
  document
    .querySelectorAll('.mepr-checkout-container .mepr-password')
    .forEach(updateCheckoutPasswordMeter);
});
