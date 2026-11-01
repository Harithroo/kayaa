/* Auth, account and contact forms, confirmation dialogs and the verification banner (production code: stays after the Blade conversion; the server repeats every rule).
   For every <form data-auth-form data-summary="ID"> it provides:
   - client validation on submit: required, data-validate (email, phone), data-minlength, data-match="otherFieldId"
   - an error summary (role="alert", focus moves to it, links to each invalid field) plus inline messages with
     aria-invalid and aria-describedby; errors clear as the shopper fixes them
   - show/hide password buttons ([data-password-toggle] with aria-pressed and aria-controls)
   Login errors never say which field was wrong and forgot-password never says whether an email exists: those
   messages come from the server as one alert.
   window.KayaaForms.addError(form, fieldId, message) lets a server-side error (for example "email already registered")
   join the summary. */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  // account chip nav (a scrolling row on small screens): bring the current chip into view on load, so nothing looks cut off
  (function () {
    var row = $('.account-nav ul');
    var cur = row && $('[aria-current="page"]', row);
    if (cur && row.scrollWidth > row.clientWidth) row.scrollLeft = Math.max(0, cur.offsetLeft - (row.clientWidth - cur.offsetWidth) / 2);
  })();

  var RULES = {
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
    // Sri Lankan mobile: 07X XXXXXXX (10 digits) or +94 7X XXXXXXX, spaces and dashes allowed
    phone: function (v) { return /^(?:0|\+94)7[0-8]\d{7}$/.test(v.replace(/[\s\-().]/g, '')); },
    // the contact form has one field for either: a Sri Lankan mobile number or an email address
    contact: function (v) { return RULES.email(v) || RULES.phone(v); }
  };

  function wrapOf(input) { return input.closest('[data-field]'); }

  function message(input) {
    var v = input.value;
    var trimmed = v.trim();
    if (input.required && !trimmed) return input.getAttribute('data-required-message') || 'This field is required';
    if (!trimmed) return '';
    var rule = input.getAttribute('data-validate');
    if (rule && RULES[rule] && !RULES[rule](trimmed)) return input.getAttribute('data-invalid-message') || 'Check this field';
    var min = parseInt(input.getAttribute('data-minlength'), 10);
    if (min && v.length < min) return input.getAttribute('data-minlength-message') || 'Use at least ' + min + ' characters';
    var match = input.getAttribute('data-match');
    if (match) {
      var other = document.getElementById(match);
      if (other && other.value !== v) return input.getAttribute('data-match-message') || 'The values do not match';
    }
    return '';
  }

  function setError(input, text) {
    var wrap = wrapOf(input);
    var err = $('.field__error', wrap);
    var hint = $('.field__hint', wrap);
    wrap.classList.toggle('field--error', !!text);
    err.textContent = text;
    err.hidden = !text;
    if (text) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', (hint ? hint.id + ' ' : '') + err.id);
    } else {
      input.removeAttribute('aria-invalid');
      if (hint) input.setAttribute('aria-describedby', hint.id); else input.removeAttribute('aria-describedby');
    }
  }

  function init(form) {
    var summary = document.getElementById(form.getAttribute('data-summary'));
    var list = summary && $('[data-error-list]', summary);
    var inputs = $$('[data-field] input, [data-field] textarea', form).filter(function (i) { return i.type !== 'checkbox'; });
    var extra = {};   // server-side errors keyed by field id
    var attempted = false;

    function collect() {
      var errors = [];
      inputs.forEach(function (input) {
        var text = extra[input.id] || message(input);
        setError(input, text);
        if (text) errors.push({ id: input.id, message: text });
      });
      return errors;
    }
    function show(errors, focus) {
      if (!summary) return;
      list.innerHTML = '';
      errors.forEach(function (e) {
        var li = document.createElement('li');
        var a = document.createElement('a');
        a.href = '#' + e.id;
        a.textContent = e.message;
        li.appendChild(a);
        list.appendChild(li);
      });
      summary.hidden = !errors.length;
      if (errors.length && focus) summary.focus();
    }

    if (summary) {
      summary.addEventListener('click', function (e) {
        var a = e.target.closest('a[href^="#"]');
        if (!a) return;
        var target = document.getElementById(a.getAttribute('href').slice(1));
        if (target) { e.preventDefault(); target.focus(); }
      });
    }

    function revalidate(e) {
      if (!attempted || !e.target.closest('[data-field]')) return;
      delete extra[e.target.id];
      show(collect(), false);
    }
    form.addEventListener('input', revalidate);
    form.addEventListener('change', revalidate);

    form.addEventListener('submit', function (e) {
      attempted = true;
      var errors = collect();
      if (errors.length) { e.preventDefault(); show(errors, true); }
      else show([], false);
    });

    form._addError = function (id, text) {
      attempted = true;
      extra[id] = text;
      show(collect(), true);
    };
  }

  $$('form[data-auth-form]').forEach(init);

  // show / hide password
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('aria-controls'));
    if (!input) return;
    var show = btn.getAttribute('aria-pressed') !== 'true';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    input.type = show ? 'text' : 'password';
  });

  /* ---------- Confirmation dialogs (native <dialog>: modal focus trap, ESC closes, backdrop click closes) ----------
     [data-dialog-open="ID"] opens the dialog with that id; [data-dialog-close] inside it closes it; focus returns to the opener
     (or the page heading when the opener was hidden in the meantime, for example after the order was cancelled). */
  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-dialog-open]');
    if (opener) {
      var dlg = document.getElementById(opener.getAttribute('data-dialog-open'));
      if (dlg && dlg.showModal && !dlg.open) { dlg._opener = opener; dlg.showModal(); }
      return;
    }
    var closer = e.target.closest('[data-dialog-close]');
    if (closer) { var d = closer.closest('dialog'); if (d) d.close(); return; }
    if (e.target.tagName === 'DIALOG' && e.target.open) e.target.close();   // a click on the backdrop
  });
  // keep Tab and Shift+Tab inside an open dialog (the page behind it is inert, but focus could otherwise leave through the browser UI)
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab') return;
    var dlg = document.querySelector('dialog[open]');
    if (!dlg) return;
    var items = $$('a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])', dlg).filter(function (n) { return n.getClientRects().length; });
    if (!items.length) return;
    var first = items[0], last = items[items.length - 1];
    if (!dlg.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
    else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });
  $$('dialog').forEach(function (dlg) {
    dlg.addEventListener('close', function () {
      var back = dlg._opener;
      if (back && back.getClientRects().length) { back.focus(); return; }
      var h1 = $('main h1');
      if (h1) { h1.setAttribute('tabindex', '-1'); h1.focus(); }
    });
  });

  /* ---------- "Verify your email" banner: dismissal is remembered for the session only (sessionStorage) ---------- */
  var BANNER_KEY = 'kayaa.verifyBannerDismissed';
  function bannerDismissed() { try { return sessionStorage.getItem(BANNER_KEY) === '1'; } catch (e) { return false; } }
  $$('[data-verify-banner]').forEach(function (banner) {
    if (bannerDismissed()) banner.setAttribute('data-dismissed', '');
    var btn = $('[data-verify-dismiss]', banner);
    if (!btn) return;
    btn.addEventListener('click', function () {
      try { sessionStorage.setItem(BANNER_KEY, '1'); } catch (e) { /* the banner just comes back on the next page */ }
      banner.setAttribute('data-dismissed', '');
      var h1 = $('main h1');   // the button is about to disappear: keep focus somewhere sensible
      if (h1) { h1.setAttribute('tabindex', '-1'); h1.focus(); }
    });
  });

  window.KayaaForms = {
    bannerDismissed: bannerDismissed,
    addError: function (form, fieldId, text) { if (form && form._addError) form._addError(fieldId, text); }
  };
})();
