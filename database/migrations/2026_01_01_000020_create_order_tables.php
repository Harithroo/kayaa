<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();          // KY-240917-A3F9, shown to the customer
            $table->string('status', 20)->default('pending');   // pending | confirmed | shipped | delivered | cancelled
            $table->string('payment_method', 20);               // cod | onepay
            $table->string('payment_status', 20)->default('pending'); // pending | paid | failed | refunded
            $table->string('payment_reference')->nullable();    // Onepay transaction id

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('district');
            $table->text('note')->nullable();

            $table->unsignedInteger('subtotal');   // CENTS
            $table->unsignedInteger('shipping');   // CENTS
            $table->unsignedInteger('total');      // CENTS

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('phone');
        });

        // Snapshots name and price at purchase time — never join back to live product data.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label');   // "Butter · 0–3m"
            $table->string('sku');
            $table->unsignedInteger('unit_price'); // CENTS
            $table->unsignedSmallInteger('qty');
            $table->unsignedInteger('line_total'); // CENTS
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
