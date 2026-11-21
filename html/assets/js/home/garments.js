/* Kayaa Home: the garment drawings, the fabric textures and the rail hanger, shared by the preloader, the hero washing line, the shop-by-age
   outfit and the category rail. One file, one namespace: window.KayaaGarments. Ported from design/home-v2-concept.html (tuning unchanged).
   Classic script (no modules) so it works over file://. A garment is a replaceable slot: the rail uses a photo cut-out instead when its
   hanger carries data-photo (see docs/rail-photo-guide.md). No green, yellow, orange, peach or brown anywhere in the palette below. */
(function () {
  'use strict';
  var NS = 'http://www.w3.org/2000/svg';
  var G = window.KayaaGarments = window.KayaaGarments || {};

  /* ---------- fabric textures: 48px tiles drawn on canvas (dark threads, light highlights, seamless) ---------- */
  var TEX = {};
  /* software canvases (willReadFrequently): these are only drawn once and read back (toDataURL, getImageData); the GPU path costs ~0.7s of blocking time on a phone-class device */
  function mk(w, h) { var c = document.createElement('canvas'); c.width = w; c.height = h; return c; }
  function rnd(seed) { var s = seed || 1; return function () { s = (s * 16807) % 2147483647; return (s - 1) / 2147483646; }; }
  function tile(kind) {
    var c = mk(48, 48), x = c.getContext('2d', { willReadFrequently: true }), r = rnd(kind.length * 977), i, j;
    x.lineCap = 'round';
    if (kind === 'rib') {
      for (i = 0; i < 48; i += 8) {
        x.fillStyle = 'rgba(61,53,71,.16)'; x.fillRect(i + 5, 0, 3, 48);
        x.fillStyle = 'rgba(255,255,255,.45)'; x.fillRect(i + 1, 0, 2, 48);
        for (j = 0; j < 48; j += 4) { x.fillStyle = 'rgba(61,53,71,' + (0.04 + r() * 0.05) + ')'; x.fillRect(i + 3, j, 2, 2); }
      }
    } else if (kind === 'jersey') {
      for (j = 0; j < 48; j += 6) for (i = 0; i < 48; i += 6) {
        x.strokeStyle = 'rgba(61,53,71,.17)'; x.lineWidth = 1.1; x.beginPath(); x.moveTo(i + 0.8, j + 1); x.lineTo(i + 3, j + 5); x.lineTo(i + 5.2, j + 1); x.stroke();
        x.strokeStyle = 'rgba(255,255,255,.5)'; x.beginPath(); x.moveTo(i + 1.6, j + 0.6); x.lineTo(i + 3, j + 3.4); x.stroke();
      }
    } else if (kind === 'waffle') {
      for (j = 0; j < 48; j += 12) for (i = 0; i < 48; i += 12) {
        x.fillStyle = 'rgba(61,53,71,.08)'; x.fillRect(i, j, 12, 2); x.fillRect(i, j, 2, 12);
        x.fillStyle = 'rgba(255,255,255,.5)'; x.fillRect(i + 2.5, j + 2.5, 7, 1.5); x.fillRect(i + 2.5, j + 2.5, 1.5, 7);
        x.fillStyle = 'rgba(61,53,71,.1)'; x.fillRect(i + 9.5, j + 3, 1.5, 7.5); x.fillRect(i + 3, j + 9.5, 8, 1.5);
      }
    } else if (kind === 'muslin') {
      for (i = 0; i < 48; i += 3) { x.fillStyle = 'rgba(61,53,71,' + (0.03 + r() * 0.05) + ')'; x.fillRect(i, 0, 1, 48); x.fillRect(0, i, 48, 1); }
      for (i = 0; i < 5; i++) {   // slubs and crinkle
        var y = r() * 48; x.strokeStyle = 'rgba(255,255,255,.55)'; x.lineWidth = 1.4; x.beginPath(); x.moveTo(0, y);
        for (j = 0; j <= 48; j += 8) x.lineTo(j, y + Math.sin(j * 0.25 + i) * 2.2); x.stroke();
        var y2 = r() * 48; x.strokeStyle = 'rgba(61,53,71,.08)'; x.lineWidth = 1; x.beginPath(); x.moveTo(0, y2);
        for (j = 0; j <= 48; j += 8) x.lineTo(j, y2 + Math.cos(j * 0.3 + i) * 2); x.stroke();
      }
    } else if (kind === 'linen') {
      for (i = 0; i < 48; i += 2) { x.fillStyle = 'rgba(61,53,71,' + (0.04 + r() * 0.09) + ')'; x.fillRect(0, i, 48, 1); x.fillStyle = 'rgba(61,53,71,' + (0.03 + r() * 0.06) + ')'; x.fillRect(i, 0, 1, 48); }
    }
    return c;
  }
  ['rib', 'jersey', 'waffle', 'muslin', 'linen'].forEach(function (k) { TEX[k] = tile(k); });

  /* The SVG <pattern> defs (and the soft shading gradient) are written ONCE per page, in a hidden svg every drawing refers to */
  function ensureDefs() {
    if (document.getElementById('kayaa-fab-defs')) return;
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('width', '0'); svg.setAttribute('height', '0'); svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('focusable', 'false');
    svg.style.position = 'absolute';
    var defs = document.createElementNS(NS, 'defs'); defs.id = 'kayaa-fab-defs';
    defs.innerHTML = '<linearGradient id="gshade" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".5"/><stop offset=".42" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#3D3547" stop-opacity=".16"/></linearGradient>';
    Object.keys(TEX).forEach(function (k) {
      var p = document.createElementNS(NS, 'pattern'); p.setAttribute('id', 'tx-' + k); p.setAttribute('patternUnits', 'userSpaceOnUse'); p.setAttribute('width', '12'); p.setAttribute('height', '12');
      var im = document.createElementNS(NS, 'image'); im.setAttribute('width', '12'); im.setAttribute('height', '12'); im.setAttribute('preserveAspectRatio', 'none'); im.setAttribute('href', TEX[k].toDataURL());
      p.appendChild(im); defs.appendChild(p);
    });
    svg.appendChild(defs); document.body.insertBefore(svg, document.body.firstChild);
  }

  /* A larger texture with colour, for the swatch dots (and, later, the cloth swatch) */
  function clothTexture(kind, base, size) {
    size = size || 512; var c = mk(size, size), x = c.getContext('2d', { willReadFrequently: true });
    x.fillStyle = base; x.fillRect(0, 0, size, size);
    var sc = kind === 'waffle' ? 2.6 : kind === 'rib' ? 2.2 : 2, t = TEX[kind];
    x.save(); x.scale(sc, sc); x.fillStyle = x.createPattern(t, 'repeat'); x.fillRect(0, 0, size / sc, size / sc); x.restore();
    var r = rnd(size + kind.length), i, img = x.getImageData(0, 0, size, size), d = img.data;
    for (i = 0; i < d.length; i += 4) { var n = (r() - 0.5) * 10; d[i] += n; d[i + 1] += n; d[i + 2] += n; }
    x.putImageData(img, 0, 0);
    if (size >= 256) { x.strokeStyle = 'rgba(110,90,140,.45)'; x.lineWidth = 3; x.setLineDash([10, 8]); x.strokeRect(22, 22, size - 44, size - 44); }   // a stitched hem
    return c;
  }

  /* ---------- garments: one 140 x 230 drawing box; on a hanger the hook sits at (70,12) ---------- */
  var COL = {
    white: { m: '#FFFFFF', i: '#E9E1F0', t: '#C3AFDA' },
    lilac: { m: '#DCCDED', i: '#BCA8D6', t: '#8E79B0' },
    peri: { m: '#C6D6EC', i: '#A3BADA', t: '#7790B8' },
    mist: { m: '#E6EEF8', i: '#C7D6EA', t: '#90A7C9' },
    plum: { m: '#BCA6D6', i: '#9C86BC', t: '#6E5A8C' },
    cloud: { m: '#EEE9F3', i: '#D3CAE0', t: '#9F95B2' },
    rose: { m: '#E8D9EE', i: '#CDB8D9', t: '#9A7FAE' }
  };
  var OL = 'stroke="#6E5A8C" stroke-opacity=".55" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"';
  function fab(d, fill, tex) { return '<path d="' + d + '" fill="' + fill + '"/>' + (tex ? '<path d="' + d + '" fill="url(#tx-' + tex + ')" opacity=".7"/>' : '') + '<path d="' + d + '" fill="url(#gshade)"/><path d="' + d + '" fill="none" ' + OL + '/>'; }
  function star(cx, cy, r) { var p = [], k; for (k = 0; k < 10; k++) { var a = -Math.PI / 2 + k * Math.PI / 5, rr = k % 2 ? r * 0.45 : r; p.push((cx + Math.cos(a) * rr).toFixed(1) + ',' + (cy + Math.sin(a) * rr).toFixed(1)); } return p.join(' '); }
  var D = {
    bodysuit: 'M57 46 Q70 61 83 46 L100 52.5 L111 71 L97 78 L92 70 L92 117 Q92 130 81 138 L76 148 Q70 152 64 148 L59 138 Q48 130 48 117 L48 70 L43 78 L29 71 L40 52.5 Z',
    sleepsuit: 'M57 46 Q70 62 83 46 L100 52.5 L114 96 Q115 103 109 103.5 L103 103.5 L93 74 L93 128 L95.5 176 Q96 187 88 188.5 L78.5 188.5 Q73.5 187 73.5 180 L72 141 L70 139 L68 141 L66.5 180 Q66.5 187 61.5 188.5 L52 188.5 Q44 187 44.5 176 L47 128 L47 74 L37 103.5 L31 103.5 Q25 103 26 96 L40 52.5 Z',
    cardigan: 'M60 47 L70 82 L80 47 L100 53 L116 112 Q117 118 111 119 L104 120 L95 82 L96 130 Q96 136 90 136 L50 136 Q44 136 44 130 L45 82 L36 120 L29 119 Q23 118 24 112 L40 53 Z',
    tee: 'M58 47 Q70 59 82 47 L101 53 L112 72 L98 78 L93 71 L93 110 Q70 114 47 110 L47 71 L42 78 L28 72 L39 53 Z',
    shorts: 'M50 106 L90 106 L95 150 L75.5 152 L70 132 L64.5 152 L45 150 Z',
    napA: 'M32 40 L104 40 L104 98 L88 112 L38 112 Q32 112 32 106 Z',
    napB: 'M46 40 L106 40 Q112 40 112 46 L112 112 Q112 118 106 118 L46 118 Q40 118 40 112 L40 46 Q40 40 46 40Z',
    bib: 'M26 40 Q34 40 36 47 Q44 57 52 47 Q54 40 62 40 L62 70 Q62 95 44 97 Q26 95 26 70 Z',
    mitL: 'M81 46 L95 46 L95 74 Q95 82 88 82 Q81 82 81 74 Z',
    mitR: 'M97 46 L111 46 L111 74 Q111 82 104 82 Q97 82 97 74 Z',
    thumbL: 'M76.5 66 a3.5 5.5 0 1 0 7 0 a3.5 5.5 0 1 0 -7 0Z',
    thumbR: 'M108.5 66 a3.5 5.5 0 1 0 7 0 a3.5 5.5 0 1 0 -7 0Z'
  };
  var TEXOF = { newborn: 'jersey', bodysuit: 'rib', sleepsuit: 'jersey', cardigan: 'waffle', set: 'linen', napkins: 'muslin', accessories: 'muslin' };
  function garment(type, c, c2) {
    c = c || COL.white; c2 = c2 || c; var tx = TEXOF[type], g = '';
    switch (type) {
      case 'bodysuit': case 'newborn':
        g = '<path d="M57 46 Q70 61 83 46 L83 60 L57 60Z" fill="' + c.i + '"/>' + fab(D.bodysuit, c.m, tx) +
          '<path d="M57.5 46.5 Q70 61 82.5 46.5" fill="none" stroke="' + c.t + '" stroke-width="2.6" stroke-linecap="round"/>' +
          '<path d="M31 70.5 L43.5 77 M109 70.5 L96.5 77 M59.5 137.5 Q50 131 48.8 121 M80.5 137.5 Q90 131 91.2 121" fill="none" stroke="' + c.t + '" stroke-width="2.2" stroke-linecap="round"/>' +
          '<circle cx="65.5" cy="145" r="1.5" fill="#fff" ' + OL + '/><circle cx="70" cy="146.6" r="1.5" fill="#fff" ' + OL + '/><circle cx="74.5" cy="145" r="1.5" fill="#fff" ' + OL + '/>';
        g += type === 'newborn' ? '<polygon points="' + star(79, 88, 6) + '" fill="' + c.t + '" opacity=".9"/>' : '<circle cx="70" cy="66" r="1.4" fill="#fff" ' + OL + '/><circle cx="70" cy="73" r="1.4" fill="#fff" ' + OL + '/>';
        return g;
      case 'sleepsuit':
        return '<path d="M57 46 Q70 62 83 46 L83 60 L57 60Z" fill="' + c.i + '"/>' + fab(D.sleepsuit, c.m, tx) +
          '<path d="M57.5 46.5 Q70 62 82.5 46.5" fill="none" stroke="' + c.t + '" stroke-width="2.6" stroke-linecap="round"/>' +
          '<path d="M70 62 L70 138" stroke="' + c.t + '" stroke-width="1.6" stroke-dasharray="2 2.2"/><circle cx="70" cy="64" r="2" fill="' + c.t + '"/>' +
          '<path d="M84 79 a7.5 7.5 0 1 0 5.5 12.8 a6 6 0 1 1 -5.5 -12.8Z" fill="' + c.t + '" opacity=".85"/>' +
          '<path d="M45 180 Q52 184 62 181 M75 181 Q86 184 95 180" fill="none" stroke="' + c.t + '" stroke-width="5" stroke-linecap="round" opacity=".55"/>' +
          '<path d="M27.5 97 L37.5 102.5 M112.5 97 L102.5 102.5" stroke="' + c.t + '" stroke-width="2.2" stroke-linecap="round"/>';
      case 'cardigan':
        var rib = ''; for (var x = 48; x < 95; x += 4) rib += '<line x1="' + x + '" y1="129" x2="' + x + '" y2="135" stroke="' + c.t + '" stroke-width="1" opacity=".5"/>';
        return '<path d="M60 47 L80 47 L70 72Z" fill="' + c.i + '"/>' + fab(D.cardigan, c.m, tx) +
          '<path d="M60.5 48 L70 81 L79.5 48" fill="none" stroke="' + c.t + '" stroke-width="3" stroke-linejoin="round" opacity=".7"/><path d="M70 82 L70 136" stroke="#6E5A8C" stroke-opacity=".45" stroke-width="1.3"/>' +
          '<circle cx="73.5" cy="92" r="2.4" fill="#fff" ' + OL + '/><circle cx="73.5" cy="106" r="2.4" fill="#fff" ' + OL + '/><circle cx="73.5" cy="120" r="2.4" fill="#fff" ' + OL + '/>' +
          '<path d="M46 128 L94.5 128" stroke="' + c.t + '" stroke-width="1" opacity=".6"/>' + rib +
          '<path d="M25.5 111 L35.5 115.5 M114.5 111 L104.5 115.5" stroke="' + c.t + '" stroke-width="3" stroke-linecap="round" opacity=".6"/>' +
          '<rect x="50" y="98" width="13" height="12" rx="2.5" fill="none" stroke="' + c.t + '" stroke-width="1.4" opacity=".7"/>';
      case 'set':
        return fab(D.shorts, c2.m, 'jersey') +
          '<path d="M50 112 L90 112" stroke="' + c2.t + '" stroke-width="2" opacity=".7"/><path d="M46 146 L64.8 148 M75.2 148 L94 146" stroke="' + c2.t + '" stroke-width="2.2" stroke-linecap="round"/>' +
          '<path d="M58 47 Q70 59 82 47 L82 58 L58 58Z" fill="' + c.i + '"/>' + fab(D.tee, c.m, tx) +
          '<path d="M58.5 47.5 Q70 59 81.5 47.5" fill="none" stroke="' + c.t + '" stroke-width="2.6" stroke-linecap="round"/>' +
          '<path d="M30 71 L42.5 77 M110 71 L97.5 77" stroke="' + c.t + '" stroke-width="2.2" stroke-linecap="round"/>' +
          '<rect x="76" y="76" width="10" height="11" rx="2" fill="none" stroke="' + c.t + '" stroke-width="1.5"/>';
      case 'napkins':
        var st = ''; for (var y = 50; y < 114; y += 9) st += '<line x1="41" y1="' + y + '" x2="111" y2="' + y + '" stroke="' + c2.t + '" stroke-width="1.4" opacity=".5"/>';
        return fab(D.napB, c2.m, 'muslin') + st + fab(D.napA, c.m, tx) +
          '<path d="M104 98 L88 112 L90 98 Z" fill="' + c.i + '" ' + OL + '/>' +
          '<path d="M37 45 L99 45 L99 95 M37 45 L37 107" fill="none" stroke="' + c.t + '" stroke-width="1.4" stroke-dasharray="3 2.6" opacity=".8"/>' +
          '<polygon points="' + star(68, 76, 7) + '" fill="' + c.t + '" opacity=".55"/>';
      case 'accessories':
        return fab(D.bib, c.m, tx) +
          '<path d="M30 70 Q30 90 44 92 Q58 90 58 70" fill="none" stroke="' + c.t + '" stroke-width="2" stroke-dasharray="3 2.5"/>' +
          '<path d="M44 70 m-6 0 a6 6 0 0 1 12 0 c0 5 -6 8 -6 8 s-6 -3 -6 -8Z" fill="' + c.t + '" opacity=".55"/>' +
          '<path d="M88 46 Q96 52 104 46" fill="none" stroke="#9D8FB5" stroke-width="1.4"/>' +
          fab(D.thumbL, c2.m, 'jersey') + fab(D.mitL, c2.m, 'jersey') + '<path d="M81 52 L95 52" stroke="' + c2.t + '" stroke-width="3" opacity=".6"/>' +
          fab(D.thumbR, c2.m, 'jersey') + fab(D.mitR, c2.m, 'jersey') + '<path d="M97 52 L111 52" stroke="' + c2.t + '" stroke-width="3" opacity=".6"/>';
    }
    return '';
  }
  var VB = { bodysuit: '24 40 92 116', newborn: '24 40 92 116', sleepsuit: '20 40 100 152', cardigan: '18 40 104 100', set: '22 40 96 116', napkins: '26 34 92 88', accessories: '20 34 100 70' };
  function garmentSVG(type, c, c2) { return '<svg viewBox="' + VB[type] + '" aria-hidden="true" focusable="false">' + garment(type, c, c2) + '</svg>'; }

  /* a garment pinned to a line by two pegs (the hero washing line and the shop-by-age outfit) */
  var PEGS = '<rect x="40" y="40" width="5" height="15" rx="1.5" fill="#F3EEF8" stroke="#6E5A8C" stroke-opacity=".5"/><rect x="95" y="40" width="5" height="15" rx="1.5" fill="#F3EEF8" stroke="#6E5A8C" stroke-opacity=".5"/>';
  function peggedSVG(type, c, c2) { return '<svg viewBox="20 30 100 170" aria-hidden="true" focusable="false">' + garment(type, c, c2) + PEGS + '</svg>'; }

  /* ---------- the hanger and the hook (the rail) ---------- */
  var HANGER_STD = '<path d="M70 31 C66 33 38 46 29 52.5 C25 55 26 58.5 30 58.5 L110 58.5 C114 58.5 115 55 111 52.5 C102 46 74 33 70 31 Z" fill="#FFFFFF" ' + OL + '/><rect x="67" y="27.5" width="6" height="5" rx="1.5" fill="#FFFFFF" ' + OL + '/>';
  var HANGER_CLIP = '<rect x="24" y="30" width="92" height="6" rx="3" fill="#FFFFFF" ' + OL + '/><rect x="67" y="25.5" width="6" height="5" rx="1.5" fill="#FFFFFF" ' + OL + '/>';
  function clips() { return [44, 96].map(function (x) { return '<rect x="' + (x - 4) + '" y="33" width="8" height="13" rx="2" fill="#D9D1E4" ' + OL + '/><line x1="' + (x - 2) + '" y1="40" x2="' + (x + 2) + '" y2="40" stroke="#6E5A8C" stroke-opacity=".45" stroke-width="1"/>'; }).join(''); }
  var HOOK_FRONT = '<path d="M70 31 C70 25 63.6 20.5 63.6 12.5 A6.4 6.4 0 0 1 76.2 11" fill="none" stroke="#857C99" stroke-width="2.7" stroke-linecap="round"/><path d="M65.2 9 A5.2 5.2 0 0 1 72.5 6.6" fill="none" stroke="#fff" stroke-opacity=".8" stroke-width="1" stroke-linecap="round"/>';
  var HOOK_BACK = '<path d="M76.2 11 C76.6 15.5 75.2 18.6 72.4 19.8" fill="none" stroke="#6F6684" stroke-width="2.6" stroke-linecap="round"/>';
  /* it = {type, c, c2} with colour OBJECTS; the 140 x 230 box has the hook at (70,12) */
  function railSVG(it) {
    var clip = (it.type === 'napkins' || it.type === 'accessories');
    var body = clip ? HANGER_CLIP + garment(it.type, it.c, it.c2) + clips() : HANGER_STD + garment(it.type, it.c, it.c2);
    if (it.type === 'newborn') body = '<g transform="translate(70 31) scale(.84) translate(-70 -31)">' + body + '</g>';
    return '<svg viewBox="0 0 140 230" aria-hidden="true" focusable="false">' + HOOK_FRONT + '<line x1="70" y1="24" x2="70" y2="' + (clip ? 27 : 29) + '" stroke="#857C99" stroke-width="2.4"/>' + body + '</svg>';
  }
  function hookBackSVG() { return '<svg viewBox="0 0 140 230" aria-hidden="true" focusable="false">' + HOOK_BACK + '</svg>'; }

  G.COL = COL; G.TEX = TEX; G.ensureDefs = ensureDefs; G.clothTexture = clothTexture;
  G.garment = garment; G.garmentSVG = garmentSVG; G.peggedSVG = peggedSVG; G.railSVG = railSVG; G.hookBackSVG = hookBackSVG;
  ensureDefs();
}());
