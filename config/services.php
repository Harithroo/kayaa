<?php

return [
    'payhere' => [
        'merchant_id' => env('PAYHERE_MERCHANT_ID'),
        'secret' => env('PAYHERE_MERCHANT_SECRET'),
        'sandbox' => (bool) env('PAYHERE_SANDBOX', true),
    ],
];
