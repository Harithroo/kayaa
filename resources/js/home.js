/* Kayaa Home behaviour: the prototype's assets/js/home/*.js, ported verbatim and loaded
   on the home page only (resources/views/store/home.blade.php), in the prototype's order.
   They are classic scripts that attach to `window` and run on import:

     garments.js      the garment drawings, fabric textures and hanger (window.KayaaGarments)
     home-core.js     helpers, the preloader, word-rise headings, scroll reveal (window.KayaaHome)
     home-hero.js     the washing line in the hero
     home-age.js      Shop by age
     home-rail.js     the category rail
     home-marquee.js  the scrolling band
     home-fabrics.js  the cloth swatch and the fabric tabs
     home-products.js New this week / Bestsellers tabs, card entrance and tilt
     home-extras.js   promo drawings, light parallax, CSS-loop sleeping, reviews scroller

   They read window.KAYAA_RM and the html.js / html.rm / html.intro-on classes, which the
   motion script in the home page's <head> sets before first paint. garments.js draws its
   fabric textures as soon as it runs, so this bundle never loads on any other page. */
import './store/home/garments.js';
import './store/home/home-core.js';
import './store/home/home-hero.js';
import './store/home/home-age.js';
import './store/home/home-rail.js';
import './store/home/home-marquee.js';
import './store/home/home-fabrics.js';
import './store/home/home-products.js';
import './store/home/home-extras.js';
