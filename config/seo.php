<?php

// Search metadata that is not per-product. Everything under `org` and `site` can
// be overridden by the shop owner in Admin > Store settings, which writes the
// settings table; AppServiceProvider::bootSeoSettings() folds those values in, so
// the storefront only ever reads config('seo.*').
//
// Deliberately absent: meta keywords. Google has ignored the tag since 2009 and
// Bing treats it as a weak spam signal.
return [
    // Appended to every generated title. Kept here rather than hard-coded in the
    // layout so a rename does not mean a template change.
    'title_suffix' => env('SEO_TITLE_SUFFIX', 'Kayaa'),

    // Shown when a page is shared and the record has no image of its own.
    // A path on the public disk, or an absolute URL.
    'default_og_image' => env('SEO_DEFAULT_OG_IMAGE', ''),

    'site_name' => 'Kayaa',
    'locale' => 'en_LK',
    'twitter_card' => 'summary_large_image',

    // Feeds the Organization and LocalBusiness JSON-LD on the home page.
    'org' => [
        'name' => env('SEO_ORG_NAME', 'Kayaa'),
        'logo_path' => env('SEO_ORG_LOGO', ''),
        'city' => env('SEO_ORG_CITY', 'Colombo'),
        'region' => env('SEO_ORG_REGION', 'Western Province'),
        'country' => 'LK',
        // Profiles become schema.org `sameAs`. These are how most of the store's
        // traffic already knows it, so they are worth stating explicitly.
        'instagram' => env('SEO_ORG_INSTAGRAM', ''),
        'facebook' => env('SEO_ORG_FACEBOOK', ''),
    ],

    // Titles and descriptions for pages with no model behind them. The key is
    // what a controller passes to SeoMeta::forPage(). The title suffix is
    // appended at render time, so it is not repeated here.
    'pages' => [
        'home' => [
            'title' => 'Baby Clothes in Sri Lanka, Sized by Age',
            'description' => 'Soft cotton baby clothing from Newborn to 3Y, with a clear size guide. Secure card payment and island-wide delivery across Sri Lanka.',
        ],
        'shop' => [
            'title' => 'All Baby Clothes',
            'description' => 'Every Kayaa piece in one place: bodysuits, sleepwear, sets and outerwear, from Newborn to 3Y. Island-wide delivery across Sri Lanka.',
        ],
        'categories' => [
            'title' => 'Shop by Category',
            'description' => 'Every Kayaa category for babies: bodysuits, sleepwear, sets, outerwear and more.',
        ],
        'sale' => [
            'title' => 'Sale',
            'description' => 'Reduced baby clothing at Kayaa. Soft cotton essentials from Newborn to 3Y while stock lasts.',
        ],
        'search' => [
            'title' => 'Search',
            'description' => 'Search Kayaa for baby clothing by name, size or colour.',
        ],
        'size-guide' => [
            'title' => 'Baby Clothes Size Guide (Height & Weight)',
            'description' => 'Find your baby’s size by height and weight, from Newborn to 3Y. Every Kayaa size with the measurements behind it.',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Questions about an order, a size or a fabric? Message Kayaa on WhatsApp, call, or send us a note.',
        ],
        'about' => [
            'title' => 'Our Story',
            'description' => 'Why Kayaa makes baby clothing sized by age, in soft cotton, for Sri Lankan families.',
        ],
        'delivery' => [
            'title' => 'Delivery',
            'description' => 'Island-wide delivery across Sri Lanka, with working-day estimates by district and free delivery over a set order value.',
        ],
        'returns' => [
            'title' => 'Returns & Exchanges',
            'description' => 'How to exchange a size or return a Kayaa order, and how long you have to do it.',
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'description' => 'What Kayaa collects when you shop, why, and how it is kept.',
        ],
        'terms' => [
            'title' => 'Terms & Conditions',
            'description' => 'The terms that apply when you order from Kayaa.',
        ],
        'wishlist' => [
            'title' => 'Saved Items',
            'description' => 'The Kayaa pieces you have saved.',
        ],
    ],
];
