<?php

namespace App\Services;

use App\Models\ProductVariant;
use RuntimeException;

class OutOfStockException extends RuntimeException
{
    public function __construct(public readonly ProductVariant $variant, int $requested)
    {
        $name = $variant->product->name.' ('.$variant->label().')';
        $msg = $variant->stock > 0
            ? "Only {$variant->stock} of {$name} left — we've adjusted your cart."
            : "{$name} has just sold out and was removed from your cart.";
        parent::__construct($msg);
    }
}
