/* PROTOTYPE ONLY - delete at Blade conversion: the server authenticates, registers and sends reset links.
   Runs after account.js (validation). A valid demo submit sets the signed-in flag (sessionStorage) and goes to the dashboard.
   ?demo= login: errors (invalid credentials), throttle, success (after a password reset)
          register: errors, throttle
          forgot-password: errors, throttle, sent
          reset-password: errors, invalid-link
   Hints for testers: password "wrong" on login shows invalid credentials; email taken@example.com on register shows "already registered". */
(function () {
  'use strict';

  var root = document.querySelector('[data-auth-page]');
  var store = window.KayaaCart;
  if (!root || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var page = root.getAttribute('data-auth-page');
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var form = $('[data-auth-form]', root);
  function set(id, v) { var el = document.getElementById(id); if (el) el.value = v; }
  function alertEl(name) { return $('[data-auth-alert="' + name + '"]', root); }
  function show(name) { var a = alertEl(name); if (a) a.hidden = false; return a; }
  function submitEmpty() { form.dispatchEvent(new Event('submit', { cancelable: true })); }

  if (demo === 'throttle') show('throttle');

  if (page === 'login') {
    if (demo === 'errors') { set('email', 'amaya@example.com'); show('invalid'); }
    if (demo === 'success') show('success');
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      var email = document.getElementById('email').value.trim();
      if (document.getElementById('password').value === 'wrong') { show('invalid'); return; }
      store.session.set({ name: 'Amaya', email: email });
      location.href = 'index.html';
    });
  }

  if (page === 'register') {
    if (demo === 'errors') {
      set('name', 'Amaya Ranasinghe'); set('email', 'taken@example.com'); set('phone', '071123'); set('password', 'abc'); set('password_confirmation', 'abd');
      submitEmpty();
      window.KayaaForms.addError(form, 'email', 'This email is already registered. Log in or use a different email.');
    }
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      e.preventDefault();
      var email = document.getElementById('email').value.trim();
      if (email.toLowerCase() === 'taken@example.com') {
        window.KayaaForms.addError(form, 'email', 'This email is already registered. Log in or use a different email.');
        return;
      }
      store.session.set({ name: document.getElementById('name').value.trim().split(/\s+/)[0] || 'Amaya', email: email });
      location.href = 'index.html';
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
