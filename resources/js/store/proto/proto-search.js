/* PROTOTYPE ONLY - delete at Blade conversion.
   Answers the suggestion requests from window.KAYAA_CATALOGUE through search-core.js, so the prototype shows exactly what the backend will:
   production leaves window.KAYAA_SEARCH_PROVIDER unset and search-suggest.js calls GET /search/suggest?q= . */
(function () {
  'use strict';
  window.KAYAA_SEARCH_PROVIDER = function (q) { return Promise.resolve(window.KayaaSearch.suggest(q)); };
})();
