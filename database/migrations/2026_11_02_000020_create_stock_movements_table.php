<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An append-only log of every stock change. The label and SKU are copied
        // at the time, like order_items, so the history survives a renamed or
        // deleted variant.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');                 // "Cotton Romper (Butter · NB)"
            $table->string('sku');
            $table->integer('delta');                // negative when stock goes down
            $table->integer('stock_after');
            $table->string('reason', 20);            // initial | order_placed | order_cancelled | admin_edit
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_variant_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
