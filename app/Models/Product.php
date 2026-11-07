<?php

namespace App\Models;

use App\Support\Concerns\HasSeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasSeoMeta;

    protected $fillable = [
        'category_id', 'size_scale_id', 'name', 'slug', 'collection_label',
        'description', 'fabric_care', 'status', 'is_featured', 'is_new',
        'meta_title', 'meta_description', 'og_image_id', 'noindex',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_new' => 'boolean', 'noindex' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Past orders point at these variants. Returning false stops the delete,
        // so no screen (single, bulk, or a future one) can erase a sold product.
        // Archive it instead.
        static::deleting(fn (Product $product) => ! $product->hasOrders());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** True when any order, in any state, contains one of this product's variants. */
    public function hasOrders(): bool
    {
        return OrderItem::query()
            ->whereIn('product_variant_id', $this->variants()->select('id'))
            ->exists();
    }

    /**
     * A draft copy with fresh slug and SKUs. Photos are copied to new files:
     * the upload field deletes the file when a photo is removed, so two products
     * must never share one.
     */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $this->loadMissing(['variants', 'images']);

            // Copy only real columns. A record from the admin list also carries
            // computed attributes (variants_sum_stock, ...) that replicate() would
            // try to insert.
            $copy = new self($this->only($this->getFillable()));
            $copy->name = Str::limit($this->name, 108, '').' (copy)';
            $copy->slug = self::uniqueSlug($this->slug.'-copy');
            $copy->status = 'draft';
            $copy->is_featured = false;
            $copy->save();

            foreach ($this->variants as $variant) {
                $new = $variant->replicate();
                $new->product_id = $copy->id;
                $new->sku = ProductVariant::uniqueSku($variant->sku.'-COPY');
                $new->save();
            }

            $disk = Storage::disk('public');
            foreach ($this->images as $image) {
                $path = $image->path;
                if (! $path || ! $disk->exists($path)) {
                    continue;
                }
                $newPath = trim(dirname($path), '.\\/').'/'.Str::uuid().'.'.pathinfo($path, PATHINFO_EXTENSION);
                $disk->copy($path, ltrim($newPath, '/'));
                $new = $image->replicate();
                $new->product_id = $copy->id;
                $new->path = ltrim($newPath, '/');
                $new->save();
            }

            return $copy;
        });
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
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
        // Fully qualified: `Collection` in this file is Illuminate's support
        // collection (imported above), not the Collection model.
        return $this->belongsToMany(\App\Models\Collection::class)->withPivot('position');
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
