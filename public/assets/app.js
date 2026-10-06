'use strict';
// The core journey works without JavaScript; these are progressive enhancements.
document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});
document.querySelector('[data-checkout]')?.addEventListener('submit', event => {
  const button = event.currentTarget.querySelector('[data-submit]');
  button.disabled = true;
  button.textContent = 'Placing your order…';
});
window.addEventListener('pageshow', () => {
  const button = document.querySelector('[data-submit]');
  if (button) { button.disabled = false; button.textContent = 'Place demo order ↗'; }
});
