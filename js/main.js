/* =============================================================================
   Ledgerly - site-wide JavaScript
   Features: form validation, interactive slider, smooth scrolling,
             event handling (tooltips, back-to-top, sticky nav), animations.
   ========================================================================== */

(function () {
  'use strict';

  /* ---------------------------------------------------------------------
     1. Form validation
     Every form marked data-validate is checked on submit and on blur.
     Rules live in data-rule attributes on each field.
     --------------------------------------------------------------------- */

  var rules = {
    required: function (value) {
      return value.trim() !== '' || 'This field is required.';
    },
    name: function (value) {
      return /^[A-Za-z\s'.-]{2,50}$/.test(value.trim()) || 'Use letters only, at least 2 characters.';
    },
    username: function (value) {
      return /^[A-Za-z0-9_]{4,20}$/.test(value.trim()) ||
        'Use 4 to 20 characters: letters, numbers or underscore.';
    },
    email: function (value) {
      return /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/.test(value.trim()) || 'Enter a valid email address.';
    },
    mobile: function (value) {
      return /^[0-9+\s-]{9,15}$/.test(value.trim()) || 'Enter a valid mobile number (9 to 15 digits).';
    },
    password: function (value) {
      if (value.length < 8) { return 'Use at least 8 characters.'; }
      if (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) {
        return 'Mix letters and numbers.';
      }
      return true;
    },
    amount: function (value) {
      var n = parseFloat(value);
      if (isNaN(n) || n <= 0) { return 'Enter an amount greater than zero.'; }
      if (n > 99999999) { return 'That amount is too large.'; }
      return true;
    },
    date: function (value) {
      if (!value) { return 'Pick a date.'; }
      var picked = new Date(value);
      if (isNaN(picked.getTime())) { return 'Pick a valid date.'; }
      var today = new Date();
      today.setHours(23, 59, 59, 999);
      if (picked > today) { return 'The date cannot be in the future.'; }
      return true;
    },
    message: function (value) {
      return value.trim().length >= 15 || 'Tell us a little more (at least 15 characters).';
    },
    match: function (value, field) {
      var other = document.getElementById(field.dataset.matchField);
      return (other && value === other.value) || 'The two passwords do not match.';
    }
  };

  function errorBox(field) {
    var box = document.querySelector('[data-error-for="' + field.id + '"]');
    if (!box) {
      box = document.createElement('span');
      box.className = 'field-error';
      box.setAttribute('data-error-for', field.id);
      field.insertAdjacentElement('afterend', box);
    }
    return box;
  }

  function checkField(field) {
    var list = (field.dataset.rule || '').split('|').filter(Boolean);
    var box = errorBox(field);
    var value = field.value;

    for (var i = 0; i < list.length; i++) {
      var rule = rules[list[i]];
      if (!rule) { continue; }
      var result = rule(value, field);
      if (result !== true) {
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        box.textContent = result;
        return false;
      }
    }

    field.classList.remove('is-invalid');
    field.removeAttribute('aria-invalid');
    box.textContent = '';
    return true;
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var fields = form.querySelectorAll('[data-rule]');

    fields.forEach(function (field) {
      field.addEventListener('blur', function () { checkField(field); });
      field.addEventListener('input', function () {
        if (field.classList.contains('is-invalid')) { checkField(field); }
      });
    });

    form.addEventListener('submit', function (event) {
      var firstBad = null;
      fields.forEach(function (field) {
        if (!checkField(field) && !firstBad) { firstBad = field; }
      });
      if (firstBad) {
        event.preventDefault();
        firstBad.focus();
        firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });

  /* Live password strength meter on the registration form. */
  var pw = document.getElementById('password');
  var meter = document.querySelector('[data-strength-bar] span');
  var meterText = document.querySelector('[data-strength-text]');
  if (pw && meter) {
    pw.addEventListener('input', function () {
      var v = pw.value, score = 0;
      if (v.length >= 8) { score++; }
      if (/[A-Z]/.test(v)) { score++; }
      if (/[0-9]/.test(v)) { score++; }
      if (/[^A-Za-z0-9]/.test(v)) { score++; }
      var labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'];
      var colors = ['#a8422c', '#a8422c', '#b4761a', '#2f3f8f', '#10725a'];
      meter.style.width = (score / 4) * 100 + '%';
      meter.style.background = colors[score];
      if (meterText) { meterText.textContent = v ? labels[score] : ''; }
    });
  }

  /* ---------------------------------------------------------------------
     2. Interactive slider - manual buttons, dots, autoplay, keyboard
     --------------------------------------------------------------------- */

  var slider = document.getElementById('tourSlider');
  if (slider) {
    var slides = Array.prototype.slice.call(slider.querySelectorAll('.slide'));
    var dotsWrap = slider.querySelector('.slider-dots');
    var index = 0;
    var timer = null;
    var delay = 5000;

    slides.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'slider-dot' + (i === 0 ? ' is-active' : '');
      dot.setAttribute('aria-label', 'Show slide ' + (i + 1));
      dot.addEventListener('click', function () { go(i); restart(); });
      dotsWrap.appendChild(dot);
    });

    function go(next) {
      index = (next + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        slide.classList.toggle('is-active', i === index);
        slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
      });
      dotsWrap.querySelectorAll('.slider-dot').forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === index);
      });
    }

    function start() {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
      timer = window.setInterval(function () { go(index + 1); }, delay);
    }
    function stop() { window.clearInterval(timer); }
    function restart() { stop(); start(); }

    slider.querySelector('[data-slide-prev]').addEventListener('click', function () { go(index - 1); restart(); });
    slider.querySelector('[data-slide-next]').addEventListener('click', function () { go(index + 1); restart(); });
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') { go(index - 1); restart(); }
      if (event.key === 'ArrowRight') { go(index + 1); restart(); }
    });

    go(0);
    start();
  }

  /* ---------------------------------------------------------------------
     3. Smooth scrolling for in-page links (offset for the sticky navbar)
     --------------------------------------------------------------------- */

  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      var id = link.getAttribute('href');
      if (id === '#' || id.length < 2) { return; }
      var target = document.querySelector(id);
      if (!target) { return; }
      event.preventDefault();
      var nav = document.getElementById('siteNav');
      var offset = nav ? nav.offsetHeight + 12 : 0;
      var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
      window.scrollTo({
        top: top,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
      });
      target.setAttribute('tabindex', '-1');
      target.focus({ preventScroll: true });
    });
  });

  /* ---------------------------------------------------------------------
     4. Event handling - sticky nav shadow, back to top, tooltips
     --------------------------------------------------------------------- */

  var nav = document.getElementById('siteNav');
  var toTop = document.getElementById('toTop');

  window.addEventListener('scroll', function () {
    var y = window.pageYOffset;
    if (nav) { nav.classList.toggle('is-scrolled', y > 8); }
    if (toTop) { toTop.classList.toggle('is-visible', y > 400); }
  }, { passive: true });

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    if (window.bootstrap) { new window.bootstrap.Tooltip(el); }
  });

  /* Collapse the mobile menu after a link is tapped. */
  document.querySelectorAll('#mainNav .nav-link, #mainNav .btn').forEach(function (link) {
    link.addEventListener('click', function () {
      var menu = document.getElementById('mainNav');
      if (window.bootstrap && menu && menu.classList.contains('show')) {
        window.bootstrap.Collapse.getInstance(menu).hide();
      }
    });
  });

  /* ---------------------------------------------------------------------
     5. Custom animation - reveal blocks as they enter the viewport
     --------------------------------------------------------------------- */

  var revealables = document.querySelectorAll('.reveal');
  if (revealables.length) {
    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });
      revealables.forEach(function (el) { observer.observe(el); });
    } else {
      revealables.forEach(function (el) { el.classList.add('is-visible'); });
    }
  }

  /* ---------------------------------------------------------------------
     6. Dynamic content - toggle any panel with data-toggle-target
     --------------------------------------------------------------------- */

  document.querySelectorAll('[data-toggle-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var panel = document.querySelector(button.dataset.toggleTarget);
      if (!panel) { return; }
      var open = panel.hasAttribute('hidden');
      if (open) { panel.removeAttribute('hidden'); } else { panel.setAttribute('hidden', ''); }
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.textContent = open
        ? (button.dataset.labelOpen || 'Hide')
        : (button.dataset.labelClosed || 'Show');
    });
  });
})();
