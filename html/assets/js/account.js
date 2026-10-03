/* Auth and account forms (production code: stays after the Blade conversion; the server repeats every rule).
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

  var RULES = {
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
    // Sri Lankan mobile: 07X XXXXXXX (10 digits) or +94 7X XXXXXXX, spaces and dashes allowed
    phone: function (v) { return /^(?:0|\+94)7[0-8]\d{7}$/.test(v.replace(/[\s\-().]/g, '')); }
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
    var inputs = $$('[data-field] input', form).filter(function (i) { return i.type !== 'checkbox'; });
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

  window.KayaaForms = {
    addError: function (form, fieldId, text) { if (form && form._addError) form._addError(fieldId, text); }
  };
})();
