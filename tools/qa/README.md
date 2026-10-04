# tools/qa/ - browser audits

Dev-only scripts that open the site in real browsers and check accessibility, keyboard use, zoom and text size, performance, browser differences, HTML validity and colours. They are **not** part of the site and add nothing to the repository's dependencies: the test tools are installed in a temporary folder **outside the repo**.

## One-time install (Windows PowerShell shown; use `$TMPDIR` or `/tmp` on macOS and Linux)

```powershell
$qa = Join-Path $env:TEMP 'kayaa-qa'
New-Item -ItemType Directory -Force $qa | Out-Null
Set-Location $qa
npm init -y
npm i playwright axe-core lighthouse html-validate chrome-launcher
npx playwright install chromium firefox webkit     # about 600 MB, stored in the user cache (not in the repo)
```

Versions used for the last report are in `docs/qa/qa-report.md`. Lighthouse also needs Google Chrome (or Chromium) installed; chrome-launcher finds it.

## Run

From the repository root. If the tools are not in `<temp>/kayaa-qa`, set `KAYAA_QA_MODULES` to the folder that holds `node_modules`. Results (JSON, screenshots, Lighthouse reports) go to `<tools folder>/out/` (override with `KAYAA_QA_OUT`).

```powershell
node tools/qa/axe.mjs             # axe-core (wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa, best-practice): every page + key states, 375 and 1280
node tools/qa/static-checks.mjs   # unique ids, aria references, anchors, headings, names, landmarks, lang, target=_blank, tabindex
node tools/qa/keyboard.mjs        # tab order, focus indicators, skip link, no traps; focus trap, Esc and focus return for every layer
node tools/qa/modes.mjs           # 320px, 200% and 400% zoom, 200% text, text spacing, reduced motion, forced colours, touch
node tools/qa/lighthouse.mjs      # Lighthouse mobile + desktop on 9 pages (--only home for one page, --form mobile for one form factor)
node tools/qa/engines.mjs         # Chromium, Firefox, WebKit smoke test at 390x844 touch and 1280x800, with screenshots
node tools/qa/validate.mjs        # html-validate on every page
node tools/qa/hygiene.mjs         # console errors, failed requests, unused CSS rules, file names and sizes
node tools/qa/design-audit.mjs    # computed colours against tokens, green/yellow/peach hues, font sizes, spacing and radii against the scales
node tools/qa/footer-contrast.mjs # footer text against the real gradient pixels behind it at 320 to 1920 (4.5:1)
node tools/check-footer-seam.mjs  # footer top edge against the section above (also in tools/README.md)
node tools/qa/serve.mjs           # just the server: http://localhost:3480/kayaa/ (html/ under /kayaa/, 404.html fallback, gzip, 10 minute cache)
```

Every script starts its own Pages-like server on port 3480 (`KAYAA_QA_PORT` changes it, which lets two audits run side by side). Over http the pages behave as they will on GitHub Pages; open `file://` by hand for the "works from a folder" check.

Run times on a normal laptop: axe about 4 minutes, keyboard 3, modes 6, engines 4, Lighthouse 12, the rest under 3 each. Lighthouse numbers depend on the machine: run it with nothing else busy, and compare scores from the same machine.

## What each script asserts

| Script | Fails when |
|---|---|
| axe | any axe violation (serious and critical must be zero; moderate and minor are reviewed) |
| static-checks | duplicate id, dangling aria-controls / labelledby / describedby / label for / `#anchor` / sprite symbol, not exactly one visible h1, a skipped heading level, a control, link or button without a name, a missing landmark, no `lang`, `target=_blank` without `rel`, a positive tabindex |
| keyboard | the skip link is not the first stop or does not work, a stop with no visible focus indicator, a tab cycle that never closes, a layer where focus does not move in, stays in, return, or Esc does not close |
| modes | horizontal scroll, text clipped by an overflow-hidden box, overlapping text, animation or transition longer than 0.01 ms under reduced motion, a control with no border or outline in forced colours, something shown only on hover |
| engines | any flow step fails in any of the three engines; it also lists geometry that differs between engines |
| validate | any html-validate error (the recommended rules, with three documented exceptions in the script) |
| hygiene | a console error or warning, a failed request, a CSS rule that matches nothing on any page and is named in no script |

The heuristics in `modes.mjs` and `keyboard.mjs` are tuned to avoid false alarms (for example they ignore visually hidden text and line-clamped text), so a failure there deserves a look at a screenshot.

## When to run what

See "Release gate" in `CLAUDE.md`: the zero-dependency checks before every push, and these browser audits before a client review.
