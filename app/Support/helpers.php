<?php

use Illuminate\Support\Number;

if (! function_exists('money')) {
    /**
     * Format an integer amount in cents as rupees: 245000 → "Rs. 2,450".
     */
    function money(int $cents): string
    {
        $rupees = $cents / 100;
        $decimals = ($cents % 100) === 0 ? 0 : 2;

        return 'Rs. '.number_format($rupees, $decimals);
    }
}
