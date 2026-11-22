<?php

// Store-level settings. Money values are in rupees here and converted to cents where used.
return [
    'currency' => 'LKR',
    'shipping_fee' => (int) env('STORE_SHIPPING_FEE', 450) * 100,
    'free_shipping_over' => (int) env('STORE_FREE_SHIPPING_OVER', 7500) * 100,
    // Defaults only. Admin > Settings > Contact details overrides these at
    // runtime (see App\Providers\AppServiceProvider::bootContactSettings).
    'whatsapp' => env('STORE_WHATSAPP', ''),
    'phone' => env('STORE_PHONE', ''),
    'email' => env('MAIL_FROM_ADDRESS', 'hello@kayaa.lk'),
    // "Only N left" on the product page. Admin > Settings > Store overrides this.
    'low_stock_threshold' => (int) env('STORE_LOW_STOCK_THRESHOLD', 5),
    // Site availability. Admin > Storefront > Site availability overrides all of
    // this at runtime (see App\Providers\AppServiceProvider::bootSiteMode), so the
    // owner can put the shop behind a coming-soon or maintenance page themselves.
    // One of: live, coming_soon, maintenance.
    'site_mode' => env('STORE_SITE_MODE', 'live'),
    // Signed-in admins keep browsing the real storefront while a notice is up.
    'site_mode_admin_bypass' => (bool) env('STORE_SITE_MODE_ADMIN_BYPASS', true),
    'coming_soon' => [
        'heading' => 'Something lovely is on the way',
        'message' => 'We are putting the finishing touches to the shop. Leave us a message and we will tell you the moment the doors open.',
        'date' => null,
    ],
    'maintenance' => [
        'heading' => 'We are back shortly',
        'message' => 'The shop is down for a short update. Please try again in a few minutes: your cart and your orders are safe.',
        'back_at' => null,
    ],
    // Staging: keep the site out of search engines. Set to false (or remove) in production.
    // Shared secret for POST /deploy/migrate. Empty disables the route.
    'deploy_token' => env('DEPLOY_TOKEN', ''),
    'noindex' => (bool) env('APP_NOINDEX', false),
    // "Last updated" on the written pages (delivery, returns, privacy, terms,
    // size guide, our story). One date, set when the copy is next revised.
    'content_updated' => env('STORE_CONTENT_UPDATED', '2026-10-12'),
    'districts' => [
        'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota',
        'Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee',
        'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle',
    ],
    // Working days to delivery per district, as [min, max]. The storefront writes
    // its own copy from these two numbers ("2-4 working days"); nothing here is
    // pre-formatted text. 'default' covers any district not listed.
    'delivery_days' => [
        'default' => [3, 5],
        'Colombo' => [1, 2],
        'Gampaha' => [1, 2],
        'Kalutara' => [1, 3],
        'Kandy' => [2, 3],
        'Matale' => [2, 4],
        'Nuwara Eliya' => [2, 4],
        'Galle' => [2, 3],
        'Matara' => [2, 4],
        'Hambantota' => [3, 4],
        'Kurunegala' => [2, 3],
        'Puttalam' => [2, 4],
        'Ratnapura' => [2, 4],
        'Kegalle' => [2, 3],
        'Badulla' => [3, 4],
        'Monaragala' => [3, 5],
        'Anuradhapura' => [3, 4],
        'Polonnaruwa' => [3, 4],
        'Trincomalee' => [3, 5],
        'Batticaloa' => [3, 5],
        'Ampara' => [3, 5],
        'Jaffna' => [3, 5],
        'Kilinochchi' => [4, 6],
        'Mannar' => [4, 6],
        'Vavuniya' => [4, 6],
        'Mullaitivu' => [4, 6],
    ],

    // The home page (resources/views/store/home.blade.php, from the prototype's
    // html/index.html and design/home-v2-concept.html). Copy and drawing settings only:
    // the products, categories, sizes and reviews on that page are the real records.
    'home' => [
        // The scrolling band under the category rail, in order. Words alternate solid
        // and outlined. The prototype keeps these in tools/site-config.json "marquee".
        'marquee' => [
            'Breathable cotton',
            'Sized by age',
            'Free size swaps',
            'Island-wide delivery',
            'Soft on sensitive skin',
            'Made for Sri Lankan weather',
        ],

        // How each category is drawn on the rail, by category slug (the prototype's
        // `rail` object per category in tools/catalogue.json). `type` is one of the
        // drawings in resources/js/store/home/garments.js: newborn, bodysuit,
        // sleepsuit, set, cardigan, napkins, accessories. `c` and `c2` are colours of
        // its palette: white, peri, lilac, mist, plum, rose. A category not listed is
        // drawn as a white bodysuit. A cut-out photo at
        // public/assets/img/rail/{slug}.webp replaces the drawing.
        'rail' => [
            'newborn' => ['type' => 'newborn', 'c' => 'white'],
            'bodysuits' => ['type' => 'bodysuit', 'c' => 'peri'],
            'sleepwear' => ['type' => 'sleepsuit', 'c' => 'lilac'],
            'sets' => ['type' => 'set', 'c' => 'mist', 'c2' => 'lilac'],
            'outerwear' => ['type' => 'cardigan', 'c' => 'plum'],
            'napkins' => ['type' => 'napkins', 'c' => 'white', 'c2' => 'mist'],
            'accessories' => ['type' => 'accessories', 'c' => 'rose', 'c2' => 'peri'],
        ],

        // "Feel it before you buy it": one swatch tab per fabric, in order (the
        // prototype's docs/content/fabrics.md). PROPOSED copy: confirm the feel, best
        // for and care lines against the real garments' care labels before launch.
        // `key` picks the drawn texture (muslin, jersey, rib, waffle); `colour` is the
        // cloth; `stiff` and `flutter` tune how it hangs and moves (1 is the middle).
        // `match` finds the real products listed under "Found in" (up to four):
        // "tag:<slug>" is a product collection, "name:<word>" a word in the product
        // name. With no match the card links to a search for the fabric instead.
        'fabrics' => [
            [
                'key' => 'muslin', 'name' => 'Muslin', 'colour' => '#FFFFFF', 'stiff' => 0.8, 'flutter' => 1.25,
                'line' => 'Open, airy cotton gauze that gets softer with every wash.',
                'feel' => 'Light and gently crinkled',
                'best' => 'Swaddles, sleep bags, burp cloths',
                'care' => 'Machine wash cool, line dry',
                'match' => ['tag:muslin', 'name:muslin'],
            ],
            [
                'key' => 'jersey', 'name' => 'Bamboo jersey', 'colour' => '#DCCDED', 'stiff' => 0.9, 'flutter' => 1,
                'line' => 'Smooth and cool against the skin, with a soft stretch.',
                'feel' => 'Silky, cool, stretchy',
                'best' => 'Sleepsuits for warm nights',
                'care' => 'Gentle wash, no tumble dry',
                'match' => ['tag:bamboo', 'name:bamboo'],
            ],
            [
                'key' => 'rib', 'name' => 'Ribbed cotton', 'colour' => '#C6D6EC', 'stiff' => 1, 'flutter' => 0.85,
                'line' => 'Stretchy ribs that move with every kick and keep their shape.',
                'feel' => 'Springy and snug',
                'best' => 'Everyday bodysuits',
                'care' => 'Machine wash warm',
                'match' => ['name:ribbed'],
            ],
            [
                'key' => 'waffle', 'name' => 'Waffle knit', 'colour' => '#C9B6DE', 'stiff' => 1.15, 'flutter' => 0.65,
                'line' => 'Little raised squares that hold a touch of warmth for rainy evenings.',
                'feel' => 'Textured and cosy',
                'best' => 'Cardigans and layers',
                'care' => 'Wash inside out, dry flat',
                'match' => ['name:waffle'],
            ],
        ],
    ],
];
