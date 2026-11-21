# Home rail: how to replace the drawings with photographs

Each of the seven hangers on the Home category rail is a replaceable slot. Today it is a drawing made in the browser (`assets/js/home/garments.js`). When a photograph exists for a category it is used instead, with no code change.

## How the swap works

`tools/sync-shell.mjs` writes one `<a class="hanger" ...>` per category (the `gen: home-rail` region). For every category it looks for

```
html/assets/img/rail/{slug}.webp          slugs: newborn, bodysuits, sleepwear, sets, outerwear, napkins, accessories
```

and, when the file exists, adds `data-photo="assets/img/rail/{slug}.webp"` to that hanger. `home-rail.js` then shows the photo (`<img class="rail-photo" alt="" width="140" height="230">`, `object-fit: contain`) instead of the drawing, and skips the drawn hook behind the rod (the photograph carries its own hook). Delete the file and run `node tools/sync-shell.mjs` to go back to the drawing. In Blade, do the same check with `file_exists(public_path(...))` or a `rail_photo` column.

Nothing else changes: the hanger still hangs side-on at rest, turns to face front, scales, swings and slides about the hook, and the caption, the link and the accessible name are the same.

## Preparing the cut-outs

- **One garment per file**, front-facing, **hanging on a hanger**, hanging naturally (not stretched flat, not on a mannequin).
- **The same hanger for all seven** (white or pale lilac plastic, no logos). Clip hangers (a straight bar with two clips) for Napkins and Accessories, as in the drawings.
- **A plain, light background, even soft light** from the front and above: no hard shadows, no coloured light. Shoot every piece at the same distance and height.
- **Transparent background.** Remove everything except the garment and the hanger. Check the edges on the pale lilac wall (`#F4EEF8`): no white or dark halos.
- **The hook at the top centre** of the image. The rail draws the hook at x = 50%, 5% from the top of a 140 x 230 box, so crop the photograph to that proportion (about 0.61 wide to 1 tall) with the hook centred and a little empty space around the garment.
- **A consistent scale.** Photograph all seven at one magnification and crop each to the same 140:230 box, so a newborn bodysuit looks smaller than a sleepsuit, as the drawings do.
- **Export a transparent WebP** (quality 70 to 80), about **60 KB each**, about **700 px wide x 1150 px tall** (5x the 140 x 230 box is plenty; the page shows it at 126 to 172 px wide).
- Colours: lilac, periwinkle, white and soft pastels read best against the wall. No green, yellow, orange, peach or brown garments in this block.

## The side-on turn

At rest every hanger is turned side-on (`rotateY` about 64 degrees) and shaded by its angle, then turns to face you. A flat front-facing photograph handles this well because it is only rotated, not re-drawn; keep the garment roughly symmetric around the hook so the turn looks natural.

## Checking

Run `node tools/sync-shell.mjs` and open the Home page: the rail should look the same as with the drawings, with photographs on the hangers. `node tools/qa/home.mjs` (the rail checks) must still pass, and `node tools/check-budget.mjs` does not count images, so check the Lighthouse mobile run: the rail is below the fold, so the photographs should not change the Home LCP.
