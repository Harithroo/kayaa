/* Kayaa storefront behaviour. The prototype's assets/js modules, ported verbatim.
   They are classic scripts that attach to `window` and run on import, so the order
   here is the order of tools/partials/scripts.html. Every page module guards itself
   on its own page root ([data-product-page], [data-checkout-form], ...) and no-ops
   elsewhere, so one bundle serves every page.

   The prototype's proto-*.js and catalogue-data.js live in resources/js/store/proto/
   for reference only: they fake server behaviour (demo cart in sessionStorage, static
   catalogue, fake auth) and are deliberately not imported — the real server does that
   work now. */

// Shell: header, drawers, mega menu, quick add, tab bar, topbar, wishlist buttons.
import './store/search-core.js';
import './store/search-suggest.js';
import './store/app.js';
import './store/cart-server.js';   // the server cart, in place of the prototype's proto-cart.js

// Page modules.
import './store/product.js';
import './store/cart.js';
import './store/checkout.js';
import './store/account.js';
import './store/content.js';
import './store/size-guide.js';
import './store/thank-you.js';
