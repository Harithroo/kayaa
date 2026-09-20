<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'size_scale_id', 'name', 'slug', 'collection_label',
        'description', 'fabric_care', 'status', 'is_featured', 'is_new',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_new' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------- relations ----------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sizeScale(): BelongsTo
    {
        return $this->belongsTo(SizeScale::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('position');
    }

    // ---------- scopes ----------

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** Eager-load everything a product card needs. */
    public function scopeForCards(Builder $q): Builder
    {
        return $q->with(['images', 'variants.sizeOption', 'variants.color']);
    }

    // ---------- derived data (require variants loaded) ----------

    public function primaryImage(): ?ProductImage
    {
        return $this->images->first();
    }

    public function activeVariants(): Collection
    {
        return $this->variants->where('is_active', true);
    }

    public function minPrice(): int
    {
        return (int) ($this->activeVariants()->min('price') ?? 0);
    }

    /** "Was" price to show on cards, only when every variant shares the same compare-at price. */
    public function compareAtPrice(): ?int
    {
        $prices = $this->activeVariants()->pluck('compare_at_price')->filter()->unique();

        return $prices->count() === 1 ? (int) $prices->first() : null;
    }

    public function colors(): Collection
    {
        return $this->activeVariants()->pluck('color')->filter()->unique('id')->values();
    }

    /** Size options this product is offered in, in scale order. */
    public function sizeOptions(): Collection
    {
        return $this->activeVariants()->pluck('sizeOption')->filter()->unique('id')->sortBy('position')->values();
    }

    public function sizeRangeLabel(): ?string
    {
        $sizes = $this->sizeOptions();
        if ($sizes->isEmpty()) {
            return null;
        }
        $first = self::shortSize($sizes->first()->label);
        $last = self::shortSize($sizes->last()->label);

        return $first === $last ? $first : "{$first}–{$last}";
    }

    /** Average of approved ratings, rounded to one decimal. Null when there are none. */
    public function ratingAverage(): ?float
    {
        $avg = $this->reviews()->approved()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function ratingCount(): int
    {
        return $this->reviews()->approved()->count();
    }

    public function totalStock(): int
    {
        return (int) $this->activeVariants()->sum('stock');
    }

    public function isLowStock(): bool
    {
        $stock = $this->totalStock();

        return $stock > 0 && $stock <= 5;
    }

    /** "Newborn" → "NB", "12–18m" → "18m" so ranges read as "NB–18m". */
    private static function shortSize(string $label): string
    {
        if (strcasecmp($label, 'Newborn') === 0) {
            return 'NB';
        }
        if (preg_match('/^\d+–(\d+m)$/u', $label, $m)) {
            return $m[1];
        }

        return $label;
    }
}
