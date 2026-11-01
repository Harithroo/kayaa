<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A print-friendly slip to put in the parcel. Staff only. */
class PackingSlipController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        // Same rule as the admin panel itself: is_admin or nothing. Guests are
        // sent to sign in by the `auth` middleware before they get here.
        abort_unless($request->user()?->is_admin, 403);

        return view('admin.packing-slip', ['order' => $order->load('items')]);
    }
}
