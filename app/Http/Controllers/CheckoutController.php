<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\OutOfStockException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('store.checkout', [
            'cart' => $this->cart,
            'districts' => config('kayaa.districts'),
        ]);
    }

    public function store(Request $request, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^0?7\d[\s-]?\d{3}[\s-]?\d{4}$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', Rule::in(config('kayaa.districts'))],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(['cod', 'payhere'])],
        ], [
            'phone.regex' => 'Enter a Sri Lankan mobile number, e.g. 077 123 4567.',
        ]);

        $data['phone'] = preg_replace('/\D+/', '', $data['phone']);
        $payment = $data['payment_method'];
        unset($data['payment_method']);

        // PayHere is wired up in a later step; until then every order is placed as COD.
        if ($payment === 'payhere' && ! config('services.payhere.merchant_id')) {
            $payment = 'cod';
        }

        try {
            $order = $orders->placeOrder($data, $payment);
        } catch (OutOfStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        // TODO (PayHere step): if ($payment === 'payhere') redirect to the PayHere form.

        return redirect()->route('orders.thanks', $order);
    }
}
