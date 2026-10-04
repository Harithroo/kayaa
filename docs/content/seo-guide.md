# SEO guide for Kayaa

Practical rules for a small baby clothing store in Sri Lanka. The aim is pages that answer real questions clearly, load fast on phones and are easy for search engines to understand. No keyword stuffing: write for parents first.

## 1. Who we are writing for
- Expecting mothers in their early 20s: researching sizes, what to buy before the baby arrives, safe and gentle choices.
- Mothers in their 40s and repeat buyers: want speed, clear sizing and reliable delivery.
Search language will mix English with local place names ("baby clothes Colombo", "newborn clothes Sri Lanka"). Pages should state clearly that Kayaa delivers across Sri Lanka.

## 2. Keyword targets (one primary per page)
| Page | Primary | Secondary ideas |
|---|---|---|
| Home | baby clothes Sri Lanka | baby clothing online, newborn clothes |
| Shop | baby clothes online Sri Lanka | buy baby clothes online |
| Bodysuits | baby bodysuits Sri Lanka | baby onesies |
| Sleepsuits | baby sleepsuits Sri Lanka | baby pyjamas |
| Sets | baby clothing sets | baby gift sets |
| Dresses & Rompers | baby dresses Sri Lanka | baby rompers |
| Hats & Mitts | baby hats and mittens | newborn mittens |
| Swaddles & Blankets | baby swaddle blankets | muslin swaddle |
| Newborn | newborn baby clothes Sri Lanka | newborn essentials |
| 0-3 / 3-6 / 6-12 / 1-2 | baby clothes 0-3 months (etc.) | by age |
| Size guide | baby clothing size chart by weight | baby size guide |
| Delivery | baby clothes delivery Sri Lanka | free delivery over Rs 7,500 |
| Returns | baby clothes returns and exchanges | |

Use these naturally in the title, H1, first paragraph and one or two subheadings. Do not repeat them more than needed.

## 3. Meta titles and descriptions
Titles: aim for 60 characters or fewer. Descriptions: 120 to 155 characters. Category and age tags are in `category-and-age-intros.md`; content pages carry theirs in their front matter. The remaining pages:

| Page | Title | Description |
|---|---|---|
| Home | Kayaa: Baby Clothes in Sri Lanka, Sized by Weight & Height | Soft everyday baby clothing from newborn to 2 years, sized by weight and height. Secure card payment and delivery across Sri Lanka. |
| Shop | Baby Clothes Online in Sri Lanka | Kayaa | Shop baby clothing from newborn to 2 years. Sized by weight and height, with secure card payment and delivery across Sri Lanka. |
| Product (template) | {Product name} | Baby {Category} | Kayaa | First 150 characters of the product description. Fallback: "{Product name} from Kayaa. Choose your colour and size, pay securely by card and get delivery across Sri Lanka." |
| Contact | Contact Kayaa | WhatsApp, Email & Messages | Questions about sizing, delivery or an order? Message Kayaa on WhatsApp, email us or use the contact form. |

Every title and description must be unique. Backend: allow an optional `meta_title` and `meta_description` on products and categories, with the templates above as the fallback.

## 4. Indexing rules (robots and canonical)
| Page | Robots | Canonical |
|---|---|---|
| Home, Shop, category pages, age pages | index,follow | itself |
| Product pages | index,follow | the product URL, without ?colour= |
| Size guide, Delivery, Returns, About, Contact, Privacy, Terms | index,follow | itself |
| Shop with sort or sale parameters, and any combination of filters | noindex,follow | the clean listing URL |
| Listing page 2 and beyond | index,follow | itself |
| Search results | noindex,follow | itself |
| Cart, checkout, thank-you, track, all account and auth pages | noindex,nofollow | itself |
| Error pages | noindex | none |

While the site is on staging every page is `noindex,nofollow` (the `APP_NOINDEX` setting). See the launch checklist.

## 5. Structured data (JSON-LD)
- **Organization** on the home page: name, URL, logo, social profile links, contact point (WhatsApp or email). Add once the logo and real details exist.
- **BreadcrumbList** on category, product and content pages, matching the visible breadcrumbs.
- **Product** on product pages: name, images, description, brand, `offers` (price in LKR, availability, URL) and, when there are approved reviews, `aggregateRating` and `review`. Only mark up reviews of that product.
- Do not mark up reviews of the store itself.
- FAQ markup is optional. Google stopped showing FAQ rich results in May 2026, so do not add it just to try to win a rich result; visible FAQs on the page are still useful to shoppers.

## 6. On-page rules for every content page
- One H1 that says what the page is. Subheadings (H2, H3) in a logical order.
- State what Kayaa is and that it delivers across Sri Lanka within the first 100 words where it fits naturally.
- Short paragraphs, descriptive link text (never "click here"), a "Last updated" date on policy pages.
- Link onwards: product pages link to the size guide, delivery and returns; the size guide links to shop by age and categories; returns and delivery link to contact and track.
- Each page ends with a clear next step (shop, contact or track).

## 7. Internal linking map
- Home: all six categories, five ages, size guide.
- Category and age pages: size guide, sibling categories, delivery.
- Product pages: parent category, size guide, returns, delivery, "You may also like".
- Size guide: five ages and six categories.
- Footer: Shop by age, Help (Track, Size guide, Delivery, Returns, Contact), Company (About, Privacy, Terms).

## 8. Images
- Real product photos should be named after the product (ribbed-cotton-bodysuit-lilac-1.webp).
- Alt text describes what is visible in one plain sentence. Mention the product, colour and angle for product photos. Decorative images get empty alt text.
- Always set width and height, lazy-load images below the fold, preload only the main image on the home page and product page.
- Serve WebP, mobile-sized variants (480, 800, 1200).

## 9. Technical checklist
- HTTPS only; one canonical host (with or without www) and 301 redirect the other.
- 301 redirects whenever a product or category slug changes. Unknown URLs return a real 404 status.
- XML sitemap listing only indexable pages, updated automatically; link it in robots.txt.
- Mobile first. Aim for fast loading: self-host fonts, compress images, avoid layout shifts.
- No duplicate content from colour variants or sorted listings (see section 4).
- Language: English (`lang="en"`). Add hreflang only if Sinhala or Tamil versions are built later.

## 10. Launch checklist (staging to live)
1. Set `APP_NOINDEX=false` on the production server.
2. Delete the unconditional `X-Robots-Tag: noindex` block in `public/.htaccess`. Apache cannot read `.env`, so this block must be removed by hand before launch.
3. Remove the noindex meta tag from the pages that should be indexed (the staging flag in the SEO config handles this at conversion).
4. Publish `robots.txt` (allow all, disallow /cart, /checkout, /account, /track, /search, point to the sitemap).
5. Submit the sitemap in Google Search Console and Bing Webmaster Tools. Verify the domain.
6. Check a few pages with the Rich Results Test (Product and Breadcrumb) and PageSpeed Insights on a phone.
7. Replace every `[[TODO]]` and `[[PROPOSED]]` marker with approved text.

## 11. Phase 2: guides that earn traffic
Helpful articles bring in parents before they are ready to buy and give us pages to link to products. Needs a simple Blade guides section (not in the current backend). Starting ideas, each 700 to 1,000 words:
1. Newborn clothing checklist: what to buy before your baby arrives
2. How to choose baby clothes sizes by weight and height
3. How many baby clothes do you really need?
4. How to wash and care for newborn clothes
5. What to pack for your baby in the hospital bag
6. Dressing your baby for Sri Lanka's heat and rainy season
Each guide links to the size guide, the relevant category and delivery. Write from experience, avoid medical claims, and have a clinician check anything health-related.
