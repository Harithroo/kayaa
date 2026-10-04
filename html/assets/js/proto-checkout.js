/* PROTOTYPE ONLY - delete at Blade conversion.
   Renders the order summary from the demo cart, applies the ?demo= states and simulates the hand-off to the
   payment gateway: a valid submit stores the order in sessionStorage (reference KY-YYMMDD-XXXX), empties the bag (the server empties
   the cart when it creates the order), waits about 1.2s on the loading state, then goes to proto-onepay.html.
   A cash order skips the gateway: the order is stored with payment method cod and the next page is thank-you.html?state=cod.
   ?demo=: errors, throttle, gateway-error, signed-in, empty, cod-on (the second payment option; in production the server shows it only when the admin setting cod_enabled is on). */
(function () {
  'use strict';

  var page = document.querySelector('[data-checkout-page]');
  var store = window.KayaaCart;
  if (!page || !store) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var money = store.money;
  var demo = new URLSearchParams(location.search).get('demo') || '';
  var form = $('[data-checkout-form]', page);
  var signedIn = demo === 'signed-in';

  /* ---------- Summary ---------- */
  function lineHtml(it) {
    return '<li class="order-line"><div class="media media--tile order-line__media" data-placeholder style="--tone: ' + store.toneCss(it) + '">' +
      '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-image"></use></svg></div>' +
      '<div><p class="order-line__name">' + it.name + '</p><p class="order-line__meta">Size ' + it.sizeLabel + ' · ' + it.colourLabel + ' · Qty ' + it.qty + '</p></div>' +
      '<p class="order-line__price">' + money(it.qty * it.unitPrice) + '</p></li>';
  }

  function render() {
    var items = store.get().filter(function (it) { return !it.unavailable; });
    var t = store.totals();
    var has = items.length > 0;
    $('[data-checkout-filled]', page).hidden = !has;
    $('[data-checkout-empty]', page).hidden = has;
    $$('[data-summary-lines]', page).forEach(function (ul) { ul.innerHTML = items.map(lineHtml).join(''); });
    $$('[data-summary-subtotal]', page).forEach(function (n) { n.textContent = money(t.subtotal); });
    $$('[data-summary-delivery]', page).forEach(function (n) { n.textContent = t.delivery === 0 ? 'Free' : money(t.delivery); });
    $$('[data-summary-total]', page).forEach(function (n) { n.textContent = money(t.total); });
    $$('[data-summary-heading]', page).forEach(function (n) { n.textContent = 'Order summary · ' + t.count + (t.count === 1 ? ' item' : ' items') + ' · ' + money(t.total); });
    $$('[data-fee-text]', page).forEach(function (n) {
      n.textContent = t.delivery === 0 ? 'Delivery: free (orders from ' + money(t.freeAt) + ')' : 'Delivery: ' + money(t.fee) + ' (free from ' + money(t.freeAt) + ')';
    });
  }
  document.addEventListener('kayaa:cart', render);
  render();

  /* ---------- Demo states ---------- */
  function setValue(id, v) { var el = document.getElementById(id); if (el) el.value = v; }
  function showAlert(name) { var a = $('[data-checkout-alert="' + name + '"]', page); if (a) a.hidden = false; }

  if (signedIn) {
    setValue('email', 'amaya@example.com');
    setValue('phone', '071 234 5678');
    setValue('name', 'Amaya Ranasinghe');
    setValue('address_line1', '42 Temple Road');
    setValue('city', 'Nugegoda');
    setValue('district', 'colombo');
    $('[data-account-aside]', page).innerHTML = 'Signed in as <strong>amaya@example.com</strong> · <a href="account/login.html">Not you?</a>';
    $('#district').dispatchEvent(new Event('change', { bubbles: true }));
  }
  if (demo === 'errors') {
    setValue('email', 'amaya@');
    setValue('phone', '071123');
    setValue('address_line1', '');
    setValue('district', '');
    form.dispatchEvent(new Event('submit', { cancelable: true }));
  }
  if ((window.KAYAA_CONFIG || {}).cod_enabled || demo === 'cod-on') {
    var single = $('[data-pay-single]', page);
    var choice = $('[data-pay-choice]', page);
    single.hidden = true;
    $('[data-pay-default]', single).disabled = true;
    choice.hidden = false;
    choice.disabled = false;
  }
  if (demo === 'throttle') showAlert('throttle');
  if (demo === 'gateway-error') showAlert('gateway');

  /* ---------- Simulated hand-off ---------- */
  window.KayaaDemoPay = {
    start: function (f) {
      var t = store.totals();
      var cod = new FormData(f).get('payment_method') === 'cod';
      var districtEl = f.elements.district;
      var eta = null;   // { min, max } working days for the chosen district
      try { eta = JSON.parse(document.getElementById('district-eta').textContent)[districtEl.value] || null; } catch (e) { /* none */ }
      store.order.save({
        ref: store.order.nextRef(),
        createdAt: new Date().toISOString(),
        signedIn: signedIn,
        items: store.get().filter(function (it) { return !it.unavailable; }),
        totals: t,
        contact: { email: f.elements.email.value.trim(), phone: f.elements.phone.value.trim() },
        address: {
          name: f.elements.name.value.trim(),
          line1: f.elements.address_line1.value.trim(),
          line2: f.elements.address_line2.value.trim(),
          city: f.elements.city.value.trim(),
          district: districtEl.value,
          districtLabel: districtEl.options[districtEl.selectedIndex].text,
          notes: f.elements.notes.value.trim()
        },
        eta: eta,
        payment: { method: cod ? 'cod' : 'online', status: cod ? 'cod' : 'pending' }
      });
      // the order exists now, so the bag is empty whatever the payment outcome is (silent: this page is still showing)
      store.clear(true);
      setTimeout(function () { location.href = cod ? 'thank-you.html?state=cod' : 'proto-onepay.html'; }, cod ? 600 : 1200);
    }
  };
})();
