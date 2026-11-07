<?php

namespace App\Support\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\Seo\MetaImage;
use App\Support\Seo\MetaText;

/**
 * Per-record search metadata, with a generated value behind every field.
 *
 * The contract the storefront relies on: metaTitle() and metaDescription() never
 * return an empty string. A product saved without the admin's SEO section ever
 * being opened still ships a real title and a real description, so no page can
 * reach Google blank.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasSeoMeta
{
    /** Google truncates around 60; 70 is the hard stop, suffix included. */
    public const TITLE_LIMIT = 70;

    public const DESCRIPTION_LIMIT = 155;

    public function metaTitle(): string
    {
        $stored = MetaText::flatten($this->meta_title ?? null);

        if ($stored !== null) {
            return MetaText::clamp($stored, self::TITLE_LIMIT);
        }

        return $this->generatedMetaTitle();
    }

    public function metaDescription(): string
    {
        $stored = MetaText::summarise($this->meta_description ?? null, 170);

        return $stored ?? $this->generatedMetaDescription();
    }

    /**
     * The one URL this record should be indexed under. Never carries a query
     * string: filters and sort orders are not separate pages.
     */
    public function canonicalUrl(): string
    {
        if ($this instanceof Product) {
            return route('products.show', $this);
        }

        if ($this instanceof Category) {
            return $this->url();
        }

        return url()->current();
    }

    /**
     * Absolute URL of the image used when the page is shared. Absolute matters:
     * WhatsApp and Instagram drop a relative og:image, and a product link with
     * no preview looks broken, which is the main way this store gets shared.
     */
    public function ogImageUrl(): ?string
    {
        $image = $this->ogImage();

        return $image !== null
            ? MetaImage::absolute($image->url())
            : MetaImage::storeDefault();
    }

    /**
     * The chosen share image, or the first photo. Categories have no images of
     * their own, so they fall through to the store default.
     */
    protected function ogImage(): ?ProductImage
    {
        if (! $this instanceof Product) {
            return null;
        }

        if ($this->og_image_id) {
            $this->loadMissing('images');

            // Read off the loaded collection rather than querying: the product
            // page already has the images, and a stale og_image_id pointing at a
            // deleted row then simply falls through to the first photo.
            $chosen = $this->images->firstWhere('id', $this->og_image_id);

            if ($chosen !== null) {
                return $chosen;
            }
        }

        $this->loadMissing('images');

        return $this->images->first();
    }

    // ---------- generated fallbacks ----------

    private function generatedMetaTitle(): string
    {
        $suffix = trim((string) config('seo.title_suffix', 'Kayaa'));
        $tail = $suffix === '' ? '' : ' | '.$suffix;

        $lead = $this instanceof Product
            ? $this->productTitleLead()
            : $this->name.' — Baby Clothes Sri Lanka';

        // Clamp the lead so the suffix always survives: a title ending "| Kayaa"
        // is what makes the brand show in the result, so it is never the part cut.
        $lead = MetaText::clamp($lead, self::TITLE_LIMIT - mb_strlen($tail));

        return $lead.$tail;
    }

    /** "Organic Cotton Bodysuit — NB–18m", or just the name when sizeless. */
    private function productTitleLead(): string
    {
        $this->loadMissing(['variants.sizeOption']);

        $range = $this->sizeRangeLabel();

        return $range ? $this->name.' — '.$range : $this->name;
    }

    private function generatedMetaDescription(): string
    {
        if ($this instanceof Product) {
            return MetaText::summarise($this->description, self::DESCRIPTION_LIMIT)
                ?? MetaText::summarise($this->fabric_care, self::DESCRIPTION_LIMIT)
                ?? $this->name.'. Available at Kayaa.';
        }

        return MetaText::summarise($this->description, self::DESCRIPTION_LIMIT)
            ?? 'Shop '.$this->name.' for babies at Kayaa. Soft cotton, sized by age, with island-wide delivery across Sri Lanka.';
    }
}
