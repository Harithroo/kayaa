<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Size scales: "Baby age" today, "Women alpha" later. See build plan §3A.
        Schema::create('size_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('age'); // age | alpha | numeric | one_size
            $table->timestamps();
        });

        Schema::create('size_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('size_scale_id')->constrained()->cascadeOnDelete();
            $table->string('label');                 // "0–3m", "M", "12"
            $table->unsignedSmallInteger('position'); // sort order — never sort labels alphabetically
            $table->unsignedSmallInteger('min_height_cm')->nullable();
            $table->unsignedSmallInteger('max_height_cm')->nullable();
            $table->decimal('min_weight_kg', 4, 1)->nullable();
            $table->decimal('max_weight_kg', 4, 1)->nullable();
            $table->timestamps();
            $table->unique(['size_scale_id', 'label']);
        });

        // Department = root of the category tree: Baby, Women, Home.
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['department_id', 'slug']);
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('hex', 7); // swatch colour, e.g. #FBEBD2
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('size_scale_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('collection_label')->nullable(); // eyebrow text, e.g. "Kayaa Essentials"
            $table->text('description')->nullable();
            $table->text('fabric_care')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // The sellable unit: size × colour, own SKU, price, stock.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_option_id')->constrained();
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->unique();
            $table->unsignedInteger('price');                     // CENTS
            $table->unsignedInteger('compare_at_price')->nullable(); // CENTS, "was" price
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'size_option_id', 'color_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Cross-department groupings, e.g. "Reusables", "Gift sets".
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('size_options');
        Schema::dropIfExists('size_scales');
    }
};
