<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class GuestOrderLinkTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_only_unlinked_orders_with_the_same_email_and_phone_are_claimable(): void
    {
        $customer = $this->customer(['email' => 'nimali@example.test', 'phone' => '0771234567']);

        $match = $this->order();
        $otherPhone = $this->order(['phone' => '0779999999']);
        $otherEmail = $this->order(['email' => 'someone@example.test']);
        $alreadyOwned = $this->order(['user_id' => $this->customer()->id]);

        $claimable = $customer->claimableGuestOrders()->pluck('id');

        $this->assertEquals([$match->id], $claimable->all());
        $this->assertSame(1, $customer->claimGuestOrders());

        $this->assertSame($customer->id, $match->fresh()->user_id);
        $this->assertNull($otherPhone->fresh()->user_id);
        $this->assertNull($otherEmail->fresh()->user_id);
        $this->assertNotSame($customer->id, $alreadyOwned->fresh()->user_id);
    }

    public function test_an_account_with_no_phone_claims_nothing(): void
    {
        $customer = $this->customer(['email' => 'nimali@example.test', 'phone' => null]);
        $this->order();
        $this->order(['phone' => '']);

        $this->assertSame(0, $customer->claimableGuestOrders()->count());
        $this->assertSame(0, $customer->claimGuestOrders());
    }

    public function test_signing_in_uses_the_same_rule(): void
    {
        $customer = $this->customer(['email' => 'nimali@example.test', 'phone' => '0771234567']);
        $mine = $this->order();
        $notMine = $this->order(['phone' => '0770000000']);

        $this->post('/account/login', ['email' => 'nimali@example.test', 'password' => 'secret-pass-123'])
            ->assertRedirect();

        $this->assertSame($customer->id, $mine->fresh()->user_id);
        $this->assertNull($notMine->fresh()->user_id);
    }

    public function test_registering_claims_matching_orders_but_not_when_no_phone_is_given(): void
    {
        $mine = $this->order();

        $this->post('/account/register', [
            'name' => 'No Phone', 'email' => 'nimali@example.test',
            'password' => 'secret-pass-123', 'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect();

        $this->assertNull($mine->fresh()->user_id, 'email alone proves nothing');
    }

    public function test_the_customer_page_offers_to_link_and_says_how_many(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer(['email' => 'nimali@example.test', 'phone' => '0771234567']);
        $first = $this->order(['total' => 100000]);
        $second = $this->order(['total' => 50000]);
        $unrelated = $this->order(['phone' => '0770000000']);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getKey()])
            ->assertActionVisible('link_guest_orders')
            ->assertActionExists('link_guest_orders', fn ($action) => $action->getBadge() === '2'
                && str_contains((string) $action->getModalDescription(), '2 guest orders'))
            ->callAction('link_guest_orders');

        $this->assertSame($customer->id, $first->fresh()->user_id);
        $this->assertSame($customer->id, $second->fresh()->user_id);
        $this->assertNull($unrelated->fresh()->user_id);

        // Their history and totals now include what was linked.
        $row = CustomerResource::getEloquentQuery()->findOrFail($customer->id);
        $this->assertSame(2, $row->orders_count);
        $this->assertSame(150000, (int) $row->total_spent);
    }

    public function test_the_action_is_hidden_when_nothing_matches(): void
    {
        $this->actingAsAdmin();
        $noPhone = $this->customer(['phone' => null]);
        $this->order();
        $matched = $this->customer(['email' => 'zed@example.test', 'phone' => '0712223333']);

        Livewire::test(ViewCustomer::class, ['record' => $noPhone->getKey()])->assertActionHidden('link_guest_orders');
        Livewire::test(ViewCustomer::class, ['record' => $matched->getKey()])->assertActionHidden('link_guest_orders');
    }
}
