(() => {
  'use strict';
  document.addEventListener('change', (event) => {
    const el = event.target.closest('[data-csp-submit-change]');
    if (el && el.form) el.form.submit();
  });
  document.addEventListener('click', (event) => {
    const stop = event.target.closest('[data-csp-stop-propagation]');
    if (stop) event.stopPropagation();
    const print = event.target.closest('[data-csp-print]');
    if (print) { event.preventDefault(); window.print(); return; }
    const link = event.target.closest('[data-csp-href]');
    if (link) {
      const href = link.getAttribute('data-csp-href');
      if (href) window.location.href = href;
    }
  });
})();
