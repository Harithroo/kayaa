# SEO guide for Kayaa (draft 2)

Practical rules for a small baby clothing store in Sri Lanka. Write for parents first; no keyword stuffing.

## 0. What the staging site shows today (technical findings)
- Every page uses the same meta description ("Soft cotton baby clothing sized by age, delivered island-wide."). Each page needs its own.
- Size filter URLs contain an encoded en dash (?size=0%E2%80%933m). Use clean ASCII slugs (?size=0-3m) in URLs and show the en dash only in the label.
- Pages are `noindex, nofollow` (correct for staging). The launch checklist below says how to switch it.
- URLs are clean: /baby/{category}, /products/{slug}. Keep them, with 301 redirects if a slug ever changes.

## 1. Who we are writing for
- Expecting mothers in their early 20s: researching sizes and what to buy before the baby arrives.
- Mothers in their 40s and repeat buyers: want speed, clear sizing and reliable delivery.
Search language mixes English with place names ("baby clothes Colombo", "newborn clothes Sri Lanka"). Pages should state that Kayaa delivers island-wide.

## 2. Keyword targets (one primary per page)
| Page | Primary | Secondary ideas |
|---|---|---|
| Home | baby clothes Sri Lanka | baby clothing online, cotton baby clothes |
| Shop | baby clothes online Sri Lanka | buy baby clothes online |
| Newborn | newborn baby clothes Sri Lanka | newborn essentials |
| Bodysuits | baby bodysuits Sri Lanka | baby onesies |
| Sleepwear | baby sleepwear Sri Lanka | baby sleep bag, baby sleepsuit |
| Sets | baby clothing sets | baby gift sets |
| Outerwear | baby cardigans Sri Lanka | baby jackets |
| Napkins | baby napkins Sri Lanka | (confirm product first) |
| Accessories | baby mittens and booties | baby bibs |
| Size pages | baby clothes 0-3 months (etc.) | by size |
| Size guide | baby clothing size chart by age and weight | baby size guide |
| Delivery | baby clothes delivery Sri Lanka | free delivery over Rs. 7,500 |
| Returns | baby clothes exchange Sri Lanka | |

## 3. Meta titles and descriptions
Titles 60 characters or fewer, descriptions 120 to 155. Category and size tags are in `category-and-age-intros.md`, content pages carry theirs in front matter. The rest:

| Page | Title | Description |
|---|---|---|
| Home | Kayaa: Baby Clothes in Sri Lanka, Sized by Age | Soft cotton baby clothing from Newborn to 3Y, with a clear size guide. Secure card payment and island-wide delivery across Sri Lanka. |
| Shop | Baby Clothes Online in Sri Lanka | Kayaa | Shop soft baby clothing from Newborn to 3Y. Clear size guide, secure card payment and island-wide delivery across Sri Lanka. |
| All categories | Shop Baby Clothes by Category | Kayaa | Browse Kayaa baby clothing by type: newborn, bodysuits, sleepwear, sets, outerwear, napkins and accessories. |
| Product (template) | {Product name} | Baby {Category} | Kayaa | First 150 characters of the product description. Fallback: "{Product name} from Kayaa. Choose your colour and size, pay securely by card and get delivery across Sri Lanka." |
| Contact | Contact Kayaa | WhatsApp, Email & Messages | Questions about sizing, delivery or an order? Message Kayaa on WhatsApp, email us or use the contact form. |

Every title and description must be unique. Backend: optional `meta_title` and `meta_description` on products and categories, with these templates as the fallback.

## 4. Indexing rules (robots and canonical)
| Page | Robots | Canonical |
|---|---|---|
| Home, Shop, /categories, category pages (/baby/{slug}) | index,follow | itself |
| /shop?size={slug} | index,follow | itself |
| /baby/{slug}?size={slug} (category plus size) | noindex,follow | the category page |
| Product pages | index,follow | the product URL, without ?colour= |
| Size guide, Delivery, Returns, About, Contact, Privacy, Terms | index,follow | itself |
| Any sort or sale parameter, and combinations of filters | noindex,follow | the clean listing |
| Listing page 2 and beyond | index,follow | itself |
| Search results | noindex,follow | itself |
| Wishlist, cart, checkout, order confirmation, track, all account and auth pages | noindex,nofollow | itself |
| Error pages | noindex | none |

While on staging, every page is `noindex,nofollow`.

## 5. Structured data (JSON-LD)
- **Organization** on the home page: name, URL, logo, social profiles, contact point.
- **BreadcrumbList** on category, product and content pages, matching the visible breadcrumbs (the site uses Home > Baby > Category).
- **Product** on product pages: name, images, description, brand, `offers` (price in LKR, availability, URL), and when there are approved reviews, `aggregateRating` and `review`. Only mark up reviews of that product.
- No markup for reviews of the store itself.
- FAQ markup is optional. Google stopped showing FAQ rich results in May 2026, so do not add it just to win one; visible FAQs on the page still help shoppers.

## 6. On-page rules for every content page
- One H1, then H2 and H3 in order.
- Say what Kayaa is and that it delivers island-wide within the first 100 words where it fits.
- Short paragraphs, descriptive link text, a "Last updated" date on policy pages.
- Link onwards: products link to the size guide, delivery and returns; the size guide links to sizes and categories; returns and delivery link to contact and track.
- Every page ends with a next step (shop, contact or track).

## 7. Internal linking map
- Home: all seven categories, the size chips, the size guide.
- Category and size pages: size guide, sibling categories, delivery.
- Product pages: parent category, size guide, returns, delivery, "Goes well with".
- Size guide: all sizes and categories.
- Footer: Shop, Help (Size guide, Delivery, Returns, Track, Wishlist, Sign in), Company (About, Privacy, Terms).

## 8. Images
- Real product photos named after the product and colour (ribbed-cotton-bodysuit-butter-1.webp).
- Alt text describes what is visible in one sentence, with product, colour and angle for product photos. Decorative images get empty alt text.
- Always set width and height, lazy-load below the fold, preload only the hero and the main product image.
- Serve WebP at 480, 800 and 1200 widths.

## 9. Technical checklist
- HTTPS only; one canonical host (www or not), with a 301 from the other.
- 301 redirects whenever a slug changes. Unknown URLs return a real 404.
- XML sitemap of indexable pages only, linked from robots.txt.
- Mobile first, fast: self-host fonts, compress images, avoid layout shifts.
- No duplicate content from colour variants or sorted listings (section 4).
- Language: English (`lang="en"`). Add hreflang only if Sinhala or Tamil versions are built.

## 10. Launch checklist (staging to live)
1. Set `APP_NOINDEX=false` on the production server.
2. Delete the unconditional `X-Robots-Tag: noindex` block in `public/.htaccess` (Apache cannot read `.env`, so it must be removed by hand).
3. Remove the noindex meta tag from the pages that should be indexed.
4. Replace the sitewide default meta description with per-page ones.
5. Publish `robots.txt` (allow all; disallow /cart, /checkout, /account, /track, /search, /wishlist, /orders; point to the sitemap).
6. Submit the sitemap in Google Search Console and Bing Webmaster Tools and verify the domain.
7. Check a few pages with the Rich Results Test (Product, Breadcrumb) and PageSpeed Insights on a phone.
8. Remove every "PayHere" mention that is no longer true.
9. Replace every `[[TODO]]` and `[[PROPOSED]]` marker with approved text.

## 11. Phase 2: guides that earn traffic
Helpful articles bring in parents before they are ready to buy. Needs a simple Blade guides section (not in the current backend). Ideas, 700 to 1,000 words each:
1. Newborn clothing checklist: what to buy before your baby arrives
2. How to choose baby clothes sizes: age, height and weight
3. How many baby clothes do you really need?
4. How to wash and care for newborn clothes
5. What to pack for your baby in the hospital bag
6. Dressing your baby for Sri Lanka's heat and rainy season
Each guide links to the size guide, the relevant category and delivery. Write from experience, avoid medical claims, and have a clinician check anything health-related.
