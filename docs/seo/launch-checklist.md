# SEO launch checklist (staging to live)

Copied from docs/content/seo-guide.md section 10. Also run `node tools/list-todos.mjs --fail-on-open` (it must pass) and `node tools/check-seo.mjs`, and set `"staging": false` in tools/seo.json for the prototype.

1. Set `APP_NOINDEX=false` on the production server.
2. Delete the unconditional `X-Robots-Tag: noindex` block in `public/.htaccess` (Apache cannot read `.env`, so it must be removed by hand).
3. Remove the noindex meta tag from the pages that should be indexed.
4. Replace the sitewide default meta description with per-page ones.
5. Publish `robots.txt` (allow all; disallow /cart, /checkout, /account, /track, /search, /wishlist, /orders; point to the sitemap).
6. Submit the sitemap in Google Search Console and Bing Webmaster Tools and verify the domain.
7. Check a few pages with the Rich Results Test (Product, Breadcrumb) and PageSpeed Insights on a phone.
8. Remove every payment mention that is no longer true (any payment option other than card, and PayHere).
9. Replace every `[[TODO]]` and `[[PROPOSED]]` marker with approved text.
