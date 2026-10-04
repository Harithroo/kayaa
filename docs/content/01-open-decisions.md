# Decisions for the client

The "Staging site says" column is what the current test site already shows. Please confirm or correct each line. "Required" items cannot be guessed.

| # | Decision | Staging site says | Status |
|---|---|---|---|
| 1 | Registered business name and address | "Colombo, Sri Lanka" only | Required |
| 2 | WhatsApp number and support email | 077 000 0000 (placeholder); email hidden | Required |
| 3 | Opening hours | Mon to Sat, 9am to 6pm | Confirm |
| 4 | Exchange window | 14 days from delivery, item unworn, unwashed, tags on | Confirm |
| 5 | Who pays return courier on size swaps | Kayaa pays; we arrange the pick-up via WhatsApp | Confirm |
| 6 | Refunds | Item price refunded within 7 days of receiving it back; delivery fee not refunded unless the item was faulty or wrong | Confirm (refund goes to the card used) |
| 7 | Faulty or wrong items | Photo within 7 days; replace or refund including delivery | Confirm |
| 8 | Items that cannot be returned | Worn or washed items, and sale items marked final | Confirm (are sale items final?) |
| 9 | Dispatch | Same day if ordered before 2pm on a working day | Confirm |
| 10 | Delivery times | Colombo and suburbs 1 to 2 working days; rest of the island 2 to 4 | Replace with per-district data from the backend |
| 11 | "We'll WhatsApp you when the parcel leaves us" | Stated on the delivery page | Confirm we do this |
| 12 | "In stock, ships from Colombo" | Stated on product pages | Confirm |
| 13 | Fabric claims: "OEKO-TEX certified combed cotton", "breathable cotton and bamboo", "no harsh dyes", "tested for sensitive skin" | Stated on Home and product pages | Required: certificate evidence before launch. Certification claims must be true and provable |
| 14 | Care instructions | Machine wash cold, gentle cycle; tumble dry low or line dry in shade; do not bleach (bodysuit sample) | Per product |
| 15 | Size chart | 9 sizes, Newborn to 3Y, height and weight (see size-guide.md) | Final, supplied by the client |
| 16 | Size positioning | "Sized by age"; height and weight are the better guide; between sizes go up | Confirm |
| 17 | Payment gateway name | Footer still says "Payments secured by PayHere" | Required: the client chose Onepay, so update every mention |
| 18 | Cash on delivery | Removed from every static page | Kept only as an admin on/off switch for emergencies; shown at checkout and on that order's pages, never in static text. Decided |
| 19 | Prices include tax | not stated | Approve (proposed: yes) |
| 20 | Marketing messages | none | Approve (proposed: none; consent first if that changes) |
| 21 | Analytics or advertising tools | not stated | Decide before launch; none in v1 |
| 22 | Data retention: orders / contact messages / security logs | not stated | Approve (proposed: 6 years, confirm with accountant / 12 months / 90 days) |
| 23 | Hosting country and email provider | not stated | Required |
| 24 | Courier names (or just "delivery partners") | "courier" | Approve |
| 25 | Governing law and courts | not stated | Approve (proposed: Sri Lanka) |
| 26 | What "Napkins" are (cloth nappies, muslin squares, other?) | a category with no description | Required |
| 27 | Brand story: who started Kayaa and why | none | Optional |
| 28 | Logo files and a 1200x630 social share image | none; the Organization logo and og:image are marked TODO | Required |
| 29 | Live social profile URLs | none; Organization sameAs is empty | Required |
| 30 | The real production domain for canonicals, sitemap and robots | example.com placeholder | Required |
| 31 | Who edits Delivery, Returns, About, Privacy, Terms and Size guide | not stated | Decided: developer-edited views for v1 |
