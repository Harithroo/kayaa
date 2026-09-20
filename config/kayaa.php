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
    // Staging: keep the site out of search engines. Set to false (or remove) in production.
    // Shared secret for POST /deploy/migrate. Empty disables the route.
    'deploy_token' => env('DEPLOY_TOKEN', ''),
    'noindex' => (bool) env('APP_NOINDEX', false),
    'districts' => [
        'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota',
        'Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee',
        'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle',
    ],
];
