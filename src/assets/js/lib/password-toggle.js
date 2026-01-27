document.addEventListener('click', (event) => {
  const toggle = event.target.closest('[data-password-toggle]');
  if (!toggle) {
    return;
  }

  const group = toggle.closest('.account-page__input-group');
  const input = group ? group.querySelector('input') : null;
  if (!input) {
    return;
  }

  const isVisible = input.type === 'text';
  input.type = isVisible ? 'password' : 'text';
  toggle.classList.toggle('is-visible', !isVisible);
  toggle.setAttribute('aria-pressed', (!isVisible).toString());
  toggle.setAttribute('aria-label', isVisible ? 'Prikaži lozinku' : 'Sakrij lozinku');
});
