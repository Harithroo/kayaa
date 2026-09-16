<?php

// Store-level settings. Money values are in rupees here and converted to cents where used.
return [
    'currency' => 'LKR',
    'shipping_fee' => (int) env('STORE_SHIPPING_FEE', 450) * 100,
    'free_shipping_over' => (int) env('STORE_FREE_SHIPPING_OVER', 7500) * 100,
    'whatsapp' => env('STORE_WHATSAPP', ''),
    'email' => env('MAIL_FROM_ADDRESS', 'hello@kayaa.lk'),
    'districts' => [
        'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota',
        'Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee',
        'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla', 'Monaragala', 'Ratnapura', 'Kegalle',
    ],
];
