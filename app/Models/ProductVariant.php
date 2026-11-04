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

    protected static function booted(): void
    {
        // Stock history. A new variant starts with an "initial" entry; a stock change
        // saved through the model (the product form) is an "admin_edit". Orders move
        // stock with query-builder increments, which fire no events here, and log
        // their own movement, so no change is ever recorded twice.
        static::created(function (ProductVariant $variant) {
            if ($variant->stock !== 0) {
                StockMovement::record($variant, $variant->stock, $variant->stock, 'initial');
            }
        });

        static::updated(function (ProductVariant $variant) {
            if ($variant->wasChanged('stock')) {
                $before = (int) $variant->getOriginal('stock');
                StockMovement::record($variant, $variant->stock - $before, $variant->stock, 'admin_edit');
            }
        });
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

    public static function uniqueSku(string $base): string
    {
        $sku = $base;
        $i = 2;
        while (self::where('sku', $sku)->exists()) {
            $sku = $base.$i++;
        }

        return $sku;
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
