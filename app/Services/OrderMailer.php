<?php

namespace App\Services;

use App\Mail\OrderPlaced;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Order email, kept deliberately forgiving: a mail server that is down or not
 * configured yet must never take a placed order down with it. The order records
 * confirmation_sent_at only when the message actually left, so the storefront
 * can say "confirmation sent" truthfully rather than assuming.
 */
class OrderMailer
{
    public function sendConfirmation(Order $order): bool
    {
        if (blank($order->email) || $order->confirmation_sent_at !== null) {
            return false;
        }

        try {
            Mail::to($order->email)->send(new OrderPlaced($order));
        } catch (Throwable $e) {
            Log::warning('Order confirmation email failed', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $order->forceFill(['confirmation_sent_at' => now()])->save();

        return true;
    }
}
