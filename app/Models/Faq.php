<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    public const PLACEMENTS = ['product' => 'Product pages', 'contact' => 'Contact page'];

    protected $fillable = [
        'placement', 'product_id', 'category_id', 'question', 'answer', 'is_active', 'position',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Product FAQs for one product: pinned to the product, pinned to its category,
     * or global (neither set — shown on every product page).
     */
    public function scopeForProduct(Builder $q, Product $product): Builder
    {
        return $q->where('placement', 'product')->where(function (Builder $q) use ($product) {
            $q->where('product_id', $product->id)
                ->orWhere('category_id', $product->category_id)
                ->orWhere(fn (Builder $q) => $q->whereNull('product_id')->whereNull('category_id'));
        });
    }

    public function scopeForContact(Builder $q): Builder
    {
        return $q->where('placement', 'contact');
    }

    /** Where it shows, in words — for the admin table. */
    public function targetLabel(): string
    {
        if ($this->placement === 'contact') {
            return 'Contact page';
        }

        return $this->product?->name ?? ($this->category?->name ? $this->category->name.' category' : 'All products');
    }
}
