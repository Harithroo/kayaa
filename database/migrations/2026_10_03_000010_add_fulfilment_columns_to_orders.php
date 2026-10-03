<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // One timestamp per step of the order stepper. paid_at and shipped_at
            // already existed; these complete the set. Null means "not yet".
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');

            // Set when the confirmation email actually left the server, so the
            // storefront can say "confirmation sent" truthfully instead of guessing.
            $table->timestamp('confirmation_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'cancelled_at', 'confirmation_sent_at']);
        });
    }
};
