<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OnepayException;
use App\Services\OnepayGateway;
use App\Support\OrderAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private OnepayGateway $onepay) {}

    /**
     * Start or retry the online payment for an existing order. Reached from the
     * order page, never from checkout — the cart is emptied the moment the order
     * is created, so there is nothing left there to re-submit.
     */
    public function start(Request $request, Order $order): RedirectResponse
    {
        abort_unless(OrderAccess::allows($request, $order), 404);

        if ($order->payment_status === 'paid') {
            return redirect()->to($order->thanksUrl());
        }

        abort_unless($order->isPayable(), 404);

        if (! $this->onepay->configured()) {
            return redirect()->to($order->thanksUrl())
                ->with('error', 'Online payment is not available right now. Please contact us to pay for this order.');
        }

        try {
            $url = $this->onepay->createCheckoutLink(
                $order,
                returnUrl: $order->paymentReturnUrl(),
                callbackUrl: route('payment.callback'),
            );
        } catch (OnepayException $e) {
            Log::warning('Onepay redirect failed', ['order' => $order->reference, 'error' => $e->getMessage()]);

            return redirect()->to($order->thanksUrl())
                ->with('error', "We couldn't open the payment page. Your order is saved — please try again in a moment.");
        }

        return redirect()->away($url);
    }

    /**
     * Where Onepay sends the customer's browser afterwards. Nothing here is
     * trusted: the real answer comes from the status lookup, and if the callback
     * has not landed yet the page simply says the payment is being confirmed.
     */
    public function return(Request $request, Order $order): RedirectResponse
    {
        abort_unless(OrderAccess::allows($request, $order), 404);

        $transactionId = $request->string('transaction_id')->toString();

        if ($transactionId !== '' && $order->payment_status !== 'paid') {
            $this->settle($order, $transactionId);
        }

        $order->refresh();

        return redirect()->to($order->thanksUrl());
    }

    /**
     * Onepay's server-to-server callback. Posted from outside the browser
     * session, so it is CSRF-exempt (see bootstrap/app.php) and authenticates
     * itself by the transaction id surviving a status lookup.
     */
    public function callback(Request $request): JsonResponse
    {
        $transactionId = $request->string('transaction_id')->toString();
        $reference = $request->string('additional_data')->toString();

        if ($transactionId === '') {
            return response()->json(['message' => 'transaction_id missing'], 422);
        }

        $order = Order::where('reference', $reference)->first()
            ?? Order::where('payment_reference', $transactionId)->first();

        if ($order === null) {
            Log::warning('Onepay callback for an unknown order', [
                'transaction_id' => $transactionId,
                'additional_data' => $reference,
            ]);

            // 200 so Onepay stops retrying something we will never match.
            return response()->json(['message' => 'unknown order']);
        }

        $this->settle($order, $transactionId);

        return response()->json(['message' => 'ok']);
    }

    /** Ask Onepay what happened, then record it. Idempotent — safe to run twice. */
    private function settle(Order $order, string $transactionId): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        try {
            $result = $this->onepay->transactionStatus($transactionId);
        } catch (OnepayException $e) {
            Log::warning('Onepay status lookup failed', [
                'order' => $order->reference,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($result['paid']) {
            $order->markPaid($transactionId);

            return;
        }

        $order->markPaymentFailed($transactionId);

        Log::info('Onepay payment not successful', [
            'order' => $order->reference,
            'status' => $result['status'],
            'message' => $result['message'],
        ]);
    }
}
