# Kayaa content pack (draft 2, aligned with the staging site)

Draft 2 replaces draft 1. It now matches what the backend staging site (kayaa.harithroowijayawardhana.com) already says about sizes, categories, delivery and returns. Where the staging site states a fact, this pack uses it and marks it "confirm", because the client has not signed it off.

## Markers
- `[[TODO: ...]]` a fact only Kayaa knows.
- `[[PROPOSED: ...]]` a sensible default for the client to approve.
- `{{cfg:key}}` a value read from the site config (tools/site-config.json). Keys used here: shipping_fee, free_shipping_over, return_window_days, refund_days, faulty_report_days, delivery_colombo_days, delivery_island_days, dispatch_cutoff, support_hours. Never type these numbers by hand.

## How it is used
1. The client reads `01-open-decisions.md` and confirms or corrects each line.
2. Pages pull their text from these files (Prompt 12). Edit the file, not the HTML.
3. `tools/list-todos.mjs` lists every open marker. Nothing ships while markers remain.

## Positioning (important)
Kayaa sizes by age band (Newborn, 0-3m ... 3Y). Height and weight are the better guide, and the size guide says so. Copy must not claim "sized by weight and height".

## Legal note
`privacy-policy.md` and `terms.md` are careful drafts aligned with Sri Lanka's Personal Data Protection Act, No. 9 of 2022. They are not legal advice. Have a Sri Lankan lawyer review both before launch.

## Files
`01-open-decisions.md`, `privacy-policy.md`, `terms.md`, `returns.md`, `delivery.md`, `about.md`, `size-guide.md`, `faqs.md`, `category-and-age-intros.md`, `seo-guide.md`
