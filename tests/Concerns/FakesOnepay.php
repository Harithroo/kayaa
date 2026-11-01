<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/** Onepay credentials and canned gateway responses, so no test touches the network. */
trait FakesOnepay
{
    protected function configureOnepay(): void
    {
        config([
            'services.onepay.app_id' => 'APP123',
            'services.onepay.hash_salt' => 'salty',
            'services.onepay.app_token' => 'tok_test',
            'services.onepay.base_url' => 'https://api.onepay.test',
        ]);
    }

    /** Http::fake stubs stack and the first match wins, so a test that changes its mind starts clean. */
    protected function resetHttpFakes(): void
    {
        Http::swap(new Factory);
    }

    /** What the status lookup answers: success (status 1) with the given extra fields nested in the body. */
    protected function fakeOnepayStatus(array $extra = [], int $status = 1): void
    {
        Http::fake([
            'api.onepay.test/v3/transaction/status/*' => Http::response([
                'status' => $status,
                'data' => $extra,
            ]),
        ]);
    }

    protected function fakeOnepayLink(string $url = 'https://pay.onepay.test/checkout/abc'): void
    {
        Http::fake([
            'api.onepay.test/v3/checkout/link/*' => Http::response(['data' => ['gateway' => ['redirect_url' => $url]]]),
        ]);
    }
}
