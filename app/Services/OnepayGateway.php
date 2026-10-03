<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Onepay v3 (https://docs.onepay.lk).
 *
 * Hosted redirect: we ask Onepay for a checkout link, send the customer there,
 * and they come back to transaction_redirect_url. Onepay also posts a callback
 * to us. Neither the return trip nor the callback is trusted on its own — the
 * payment is only marked paid after a server-to-server status lookup, because
 * anything arriving through the browser can be forged.
 *
 * Authentication is the app id in the body plus a SHA-256 hash of
 * app_id + currency + amount + hash salt. The salt never leaves the server.
 */
class OnepayGateway
{
    public function configured(): bool
    {
        return filled(config('services.onepay.app_id')) && filled(config('services.onepay.hash_salt'));
    }

    /**
     * Ask Onepay for a hosted checkout URL for this order.
     *
     * @throws OnepayException
     */
    public function createCheckoutLink(Order $order, string $returnUrl, string $callbackUrl): string
    {
        $amount = $order->amountForGateway();
        $currency = config('kayaa.currency', 'LKR');

        $payload = [
            'app_id' => (string) config('services.onepay.app_id'),
            'amount' => (float) $amount,
            'currency' => $currency,
            'hash' => $this->hash($currency, $amount),
            'reference' => $order->reference,
            'customer_first_name' => $order->first_name,
            'customer_last_name' => $order->last_name,
            'customer_phone_number' => $this->e164($order->phone),
            'customer_email' => $order->email ?: config('kayaa.email'),
            'transaction_redirect_url' => $returnUrl,
            // Comes back verbatim on the callback, so we can find the order
            // even if the transaction id is all we are given.
            'additionalData' => $order->reference,
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders($this->headers())
                ->timeout(20)
                ->post($this->url('/v3/checkout/link/'), $payload + ['callback_url' => $callbackUrl]);
        } catch (ConnectionException $e) {
            throw new OnepayException('Could not reach Onepay: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            Log::warning('Onepay checkout link failed', [
                'order' => $order->reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new OnepayException('Onepay refused the request (HTTP '.$response->status().').');
        }

        $url = $this->findRedirectUrl($response->json() ?? []);

        if ($url === null) {
            Log::warning('Onepay response carried no redirect URL', [
                'order' => $order->reference,
                'body' => $response->body(),
            ]);

            throw new OnepayException('Onepay did not return a checkout URL.');
        }

        return $url;
    }

    /**
     * Server-to-server check of what actually happened. This, not the callback
     * body, decides whether an order counts as paid.
     *
     * @return array{paid: bool, status: int|null, message: string|null}
     */
    public function transactionStatus(string $transactionId): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders($this->headers())
                ->timeout(20)
                ->post($this->url('/v3/transaction/status/'), [
                    'app_id' => (string) config('services.onepay.app_id'),
                    'onepay_transaction_id' => $transactionId,
                ]);
        } catch (ConnectionException $e) {
            throw new OnepayException('Could not reach Onepay: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new OnepayException('Onepay status lookup failed (HTTP '.$response->status().').');
        }

        $body = $response->json() ?? [];
        $status = $this->findFirst($body, ['status', 'transaction_status', 'status_code']);
        $message = $this->findFirst($body, ['status_message', 'message']);

        return [
            // Onepay reports success as status 1 / "SUCCESS" depending on the field.
            'paid' => in_array((string) $status, ['1', '1000', 'SUCCESS', 'success'], true),
            'status' => is_numeric($status) ? (int) $status : null,
            'message' => is_string($message) ? $message : null,
        ];
    }

    public function hash(string $currency, string $amount): string
    {
        return hash('sha256', config('services.onepay.app_id').$currency.$amount.config('services.onepay.hash_salt'));
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        $token = config('services.onepay.app_token');

        return filled($token) ? ['Authorization' => $token] : [];
    }

    private function url(string $path): string
    {
        return config('services.onepay.base_url').$path;
    }

    /** Onepay wants E.164: 0771234567 becomes +94771234567. */
    private function e164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '94')) {
            return '+'.$digits;
        }

        return '+94'.ltrim($digits, '0');
    }

    /**
     * The redirect URL's position in the response has moved between Onepay
     * versions, so look for it rather than assuming a path.
     *
     * @param  array<mixed>  $body
     */
    private function findRedirectUrl(array $body): ?string
    {
        $value = $this->findFirst($body, ['redirect_url', 'gateway_url', 'payment_url', 'url', 'link']);

        return is_string($value) && str_starts_with($value, 'http') ? $value : null;
    }

    /**
     * Depth-first search for the first of $keys present anywhere in the payload.
     *
     * @param  array<mixed>  $body
     * @param  array<string>  $keys
     */
    private function findFirst(array $body, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $body)) {
                return $body[$key];
            }
        }

        foreach ($body as $value) {
            if (is_array($value)) {
                $found = $this->findFirst($value, $keys);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
