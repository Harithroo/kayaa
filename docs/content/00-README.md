# Kayaa content pack (draft 1)

Written for the Kayaa storefront. Everything here is drafted copy plus SEO guidance. Nothing is invented about the business: where a fact is unknown the text shows a marker.

## Markers
- `[[TODO: ...]]` a fact only Kayaa knows (legal name, address, phone, hours, courier, etc.).
- `[[PROPOSED: ...]]` a sensible default for the client to approve or change (for example the return window).
- `{{cfg:shipping_fee}}` and `{{cfg:free_shipping_over}}` read the delivery fee and free-delivery threshold from the site config (currently Rs 450 and Rs 7,500). Never type these numbers by hand.

## How it is used
1. Read `01-open-decisions.md` and get the client to approve or edit each default.
2. The pages pull their text from these files (Prompt 12 does this). Edit the file, not the HTML.
3. `tools/list-todos.mjs` lists every open marker. Nothing ships to production while markers remain.

## Legal note
`privacy-policy.md` and `terms.md` are careful drafts aligned with Sri Lanka's Personal Data Protection Act, No. 9 of 2022 and ordinary consumer practice. They are not legal advice. Have a Sri Lankan lawyer review both before launch.

## Files
- `01-open-decisions.md` decisions for the client
- `privacy-policy.md`, `terms.md`, `returns.md`, `delivery.md`, `about.md`, `size-guide.md`
- `faqs.md` product, contact and size-guide FAQs
- `category-and-age-intros.md` intro text and meta tags for category and age pages
- `seo-guide.md` keywords, meta tags for every page, canonical and robots rules, structured data, internal links, launch checklist
