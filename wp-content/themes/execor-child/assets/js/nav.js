/**
 * BCII — Nav + scroll reveal
 *  - Mobile nav toggle (.open en #navLinks)
 *  - Auto-reveal: aplica .reveal a tarjetas / panels y togglea .is-visible
 *    al entrar en viewport (IntersectionObserver). Respetando prefers-reduced-motion.
 */
(function () {
  'use strict';

  function initNav() {
    var btn  = document.getElementById('navToggle');
    var menu = document.getElementById('navLinks');
    if (!btn || !menu) return;

    btn.addEventListener('click', function () {
      var open = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    menu.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (a && menu.classList.contains('open')) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initReveal() {
    if (!('IntersectionObserver' in window)) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    // Auto-elegibles: tarjetas comunes que deberían animarse al scroll.
    var autoSelectors = '.panel, .news-card, .leader-card, .stat-card, .step-card, .person-card, .timeline-item, .vp-icon';
    var nodes = document.querySelectorAll(autoSelectors + ', .reveal');
    nodes.forEach(function (n) {
      if (!n.classList.contains('reveal')) n.classList.add('reveal');
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    nodes.forEach(function (n) { io.observe(n); });
  }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    initNav();
    initReveal();
  });
})();
