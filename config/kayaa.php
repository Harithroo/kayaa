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
    // Staging: keep the site out of search engines. Set to false (or remove) in production.
    // Shared secret for POST /deploy/migrate. Empty disables the route.
    'deploy_token' => env('DEPLOY_TOKEN', ''),
    'noindex' => (bool) env('APP_NOINDEX', false),
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
];
