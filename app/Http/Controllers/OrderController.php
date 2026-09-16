<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function thanks(Order $order): View
    {
        $order->load('items');

        return view('store.thanks', compact('order'));
    }

    public function trackForm(): View
    {
        return view('store.track', ['order' => null]);
    }

    public function track(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::with('items')
            ->where('reference', strtoupper(trim($data['reference'])))
            ->where('phone', preg_replace('/\D+/', '', $data['phone']))
            ->first();

        if (! $order) {
            return back()->withInput()->with('error', "We couldn't find an order with that number and phone.");
        }

        return view('store.track', compact('order'));
    }
}
