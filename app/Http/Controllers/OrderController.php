<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OnepayGateway;
use App\Services\OrderService;
use App\Support\OrderAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * The confirmation page. It shows the delivery address and phone number, so
     * access needs either the signed link issued at checkout or the signed-in
     * customer the order belongs to — the reference alone is not enough.
     */
    public function thanks(Request $request, Order $order): View
    {
        abort_unless(OrderAccess::allows($request, $order), 404);

        $order->load('items');

        return view('store.thanks', [
            'order' => $order,
            'canCancel' => $order->canCustomerCancel(),
            'canPay' => $order->isPayable() && app(OnepayGateway::class)->configured(),
        ]);
    }

    /**
     * Tracking from a link we issued (the confirmation page, a confirmation
     * email). The signature stands in for the phone number, so the lookup runs
     * immediately instead of asking for details the customer just gave us.
     */
    public function show(Request $request, Order $order): View
    {
        abort_unless($request->hasValidSignature(), 404);

        $order->load('items');

        return view('store.track', compact('order'));
    }

    public function trackForm(): View
    {
        return view('store.track', ['order' => null]);
    }

    /**
     * The public tracking form. Reference plus phone, both required: the
     * reference on its own would turn this into a lookup oracle for anyone
     * willing to guess at four random characters.
     */
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

    /** Shopper-initiated cancellation, allowed only while the order is untouched. */
    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless(OrderAccess::allows($request, $order), 404);

        if (! $order->canCustomerCancel()) {
            return redirect()->to($order->thanksUrl())
                ->with('error', 'This order is already on its way — please contact us and we will sort it out.');
        }

        $orders->cancel($order, 'Cancelled by the customer.');

        return redirect()->to($order->thanksUrl())
            ->with('success', 'Your order has been cancelled.');
    }
}
