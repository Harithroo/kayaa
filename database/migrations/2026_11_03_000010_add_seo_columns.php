<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable search metadata. Every field is optional: left blank, the
        // storefront generates a value from the record itself (see HasSeoMeta), so
        // a product saved without touching this section still ships a real title
        // and description. Deliberately no `keywords` column — search engines have
        // ignored the tag since 2009.
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_title', 70)->nullable()->after('description');
            $table->string('meta_description', 170)->nullable()->after('meta_title');
            // The image used when the page is shared to WhatsApp or Instagram.
            // Blank falls back to the product's first photo.
            $table->foreignId('og_image_id')->nullable()->after('meta_description')
                ->constrained('product_images')->nullOnDelete();
            // One-off exclusion, for a product that should stay out of search
            // results while remaining buyable.
            $table->boolean('noindex')->default(false)->after('og_image_id');
        });

        // `categories.description` already holds the intro copy rendered on the
        // listing page, so no body column is added here.
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_title', 70)->nullable()->after('description');
            $table->string('meta_description', 170)->nullable()->after('meta_title');
            $table->boolean('noindex')->default(false)->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('og_image_id');
            $table->dropColumn(['meta_title', 'meta_description', 'noindex']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'noindex']);
        });
    }
};
