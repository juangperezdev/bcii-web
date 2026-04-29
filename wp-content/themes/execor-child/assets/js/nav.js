/**
 * BCII — Mobile nav toggle
 * Togglea .open en #navLinks cuando se clickea #navToggle.
 */
(function () {
  'use strict';

  function init() {
    var btn  = document.getElementById('navToggle');
    var menu = document.getElementById('navLinks');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Cerrar al clickear un link (UX en mobile)
    menu.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (a && menu.classList.contains('open')) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
