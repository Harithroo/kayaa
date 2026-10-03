/* PROTOTYPE ONLY - delete at Blade conversion. Fills the stand-in payment page with the stored order. */
(function () {
  'use strict';
  var store = window.KayaaCart;
  var order = store && store.order.get();
  var ref = order ? order.ref : 'KYA-10234';          // sample when the page is opened directly
  var amount = order ? order.totals.total : 6750;
  var fmt = function (n) { return 'Rs ' + Math.round(n).toLocaleString('en-US'); };
  document.querySelector('[data-order-ref]').textContent = ref;
  document.querySelector('[data-order-amount]').textContent = fmt(amount);
})();
