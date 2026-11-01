/* Kayaa storefront behaviour. The prototype's assets/js modules, ported verbatim.
   They are classic scripts that attach to `window` and run on import, so the order
   here is the order of tools/partials/scripts.html. Every page module guards itself
   on its own page root ([data-product-page], [data-checkout-form], ...) and no-ops
   elsewhere, so one bundle serves every page.

   The prototype's proto-*.js files are not here: they faked server behaviour (a demo
   cart in sessionStorage, a static catalogue, a fake sign-in) and the real server does
   that work now. cart-server.js replaces the demo cart. */

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
