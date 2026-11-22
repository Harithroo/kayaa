/* Kayaa Home, Shop by age: the outfit grows with the age. Ported from design/home-v2-concept.html (tuning unchanged). The nine ages, their
   shop-by-size URLs and the height and weight ranges come from <script id="age-data"> (written by tools/sync-shell.mjs from the catalogue
   and the size chart in docs/content/size-guide.md), so the explorer and the size guide always agree. */
(function () {
  'use strict';
  var H = window.KayaaHome, G = window.KayaaGarments;
  if (!H || !G) return;
  var $ = H.$, $$ = H.$$, RM = H.RM, COL = G.COL;
  var chips = $('#ageChips'), txt = $('#ageText'), g = $('#growG'), dataEl = $('#age-data');
  if (!chips || !txt || !g || !dataEl) return;
  var AGES;
  try { AGES = JSON.parse(dataEl.textContent); } catch (e) { return; }
  var cur = 1, shownG = null, hv = AGES[cur].h.slice(), wv = AGES[cur].w.slice();

  function band(el, lo, hi, min, max) { el.style.transform = 'translateX(' + ((lo - min) / (max - min) * 100) + '%) scaleX(' + ((hi - lo) / (max - min)) + ')'; }
  function fmt(n) { return (Math.round(n * 10) / 10).toString(); }
  function count(from, to, cb) {
    if (RM) { cb(to); return; }
    var t0 = performance.now();
    (function step(t) { var k = Math.min(1, (t - t0) / 450), e = 1 - Math.pow(1 - k, 3); cb([from[0] + (to[0] - from[0]) * e, from[1] + (to[1] - from[1]) * e]); if (k < 1) requestAnimationFrame(step); }(t0));
  }
  function colours(kind) { return kind === 'set' ? [COL.mist, COL.lilac] : kind === 'newborn' ? [COL.white, COL.lilac] : [COL.peri, COL.lilac]; }
  function render(i, anim) {
    var a = AGES[i];
    function fill() { $('#ageName').textContent = a.name; var s = $('#ageShop'); s.textContent = 'Shop ' + a.label; s.setAttribute('href', a.href); }
    if (anim && !RM) { txt.classList.add('out'); setTimeout(function () { fill(); txt.classList.remove('out'); }, 170); } else fill();
    var h0 = hv.slice(), w0 = wv.slice(); hv = a.h.slice(); wv = a.w.slice();
    function upd() { $('#ageMeta').innerHTML = 'Height <b>' + $('#hOut').textContent + '</b> · Weight <b>' + $('#wOut').textContent + '</b>'; }
    count(anim ? h0 : hv, hv, function (v) { $('#hOut').textContent = fmt(v[0]) + '–' + fmt(v[1]) + ' cm'; upd(); });
    count(anim ? w0 : wv, wv, function (v) { $('#wOut').textContent = fmt(v[0]) + '–' + fmt(v[1]) + ' kg'; upd(); });
    band($('#hBand'), a.h[0], a.h[1], 45, 105); band($('#wBand'), a.w[0], a.w[1], 2, 18);
    if (shownG !== a.g) { var c = colours(a.g); g.innerHTML = G.peggedSVG(a.g, c[0], c[1]); shownG = a.g; }
    g.style.transform = 'scale(' + (0.62 + i * 0.0475).toFixed(3) + ')';
    $('#growMark').textContent = a.label;
    if (anim && !RM) { g.classList.remove('swing'); void g.offsetWidth; g.classList.add('swing'); }
  }
  chips.addEventListener('click', function (e) {
    var b = e.target.closest('.age-chip'); if (!b) return;
    cur = +b.dataset.i;
    $$('.age-chip', chips).forEach(function (c) { c.setAttribute('aria-pressed', c === b); });
    render(cur, true);
  });
  render(cur, false);
}());
