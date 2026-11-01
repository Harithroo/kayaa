<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Filled in by the admin when marking an order shipped.
            $table->string('courier', 60)->nullable()->after('shipped_at');
            $table->string('tracking_number', 80)->nullable()->after('courier');
            // Internal only — never shown to the customer (unlike `note`, which they wrote).
            $table->text('admin_note')->nullable()->after('tracking_number');
            $table->timestamp('shipped_email_sent_at')->nullable()->after('confirmation_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier', 'tracking_number', 'admin_note', 'shipped_email_sent_at']);
        });
    }
};
