/* Checkout behaviour (production code: stays after the Blade conversion; the server repeats every rule).
   - validates the form on submit: error summary (role="alert", focus moves to it, links to each field) plus inline
     messages with aria-invalid and aria-describedby
   - shows the delivery estimate once a district is chosen (table in <script type="application/json" id="district-eta">)
   - loading state on a valid submit: disabled, aria-busy, spinner, "Redirecting to secure payment..."; no double submit
   The prototype hook data-demo-pay on the form hands the valid submit to proto-checkout.js instead of posting. */
(function () {
  'use strict';

  var form = document.querySelector('[data-checkout-form]');
  if (!form) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  var summary = $('[data-error-summary]');
  var list = $('[data-error-list]');
  var payBtn = $('[data-pay-btn]');
  var payLabel = $('[data-pay-label]');
  var spinner = $('.spinner', payBtn);
  var payIcon = $('.icon', payBtn);
  var fields = $$('[data-field]', form);
  var attempted = false;

  /* ---------- Rules ---------- */
  var RULES = {
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
    // Sri Lankan mobile: 07X XXXXXXX (10 digits) or +94 7X XXXXXXX, with spaces or dashes
    phone: function (v) { return /^(?:0|\+94)7[0-8]\d{7}$/.test(v.replace(/[\s\-().]/g, '')); }
  };

  function controlOf(wrap) { return $('input, select, textarea', wrap); }

  function check(wrap) {
    var input = controlOf(wrap);
    var v = input.value.trim();
    if (input.required && !v) return input.getAttribute('data-required-message') || 'This field is required';
    var rule = input.getAttribute('data-validate');
    if (v && rule && RULES[rule] && !RULES[rule](v)) return input.getAttribute('data-invalid-message') || 'Check this field';
    return '';
  }

  function setError(wrap, message) {
    var input = controlOf(wrap);
    var err = $('.field__error', wrap);
    var hint = $('.field__hint', wrap);
    wrap.classList.toggle('field--error', !!message);
    err.textContent = message;
    err.hidden = !message;
    if (message) {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', (hint ? hint.id + ' ' : '') + err.id);
    } else {
      input.removeAttribute('aria-invalid');
      if (hint) input.setAttribute('aria-describedby', hint.id); else input.removeAttribute('aria-describedby');
    }
  }

  function validateAll() {
    var errors = [];
    fields.forEach(function (wrap) {
      var message = check(wrap);
      setError(wrap, message);
      if (message) errors.push({ id: controlOf(wrap).id, message: message });
    });
    return errors;
  }

  function showSummary(errors, focus) {
    list.innerHTML = '';
    errors.forEach(function (e) {
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#' + e.id;
      a.textContent = e.message;
      li.appendChild(a);
      list.appendChild(li);
    });
    summary.hidden = errors.length === 0;
    if (errors.length && focus) summary.focus();
  }

  // links in the summary focus the field (a plain anchor jump does not always move focus)
  summary.addEventListener('click', function (e) {
    var a = e.target.closest('a[href^="#"]');
    if (!a) return;
    var target = document.getElementById(a.getAttribute('href').slice(1));
    if (target) { e.preventDefault(); target.focus(); }
  });

  // after the first attempt, fix errors as the shopper types
  function revalidate(e) {
    if (!attempted) return;
    var wrap = e.target.closest('[data-field]');
    if (!wrap) return;
    setError(wrap, check(wrap));
    var errors = fields.filter(function (w) { return !!check(w); }).map(function (w) { return { id: controlOf(w).id, message: check(w) }; });
    showSummary(errors, false);
  }
  form.addEventListener('input', revalidate);
  form.addEventListener('change', revalidate);

  /* ---------- Delivery estimate (placeholder table; TODO real data) ---------- */
  var eta = {};
  try { eta = JSON.parse($('#district-eta').textContent); } catch (e) { eta = {}; }
  var district = $('#district');
  var etaText = $('[data-eta-text]');

  function updateEta() {
    var slug = district.value;
    var label = slug ? district.options[district.selectedIndex].text : '';
    var known = slug && eta[slug];
    etaText.textContent = known ? 'Estimated delivery to ' + label + ': ' + eta[slug] : 'Choose your district to see the delivery estimate';
    $$('[data-eta-summary]').forEach(function (n) {
      n.hidden = !known;
      n.textContent = known ? 'Delivering to ' + label + ': ' + eta[slug] : '';
    });
  }
  if (district && etaText) { district.addEventListener('change', updateEta); updateEta(); }

  /* ---------- Submit ---------- */
  function setBusy(on) {
    payBtn.disabled = on;
    if (on) payBtn.setAttribute('aria-busy', 'true'); else payBtn.removeAttribute('aria-busy');
    spinner.hidden = !on;
    if (payIcon) payIcon.hidden = on;
    if (on) { payLabel.setAttribute('data-label-idle', payLabel.textContent); payLabel.textContent = 'Redirecting to secure payment...'; }
    else if (payLabel.getAttribute('data-label-idle')) payLabel.textContent = payLabel.getAttribute('data-label-idle');
  }

  form.addEventListener('submit', function (e) {
    if (payBtn.getAttribute('aria-busy') === 'true') { e.preventDefault(); return; }   // no double submit
    attempted = true;
    var errors = validateAll();
    if (errors.length) {
      e.preventDefault();
      showSummary(errors, true);
      return;
    }
    showSummary([], false);
    setBusy(true);
    if (form.hasAttribute('data-demo-pay') && window.KayaaDemoPay) {   // prototype: simulate the redirect to Onepay
      e.preventDefault();
      window.KayaaDemoPay.start(form);
    }
    // otherwise the browser posts the form and the server redirects to Onepay's hosted page
  });

  // coming back with the browser's Back button must not leave the button stuck
  window.addEventListener('pageshow', function (e) { if (e.persisted) setBusy(false); });
})();
