<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-managed banners. placement: topbar (the thin strip above the header)
        // or home_promo (the dark promo block on the home page).
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20)->default('home_promo');
            $table->string('eyebrow', 60)->nullable();
            $table->string('title', 120);
            $table->string('body', 200)->nullable();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url')->nullable();
            $table->string('style', 20)->default('ink'); // ink | cream | butter
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['placement', 'is_active']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('contact', 120);
            $table->string('order_reference', 20)->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('banners');
    }
};
