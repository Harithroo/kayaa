<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Who may see or act on a single order.
 *
 * Two ways in, and deliberately no third: a valid URL signature (the link the
 * backend handed out after checkout, which is how guests get back to their own
 * order), or being the signed-in customer the order belongs to.
 *
 * Matching on email alone used to count as ownership. It does not any more —
 * anyone could register with someone else's address and read their order,
 * address and phone included.
 */
class OrderAccess
{
    public static function allows(Request $request, Order $order): bool
    {
        if ($request->hasValidSignature()) {
            return true;
        }

        $user = $request->user();

        return $user !== null && $order->user_id === $user->id;
    }
}
