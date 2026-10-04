# Design-system and colour audit

Run with `node tools/qa/design-audit.mjs` (Chromium, over http, every page and key state at 375 and 1280: 58 pages and states, about 100,000 colour readings). It reads the **computed** colours of every visible element (text, background, borders, outlines, SVG fill and stroke, underlines, box shadows, and the `::before` and `::after` of each element), maps each one to a token in `html/assets/css/tokens.css`, and compares font sizes, padding, gaps and radii with the token scales. The hidden icon sprite is not drawn, so it is skipped.

## Summary

| Question | Answer |
|---|---|
| Distinct colours drawn | 37 |
| On a token (exactly, or a token at partial opacity, or the growth-step tints) | 35 |
| Off token | 2, both a border colour caught half-way through a hover transition (see below) |
| Pure black (`#000`) | None |
| Green hues (70-170 degrees) | None |
| Yellow, orange or peach hues (20-65 degrees) | One: `#F3E9DC` (`--swatch-cream`), only on the Butter product swatch dot and on product-photo placeholders. Allowed by the brief. |
| Font sizes off the scale | 1 value, justified below |
| Spacing off the scale | 18 values, justified below |
| Radii off the scale | 0 (four tokens added) |

## What the audit changed

| Finding | Fix |
|---|---|
| `--ink-soft` `#79708A` is 4.4:1 on the page background and 3.7:1 on the lilac footer; AA needs 4.5:1. 526 axe contrast failures. | Darkened to `#635C71` (4.5:1 or better on Bg, Secondary and the lilac tints). **Needs the client or designer to confirm**; the design file still shows the old value. |
| `--error` `#C1584B` is 4.4:1 as text on white and under white button text. | Darkened to `#A34A3F`. Same confirmation needed. |
| `.topbar--cream` (the admin could choose a cream announcement bar) used `--cream` `#FBF6EE`, a yellowish peach. | Removed the variant. The announcement bar is `lilac` or `sky`. The contract and the partial were updated. |
| Category-tile label pill (shown only when a real photo exists) used `--cream`. | Now white (`--surface`). |
| `--cream` is still defined | Only as the tint of the Butter product-colour placeholders. The token comment says so. |
| Growth-step tiles used a literal `clamp()` for the label size; buttons used a literal `15px`. | New tokens `--fs-step` and `--fs-ui-lg`. |
| Content markers and the checkbox used literal radii 4px, 6px and 8px; the mega menu panel used 24px. | New tokens `--radius-xs`, `--radius-sm`, `--radius-md`, `--radius-xl`. |

Colour hue check uses HSL: a colour is flagged when its hue is 70-170 or 20-65 degrees, its saturation is 15% or more, and it is not transparent. Neutral lilacs sit at 264-272 degrees. The pinks (`#EFD3D8` Blush swatch, `#FBEFED` error tint) sit at 351-6 degrees, outside both ranges.

## Every computed colour and its token

| Computed colour | Hex | Token | Uses |
|---|---|---|---:|
| rgb(61, 53, 71) | #3D3547 | --ink | 38966 |
| rgb(110, 90, 140) | #6E5A8C | --primary-deep | 28512 |
| rgb(233, 225, 240) | #E9E1F0 | --line | 17508 |
| rgb(99, 92, 113) | #635C71 | --ink-soft | 7304 |
| rgb(255, 255, 255) | #FFFFFF | --surface | 4678 |
| rgb(250, 247, 251) | #FAF7FB | --bg | 1080 |
| rgb(163, 74, 63) | #A34A3F | --error | 902 |
| rgba(61, 53, 71, 0.18) | #3D3547 | --ink at 18% opacity | 640 |
| rgb(234, 224, 242) | #EAE0F2 | --secondary, --tone-1 | 565 |
| rgb(195, 175, 218) | #C3AFDA | --primary, --swatch-lilac | 537 |
| rgb(242, 235, 247) | #F2EBF7 | --tone-2 | 529 |
| rgb(217, 203, 233) | #D9CBE9 | --tone-4 | 250 |
| rgb(225, 213, 238) | #E1D5EE | --tone-3 | 193 |
| rgba(61, 53, 71, 0.25) | #3D3547 | --ink at 25% opacity | 159 |
| rgb(243, 233, 220) | #F3E9DC | --swatch-cream | 136 |
| rgba(110, 90, 140, 0.2) | #6E5A8C | --line-strong | 134 |
| rgb(169, 194, 224) | #A9C2E0 | --accent, --swatch-sky | 84 |
| rgba(61, 53, 71, 0.4) | #3D3547 | --scrim | 78 |
| rgba(61, 53, 71, 0.08) | #3D3547 | --scrim-light | 39 |
| rgb(221, 231, 243) | #DDE7F3 | --sky-tint | 20 |
| rgb(239, 211, 216) | #EFD3D8 | --swatch-blush | 20 |
| rgb(251, 239, 237) | #FBEFED | --error-tint | 18 |
| eight colours from rgb(238, 230, 245) to rgb(210, 194, 227) | #EEE6F5 to #D2C2E3 | `--primary` mixed into `--tone-2` with `color-mix` (the nine growth-step tints; step 0 is `--tone-2` itself) | 8 each |
| rgba(110, 90, 140, 0.15), 0.08, 0.06, 0.02 | #6E5A8C | --primary-deep at low opacity (focus and button shadows) | 4, 1, 1, 1 |
| rgba(61, 53, 71, 0.45) | #3D3547 | --ink at 45% opacity (dialog shadow) | 2 |
| rgb(189, 176, 204), rgb(214, 204, 225) | #BDB0CC, #D6CCE1 | **off token**: the checkout phone field's border caught half-way between `--line` and the hover or focus border during the 150ms transition. Not a design colour. | 4 each |

## Font sizes

Token scale at the audited widths (px): 12.5 (`--fs-caption`), 14 (`--fs-ui`), 14.5 (`--fs-dense`), 15 (`--fs-ui-lg`), 16 (`--fs-body`), 17 to 28 and 36 and 48 (the fluid heading sizes, which resolve to 17.0, 21.0, 27.0, 36.0 at 375px and larger at 1280px), 18 to 22 (`--fs-step`).

| Value | Where | Verdict |
|---|---|---|
| 13px | The centred "Quick add" pill on a product card, shown only to mouse users. | Justified. It is the value in the design file; touch devices get a 44px round button with a visually hidden label, so the 14px touch floor does not apply. Commented in `components.css`. |

## Spacing (padding and gaps)

The scale is `--space-1..8` = 4, 8, 12, 16, 24, 32, 48, 64. Values outside it, and why they stay:

| Value | Count | Where | Verdict |
|---|---:|---|---|
| 1px | 324 | content marker (`mark`) vertical padding | Optical: a 1px lift so the dashed outline does not touch the text. |
| 2px | 2511 | gaps in the tab bar items, small icon rows, pagination | Hairline gap between an icon and its caption. |
| 3px | 20 | gallery counter padding | Optical, matches the 3px/9px pill proportions. |
| 5px | 532 | badge and status pill vertical padding | Gives a 24px-tall pill with 14px text (5 + 14 + 5). |
| 6px | 2228 | marker horizontal padding, container bottom spacing, tight icon gaps | Half-step used between 4 and 8; kept where 4 looked cramped and 8 loose. |
| 9px, 13px, 14px | 48, 188, 1825 | vertical padding of buttons and of padded inline links | Tap-target arithmetic: 14 + 16 (line) + 14 = 44px and 13px padding on a 19px link line also gives 44px. These are the numbers that make the 44px target without changing line height. |
| 10px | 472 | status pill and order-thumbnail side padding | Matches the 24px pill height. |
| 11px, 22px | 224 each | "Quick add" pill (mouse only) | Design-file value for the pill. |
| 18px, 26px, 30px | 62, 608, 580 | horizontal padding of small, medium and large buttons | Design-file button geometry. |
| 20px, 28px | 3438, 486 | page gutter | `--gutter` (20px; 28px from 600px). A layout token, not a spacing step. |
| 44px | 53 | right padding of selects (room for the chevron) | `--tap`. |
| 52px | 174 | announcement bar side padding | Clears the 44px close button on both sides so the text stays centred. |

Nothing here is an accident; if the design file's scale is meant to be strict, the button and pill geometry is the part to raise with the designer.

## Radii

All radii now come from tokens: pill 100px, card 20px, tile 14px, input 12px, round 50%, plus the four small additions above (4, 6, 8, 24px).

## Not covered by this audit

- Colours inside images (there are no real photos yet). `tools/process-images.mjs` warns when a photo is mostly green, yellow or orange.
- Colours the browser draws itself: the native date picker, the select list, scrollbars, autofill highlight, text selection.
- Print styles.
