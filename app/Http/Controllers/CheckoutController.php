<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OnepayException;
use App\Services\OnepayGateway;
use App\Services\OrderMailer;
use App\Services\OrderService;
use App\Services\OutOfStockException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'deliveryDays' => config('kayaa.delivery_days'),
            'customer' => auth()->user(),
            'onlinePaymentAvailable' => app(OnepayGateway::class)->configured(),
        ]);
    }

    public function store(
        Request $request,
        OrderService $orders,
        OnepayGateway $onepay,
        OrderMailer $mailer,
    ): RedirectResponse {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^0?7\d[\s-]?\d{3}[\s-]?\d{4}$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', Rule::in(config('kayaa.districts'))],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in(Order::PAYMENT_METHODS)],
        ], [
            'phone.regex' => 'Enter a Sri Lankan mobile number, e.g. 077 123 4567.',
        ]);

        $data['phone'] = preg_replace('/\D+/', '', $data['phone']);
        $data['user_id'] = $request->user()?->id;   // null for guest checkout
        $payment = $data['payment_method'];
        unset($data['payment_method']);

        // Without Onepay credentials on the server there is no online path to
        // offer, so the order is placed as cash on delivery rather than failing.
        if ($payment === 'onepay' && ! $onepay->configured()) {
            $payment = 'cod';
        }

        try {
            $order = $orders->placeOrder($data, $payment);
        } catch (OutOfStockException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        $mailer->sendConfirmation($order);

        if ($payment === 'onepay') {
            try {
                return redirect()->away($onepay->createCheckoutLink(
                    $order,
                    returnUrl: $order->paymentReturnUrl(),
                    callbackUrl: route('payment.callback'),
                ));
            } catch (OnepayException $e) {
                // The order exists and the stock is held; the customer can retry
                // payment from the order page rather than checking out again.
                Log::warning('Onepay redirect failed at checkout', [
                    'order' => $order->reference,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->to($order->thanksUrl())
                    ->with('error', "Your order is saved, but we couldn't open the payment page. You can try the payment again below.");
            }
        }

        return redirect()->to($order->thanksUrl());
    }
}
