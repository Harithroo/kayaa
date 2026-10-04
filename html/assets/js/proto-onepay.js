/* PROTOTYPE ONLY - delete at Blade conversion. Fills the stand-in payment page with the stored order. */
(function () {
  'use strict';
  var store = window.KayaaCart;
  var order = store && store.order.get();
  var ref = order ? order.ref : 'KY-261003-A3F9';          // sample when the page is opened directly
  var amount = order ? order.totals.total : 6750;
  var fmt = store ? store.money : String;
  document.querySelector('[data-order-ref]').textContent = ref;
  document.querySelector('[data-order-amount]').textContent = fmt(amount);
})();
