/* PROTOTYPE ONLY - delete at Blade conversion: the server validates and sends the contact message.
   Runs after account.js (validation and the error summary).
   contact.html ?demo= sent (success alert), errors (error summary + inline messages), throttle (5 messages a minute).
   A valid submit shows the success alert and clears the form; nothing is sent. */
(function () {
  'use strict';

  var root = document.querySelector('[data-contact-page]');
  if (!root) return;

  var form = root.querySelector('[data-auth-form]');
  var demo = new URLSearchParams(location.search).get('demo') || '';
  function alertEl(name) { return root.querySelector('[data-contact-alert="' + name + '"]'); }
  function set(id, v) { var el = document.getElementById(id); if (el) el.value = v; }

  if (demo === 'sent') alertEl('sent').hidden = false;
  if (demo === 'throttle') alertEl('throttle').hidden = false;
  if (demo === 'errors') {
    set('contact', 'amaya@example');
    form.dispatchEvent(new Event('submit', { cancelable: true }));
  }

  form.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    e.preventDefault();
    form.reset();
    alertEl('throttle').hidden = true;
    var sent = alertEl('sent');
    sent.hidden = false;
    sent.focus();
  });
})();
