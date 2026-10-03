<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'size_option_id', 'color_id', 'sku',
        'price', 'compare_at_price', 'stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sizeOption(): BelongsTo
    {
        return $this->belongsTo(SizeOption::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /** "Butter · 0–3m" */
    public function label(): string
    {
        return collect([$this->color?->name, $this->sizeOption?->label])->filter()->implode(' · ');
    }

    public function inStock(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    /**
     * The threshold behind "Only N left". Admin > Settings > Store overrides the
     * config default, so the storefront must read this rather than hardcode 5.
     */
    public static function lowStockThreshold(): int
    {
        return (int) Setting::get('low_stock_threshold', config('kayaa.low_stock_threshold', 5));
    }

    public function isLowStock(): bool
    {
        return $this->inStock() && $this->stock <= self::lowStockThreshold();
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }
}
