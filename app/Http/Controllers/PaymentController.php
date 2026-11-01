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

    /**
     * Ask Onepay what happened, then record it. Idempotent — safe to run twice.
     *
     * A successful transaction only proves that somebody paid something. Before it
     * settles *this* order it has to be shown to belong to it: otherwise a shopper
     * could pay for a cheap order and present that transaction id with an expensive
     * order's reference. So a transaction is rejected (and the order left exactly
     * as it was — not paid, and not failed either) when:
     *  - it is already recorded on a different order, or
     *  - Onepay reports an amount that differs from the order total, or
     *  - Onepay reports a reference that is not this order's.
     * Amount and reference are only checked when the response carries them.
     */
    private function settle(Order $order, string $transactionId): void
    {
        // Paid is final, and so is refunded: a retried callback must not turn money
        // that went back to the customer into a sale again.
        if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
            return;
        }

        // Only online orders are paid through the gateway; a COD order is settled
        // in cash at the door.
        if ($order->payment_method !== 'onepay') {
            Log::warning('Onepay payment presented for a non-online order', [
                'order' => $order->reference,
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        if (Order::where('payment_reference', $transactionId)->whereKeyNot($order->getKey())->exists()) {
            Log::warning('Onepay transaction already belongs to another order', [
                'order' => $order->reference,
                'transaction_id' => $transactionId,
            ]);

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
            if (! $this->transactionMatches($order, $result)) {
                Log::warning('Onepay transaction does not match the order', [
                    'order' => $order->reference,
                    'transaction_id' => $transactionId,
                    'expected_amount' => $order->total,
                    'reported_amount' => $result['amount'],
                    'reported_reference' => $result['reference'],
                ]);

                return;
            }

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

    /**
     * @param  array{amount: int|null, reference: string|null}  $result
     */
    private function transactionMatches(Order $order, array $result): bool
    {
        if ($result['amount'] !== null && $result['amount'] !== $order->total) {
            return false;
        }

        return $result['reference'] === null || $result['reference'] === $order->reference;
    }
}
