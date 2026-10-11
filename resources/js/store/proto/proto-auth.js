/* PROTOTYPE ONLY - delete at Blade conversion: the server authenticates, registers and sends reset links.
   Runs after account.js (validation). A valid demo submit sets the signed-in flag (sessionStorage) and goes to the dashboard.
   ?demo= login: errors (invalid credentials), throttle, success (after a password reset)
          register: errors, throttle
          forgot-password: errors, throttle, sent
          reset-password: errors, invalid-link
          verify-email: verified, invalid (the landing states of the emailed link), sent, throttle (Resend email)
   A new account starts unverified (register goes to verify-email.html); logging in demos a verified account.
   Hints for testers: password "wrong" on login shows invalid credentials; email taken@example.com on register shows "already registered". */
(function () {
  'use strict';

  var root = document.querySelector('[data-auth-page]');
  var store = window.KayaaCart;
  if (!root || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var page = root.getAttribute('data-auth-page');
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var form = $('[data-auth-form]', root);   // none on verify-email.html
  function set(id, v) { var el = document.getElementById(id); if (el) el.value = v; }
  function alertEl(name) { return $('[data-auth-alert="' + name + '"]', root); }
  function show(name) { var a = alertEl(name); if (a) a.hidden = false; return a; }
  function submitEmpty() { form.dispatchEvent(new Event('submit', { cancelable: true })); }

  if (demo === 'throttle' && alertEl('throttle')) show('throttle');

  if (page === 'login') {
    if (demo === 'errors') { set('email', 'amaya@example.com'); show('invalid'); }
    if (demo === 'success') show('success');
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      var email = document.getElementById('email').value.trim();
      if (document.getElementById('password').value === 'wrong') { show('invalid'); return; }
      store.session.set({ name: 'Amaya', email: email, verified: true });
      location.href = 'index.html';
    });
  }

  if (page === 'register') {
    if (demo === 'errors') {
      set('name', 'Amaya Ranasinghe'); set('email', 'taken@example.com'); set('phone', '071123'); set('password', 'abc'); set('password_confirmation', 'abd');
      submitEmpty();
      window.KayaaForms.addError(form, 'email', 'This email is already registered. Sign in or use a different email.');
    }
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      var email = document.getElementById('email').value.trim();
      if (email.toLowerCase() === 'taken@example.com') {
        window.KayaaForms.addError(form, 'email', 'This email is already registered. Sign in or use a different email.');
        return;
      }
      // the account works unverified; verifying the email is what attaches earlier guest orders
      store.session.set({ name: document.getElementById('name').value.trim().split(/\s+/)[0] || 'Amaya', email: email, verified: false });
      location.href = 'verify-email.html';
    });
  }

  if (page === 'forgot') {
    if (demo === 'sent') { form.hidden = true; show('sent'); $('[data-forgot-back]', root).hidden = false; }
    if (demo === 'errors') submitEmpty();
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      // always the same answer: never reveal whether the email is registered
      form.hidden = true;
      var a = show('sent');
      $('[data-forgot-back]', root).hidden = false;
      if (a) a.focus();
    });
  }

  if (page === 'verify') {
    var user = store.session.get() || { name: 'Amaya', email: 'amaya@example.com', verified: false };
    $$('[data-verify-email]', root).forEach(function (n) { n.textContent = user.email; });
    var state = demo === 'verified' || demo === 'invalid' ? demo : 'waiting';
    $$('[data-verify-state]', root).forEach(function (s) { s.hidden = s.getAttribute('data-verify-state') !== state; });
    if (state === 'verified') { user.verified = true; store.session.set(user); document.title = 'Email verified | Kayaa'; }
    if (state === 'invalid') document.title = 'Verification link expired | Kayaa';
    var section = $('[data-verify-state="' + state + '"]', root);
    var verifyAlert = function (name) { var a = $('[data-verify-alert="' + name + '"]', section); if (a) a.hidden = false; return a; };
    if (demo === 'sent') verifyAlert('sent');
    if (demo === 'throttle') verifyAlert('throttle');
    $$('[data-resend-form]', section).forEach(function (f) {
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        var th = $('[data-verify-alert="throttle"]', section); if (th) th.hidden = true;
        var a = verifyAlert('sent');
        if (a) a.focus();
      });
    });
    return;
  }

  if (page === 'reset') {
    if (demo === 'invalid-link') { $('[data-reset-form-card]', root).hidden = true; $('[data-reset-invalid]', root).hidden = false; }
    if (demo === 'errors') { set('password', 'abc'); set('password_confirmation', 'abd'); submitEmpty(); }
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      location.href = 'login.html?demo=success';
    });
  }
})();
