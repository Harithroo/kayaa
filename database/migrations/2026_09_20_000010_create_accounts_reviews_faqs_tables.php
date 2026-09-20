<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers share the users table with admins; is_admin gates the Filament panel.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
        });

        // Guest checkout stays supported: user_id is null for guest orders.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index('email');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);              // display name, snapshotted
            $table->string('email', 190)->nullable();
            $table->unsignedTinyInteger('rating');   // 1-5
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);  // admin must approve before it shows
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_reply')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'is_approved']);
            $table->index('is_approved');
        });

        // Admin-managed FAQs. placement: product (product pages) or contact (contact page).
        // A product FAQ with no product_id/category_id shows on every product page.
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20)->default('product');
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('question', 200);
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['placement', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('reviews');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropIndex(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
