<?php

return [
    // Onepay (https://docs.onepay.lk). Credentials come from the merchant dashboard;
    // each business account has separate sandbox and live sets. The hash salt is
    // server-side only and is never sent in a request body.
    'onepay' => [
        'app_id' => env('ONEPAY_APP_ID'),
        'hash_salt' => env('ONEPAY_HASH_SALT'),
        // Some accounts are issued an app token to send as an Authorization header.
        // Left empty it is simply omitted.
        'app_token' => env('ONEPAY_APP_TOKEN'),
        'base_url' => rtrim((string) env('ONEPAY_BASE_URL', 'https://api.onepay.lk'), '/'),
    ],
];
