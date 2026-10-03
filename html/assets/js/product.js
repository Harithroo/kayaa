/* Product page behaviour (production code: stays after the Blade conversion).
   Reads the variants from <script type="application/json" id="product-variants">
   ([{"colour":"lilac","size":"3-6M","stock":4}, ...]) and the low-stock threshold from
   data-low-stock-threshold on the buy form. Blade generates both. */
(function () {
  'use strict';

  var page = document.querySelector('[data-product-page]');
  if (!page) return;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var params = new URLSearchParams(location.search);
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var smooth = reduce ? 'auto' : 'smooth';

  /* ---------- Data ---------- */
  var form = $('[data-buy-form]');
  var NAME = form.getAttribute('data-product-name') || document.title;
  var LOW = parseInt(form.getAttribute('data-low-stock-threshold'), 10) || 5;   // admin setting
  var variants = [];
  try { variants = JSON.parse($('#product-variants').textContent); } catch (e) { variants = []; }
  var stockMap = {};
  variants.forEach(function (v) { stockMap[v.colour + '|' + v.size] = Math.max(0, parseInt(v.stock, 10) || 0); });
  var stockOf = function (colour, size) { return stockMap[colour + '|' + size] || 0; };
  var unitPrice = function () { return parseInt(form.getAttribute('data-unit-price'), 10) || 0; };

  var colourInputs = $$('input[name="colour"]', form);
  var sizeInputs = $$('input[name="size"]', form);
  var COLOUR_NAME = {};
  colourInputs.forEach(function (i) { COLOUR_NAME[i.value] = $('span.visually-hidden', i.parentNode).textContent.trim(); });
  var allOut = variants.every(function (v) { return !(parseInt(v.stock, 10) > 0); });

  var stockNote = $('[data-stock-note]');
  var qtyGroup = $('[data-qty]', form);
  var qtyInput = $('[data-qty-input]', form);
  var addBtn = $('[data-add-btn]', form);
  var sizeGroup = $('[data-size-group]');
  var sizeError = $('[data-size-error]');
  var stickyBar = $('[data-sticky-atc]');
  var stickySize = $('[data-sticky-size]');
  var stickyBtn = $('[data-sticky-btn]');
  var lastTrigger = addBtn;

  function selected() {
    var c = $('input[name="colour"]:checked', form);
    var s = $('input[name="size"]:checked', form);
    return { colour: c ? c.value : (colourInputs[0] && colourInputs[0].value), size: s ? s.value : null };
  }

  /* ---------- Gallery: filtered by colour; carousel (mobile) and thumbnail rail (900px+) ---------- */
  var gallery = (function () {
    var root = $('[data-gallery]');
    var track = $('[data-gallery-track]', root);
    var slides = $$('.gallery__slide', track);
    var thumbs = $$('[data-gallery-thumb]', root);
    var dotsBox = $('.gallery__dots', root);
    var count = $('[data-gallery-count]', root);
    var visible = slides;
    var index = 0;

    function setActive(i) {
      index = i;
      count.textContent = (i + 1) + ' / ' + visible.length;
      $$('[data-gallery-dot]', dotsBox).forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
      thumbs.forEach(function (t) { t.setAttribute('aria-current', 'false'); t.tabIndex = -1; });
      var current = thumbs[slides.indexOf(visible[i])];
      if (current) { current.setAttribute('aria-current', 'true'); current.tabIndex = 0; }
    }

    function goTo(i, instant) {
      i = Math.max(0, Math.min(visible.length - 1, i));
      track.scrollTo({ left: i * track.clientWidth, behavior: instant ? 'auto' : smooth });
      setActive(i);
    }

    // photos of the chosen colour plus the shared ("all") ones; a colour without photos shows the shared set
    function pick(colour) {
      var own = slides.some(function (s) { return s.getAttribute('data-colour') === colour; });
      var set = slides.filter(function (s) {
        var c = s.getAttribute('data-colour');
        return c === 'all' || (own && c === colour);
      });
      return set.length ? set : slides;
    }

    function showColour(colour) {
      visible = pick(colour);
      var name = COLOUR_NAME[colour] || '';
      slides.forEach(function (s, k) {
        var on = visible.indexOf(s) !== -1;
        s.hidden = !on;
        thumbs[k].parentNode.hidden = !on;
        if (on) {
          var n = visible.indexOf(s) + 1;
          s.setAttribute('aria-label', 'Photo ' + n + ' of ' + visible.length);
          thumbs[k].setAttribute('aria-label', 'Show photo ' + n + ' of ' + visible.length);
          var img = $('img', s);
          if (img) img.alt = NAME + (name ? ' in ' + name : '') + ', photo ' + n + ' of ' + visible.length;
        }
      });
      dotsBox.textContent = '';
      visible.forEach(function (s, k) {
        var d = document.createElement('button');
        d.type = 'button';
        d.className = 'gallery__dot';
        d.setAttribute('data-gallery-dot', '');
        d.setAttribute('aria-label', 'Show photo ' + (k + 1) + ' of ' + visible.length);
        d.addEventListener('click', function () { goTo(k); });
        dotsBox.appendChild(d);
      });
      track.scrollTo({ left: 0, behavior: 'auto' });
      setActive(0);
    }

    var ticking = false;
    track.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        ticking = false;
        var i = Math.round(track.scrollLeft / track.clientWidth);
        if (i !== index && i >= 0 && i < visible.length) setActive(i);
      });
    });
    thumbs.forEach(function (t, k) {
      t.addEventListener('click', function () { goTo(visible.indexOf(slides[k])); });
      t.addEventListener('keydown', function (e) {
        var i = visible.indexOf(slides[k]);
        var next = null;
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') next = i + 1;
        else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') next = i - 1;
        else if (e.key === 'Home') next = 0;
        else if (e.key === 'End') next = visible.length - 1;
        if (next === null) return;
        e.preventDefault();
        next = Math.max(0, Math.min(visible.length - 1, next));
        goTo(next);
        thumbs[slides.indexOf(visible[next])].focus();
      });
    });
    window.addEventListener('resize', function () { goTo(index, true); });
    return { showColour: showColour };
  })();

  /* ---------- Colour, size, stock, quantity ---------- */
  function hideSizeError() {
    sizeError.hidden = true;
    sizeGroup.removeAttribute('aria-invalid');
    sizeGroup.removeAttribute('aria-describedby');
  }

  function refresh() {
    var sel = selected();
    $('[data-colour-label]').textContent = COLOUR_NAME[sel.colour] || '';

    sizeInputs.forEach(function (inp) {
      var out = stockOf(sel.colour, inp.value) <= 0;
      inp.disabled = out;
      if (out && inp.checked) inp.checked = false;
      var oosText = $('[data-oos-text]', inp.parentNode);
      if (oosText) oosText.hidden = !out;
    });

    sel = selected();
    var n = sel.size ? stockOf(sel.colour, sel.size) : null;

    var msg, cls = '';
    if (allOut) { msg = 'Out of stock'; cls = 'is-out'; }
    else if (n === null) msg = 'Choose a size to see availability';
    else if (n >= 1 && n <= LOW) { msg = 'Only ' + n + ' left'; cls = 'is-low'; }
    else msg = 'In stock';
    stockNote.textContent = msg;
    stockNote.className = 'buy__stock' + (cls ? ' ' + cls : '');

    qtyGroup.setAttribute('data-qty-max', String(n === null ? 10 : Math.max(1, Math.min(10, n))));
    qtyInput.dispatchEvent(new Event('change', { bubbles: true }));   // app.js clamps the value and updates the +/- buttons

    addBtn.disabled = allOut;
    addBtn.textContent = allOut ? 'Out of stock' : 'Add to cart';
    stickyBtn.disabled = allOut;
    stickyBtn.textContent = allOut ? 'Out of stock' : (sel.size ? 'Add to cart' : 'Choose size');
    stickySize.textContent = sel.size ? 'Size ' + sel.size + ' · ' + COLOUR_NAME[sel.colour] : (allOut ? 'Currently unavailable' : 'Select a size');
    $('[data-sticky-price]').textContent = $('.price__now', form.parentNode).textContent;

    if (sel.size) hideSizeError();
  }

  var lastColour = null;
  form.addEventListener('change', function (e) {
    if (e.target.name === 'colour' || e.target.name === 'size') {
      var sel = selected();
      if (sel.colour !== lastColour) {
        lastColour = sel.colour;
        gallery.showColour(sel.colour);
        if (window.history && history.replaceState) {
          var u = new URL(location.href);
          u.searchParams.set('colour', sel.colour);
          history.replaceState(null, '', u.search + u.hash);
        }
      }
      refresh();
    }
  });

  // ?colour=<slug> deep link
  var wanted = params.get('colour');
  var wantedInput = wanted && colourInputs.filter(function (i) { return i.value === wanted; })[0];
  if (wantedInput) wantedInput.checked = true;
  lastColour = selected().colour;
  gallery.showColour(lastColour);
  refresh();

  function showSizeError() {
    sizeError.hidden = false;
    sizeGroup.setAttribute('aria-invalid', 'true');
    sizeGroup.setAttribute('aria-describedby', 'size-error stock-note');
    sizeGroup.focus();
    sizeGroup.scrollIntoView({ block: 'center', behavior: smooth });
  }

  form.addEventListener('submit', function (e) {
    var sel = selected();
    if (addBtn.disabled) { e.preventDefault(); return; }
    if (!sel.size) { e.preventDefault(); showSizeError(); return; }
    // The prototype adds to the demo cart drawer. In Laravel the form posts to the backend: remove data-demo-cart.
    if (form.hasAttribute('data-demo-cart') && window.KayaaCart) {
      e.preventDefault();
      window.KayaaCart.add({
        name: NAME,
        price: unitPrice(),
        tone: sel.colour,   // the thumbnail uses the selected colour's tone
        meta: 'Size ' + sel.size + ' · ' + COLOUR_NAME[sel.colour],
        qty: Math.max(1, parseInt(qtyInput.value, 10) || 1)
      }, lastTrigger);
    }
  });
  addBtn.addEventListener('click', function () { lastTrigger = addBtn; });

  /* Size guide panel */
  var guideBtn = $('[data-size-guide-toggle]');
  var guide = $('[data-size-guide]');
  guideBtn.addEventListener('click', function () {
    var open = guide.hidden;
    guide.hidden = !open;
    guideBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  /* ---------- Sticky add-to-cart bar (below 900px) ---------- */
  (function () {
    var mq = window.matchMedia('(max-width: 899px)');
    var buttonOut = false;
    function apply() {
      var on = mq.matches && buttonOut;
      stickyBar.classList.toggle('is-visible', on);
      stickyBar.inert = !on;
      document.body.classList.toggle('has-sticky-atc', on);
    }
    if ('IntersectionObserver' in window) {
      var header = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'), 10) || 61;
      new IntersectionObserver(function (entries) {
        buttonOut = !entries[0].isIntersecting;
        apply();
      }, { rootMargin: '-' + header + 'px 0px -64px 0px' }).observe(addBtn);
    }
    mq.addEventListener('change', apply);
    stickyBtn.addEventListener('click', function () {
      lastTrigger = stickyBtn;
      if (!selected().size) { showSizeError(); return; }
      if (form.requestSubmit) form.requestSubmit(); else form.dispatchEvent(new Event('submit', { cancelable: true }));
    });
  })();

  /* ---------- Reviews: "See more reviews" reveals the next 5 in place ----------
     Without JS the link goes to ?reviews=all#reviews (the server shows everything). */
  (function () {
    var items = $$('.reviews__list .review-card');
    var box = $('[data-reviews-more]');
    if (!items.length || !box) return;
    var STEP = 5;
    var countEl = $('[data-reviews-count]', box);
    var live = $('[data-reviews-live]', box);
    var link = $('[data-reviews-more-link]', box);
    var btn = null;

    function update() {
      var shown = items.filter(function (li) { return !li.hidden; }).length;
      countEl.textContent = 'Showing ' + shown + ' of ' + items.length;
      if (shown >= items.length && btn) btn.hidden = true;
    }

    if (params.get('reviews') === 'all') {
      items.forEach(function (li) { li.hidden = false; });
      if (link) link.hidden = true;
      update();
      return;
    }

    if (link) {   // progressive enhancement: the link becomes a button
      btn = document.createElement('button');
      btn.type = 'button';
      btn.className = link.className;
      btn.textContent = link.textContent;
      btn.setAttribute('data-reviews-more-btn', '');
      link.parentNode.replaceChild(btn, link);
      btn.addEventListener('click', function () {
        var batch = items.filter(function (li) { return li.hidden; }).slice(0, STEP);
        batch.forEach(function (li) { li.hidden = false; });
        update();
        if (live) live.textContent = batch.length + (batch.length === 1 ? ' more review shown' : ' more reviews shown');
        var first = batch[0] && $('article', batch[0]);
        if (first) first.focus();
      });
    }
    update();
  })();
})();
