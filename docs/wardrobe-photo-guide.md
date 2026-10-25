# Wardrobe carousel: how to prepare the photographs

Prototype only (see `html/mockups/wardrobe.html`). This guide is for the day the client approves the wardrobe idea and the seven illustrations are replaced by real photographs of garments hanging on hangers.

## What the page expects

Each of the seven categories has ONE replaceable artwork element, `.art`. Today it is an inline SVG drawing. Later it can be a transparent WebP (or AVIF) cut-out:

```html
<img class="art" src="../assets/img/wardrobe/bodysuits.webp" alt="" width="800" height="1000" loading="lazy" decoding="async">
```

Everything that positions it lives in per-item CSS custom properties on the `<li class="wardrobe__item">` (they are written by the `gen: wardrobe-items` region in `tools/catalogue.mjs` today; in Blade they become inline `style` values or a small data table). **No script knows about sizes.**

| Property | Meaning | Today (Bodysuits) |
| --- | --- | --- |
| `--aw`, `--ah` | Width and height of the artwork box, in cells (one cell is the width of one hanger slot, about 130px on a 1280px screen) | 1.07, 1.35 |
| `--hx`, `--hy` | The hook ring's position INSIDE the artwork, as fractions of its width and height (0.5 and about -0.04 means: centred, just above the top edge) | 0.5, -0.037 |
| `--sx`, `--sy` | Offset of the soft shadow on the wall behind the piece, in cells | 0.04, 0.06 |
| `--hs` | Hover scale of this piece (1.5 in the reference) | 1.5 |

To try photographs without touching the page, put the files at `html/assets/img/wardrobe/{slug}.webp`, list the slugs in the `data-photos` attribute on the wardrobe (`data-photos="newborn,bodysuits,..."`), and choose "Art: Photo placeholder" in the mock controls: listed slugs show their photograph, the others show a flat grey silhouette that uses the same values, so you can check the layout before any photograph exists.

## Shooting

- One garment per photograph, **front-facing, on a hanger**, hanging naturally (not stretched flat, not on a mannequin).
- **The same hanger for all seven** (a white or pale-lilac plastic hanger, no logos). Clip hangers (a straight bar with two clips) for the cloth nappy, the set's trousers and the mittens, the same clip hanger for all three.
- **A plain, light, even background** (white or very pale grey) and soft, even light from the front and above: no hard shadows, no coloured light. Shoot at the same distance and the same height for every piece.
- Keep real proportions: a newborn bodysuit must look smaller than a sleepsuit. Do not scale each photograph to the same height; shoot them at one scale (the `--aw` and `--ah` values then carry the true size).
- Colours: lilac, periwinkle, white and soft pastels read best against the pale lilac wall. Avoid green, yellow, orange and brown garments in this block.

## Preparing the cut-outs

1. Remove the background so the PNG/WebP is **transparent** around the garment and the hanger. Keep the hanger's hook (the curl) at the very top.
2. Crop to the hook and the garment with about 4% of empty space on every side. **The hook must be at the top centre** of the image (so `--hx` is 0.5 and `--hy` is about 0.02). If the hook is off-centre, note its position as a fraction and set `--hx` for that piece.
3. Export **1600px tall** (width follows the garment), transparent **WebP at about 60 KB** each (quality 70 to 80; check the edges for halos on the lilac wall). AVIF is welcome as a smaller alternative with a WebP fallback.
4. Name the files after the category slugs: `newborn`, `bodysuits`, `sleepwear`, `sets`, `outerwear`, `napkins`, `accessories`.
5. Write the `alt` text as empty (`alt=""`): the link already has the name "Bodysuits, 5 items" and the caption carries the same text, so the picture is decoration.

## What stays the same

The hook ring (radius of the rod plus 2.5px, back half behind the rod, front half in front), the pendulum, the intro, the hover scale and the caption all keep working: they use the ring centre as their pivot, and the artwork is positioned so its hook sits on that centre. Tests: `tools/qa` does not cover the mock-up; the checks used while building it (ring centre within 0.5px of the rod, caption clear of every garment by 16px at 13 widths) can be re-run against the photographs.

## Performance

Seven images at about 60 KB is about 420 KB. Load them `loading="lazy"` except the first three, give each its `width` and `height`, and keep the artwork box size in CSS (it already is), so the page never shifts (CLS 0).
