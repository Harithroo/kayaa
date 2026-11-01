<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class CustomerAndSettingsTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_customer_page_totals_exclude_cancelled_orders(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $this->order(['user_id' => $customer->id, 'total' => 100000]);
        $this->order(['user_id' => $customer->id, 'total' => 50000, 'status' => 'delivered']);
        $this->order(['user_id' => $customer->id, 'total' => 900000, 'status' => 'cancelled']);

        $row = CustomerResource::getEloquentQuery()->findOrFail($customer->id);

        $this->assertSame(3, $row->orders_count);
        $this->assertSame(150000, (int) $row->total_spent);
        $this->assertSame(2, (int) $row->kept_orders_count);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getKey()])
            ->assertSee('Rs. 1,500')
            ->assertSee('Rs. 750');   // average of the two orders that count
    }

    public function test_customer_page_lists_their_orders_and_links_to_each(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $mine = $this->order(['user_id' => $customer->id]);
        $someoneElses = $this->order();

        Livewire::test(OrdersRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$someoneElses]);
    }

    public function test_customer_with_orders_cannot_be_deleted_but_an_empty_account_can(): void
    {
        $this->actingAsAdmin();
        $buyer = $this->customer();
        $this->order(['user_id' => $buyer->id]);
        $empty = $this->customer();

        Livewire::test(ListCustomers::class)
            ->assertTableActionHidden('delete', $buyer)
            ->assertTableActionVisible('delete', $empty);
    }

    public function test_admins_are_not_listed_as_customers(): void
    {
        $admin = $this->actingAsAdmin();
        $customer = $this->customer();

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer])
            ->assertCanNotSeeTableRecords([$admin]);
    }

    public function test_store_settings_page_saves_delivery_and_stock_numbers(): void
    {
        $this->actingAsAdmin();

        Livewire::test(StoreSettings::class)
            ->fillForm(['shipping_fee' => 550, 'free_shipping_over' => 9000, 'low_stock_threshold' => 8])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('550', Setting::get('shipping_fee'));
        $this->assertSame('9000', Setting::get('free_shipping_over'));
        $this->assertSame('8', Setting::get('low_stock_threshold'));
    }

    public function test_store_settings_validates_input(): void
    {
        $this->actingAsAdmin();

        Livewire::test(StoreSettings::class)
            ->fillForm(['shipping_fee' => -5, 'free_shipping_over' => null])
            ->call('save')
            ->assertHasFormErrors(['shipping_fee', 'free_shipping_over']);
    }

    public function test_saved_delivery_settings_override_the_env_defaults_at_boot(): void
    {
        config(['kayaa.shipping_fee' => 45000, 'kayaa.free_shipping_over' => 750000]);
        Setting::putMany(['shipping_fee' => '600', 'free_shipping_over' => '0']);

        $boot = new ReflectionMethod(AppServiceProvider::class, 'bootStoreSettings');
        $boot->invoke(new AppServiceProvider($this->app));

        $this->assertSame(60000, config('kayaa.shipping_fee'));
        // "0" is a real value (free delivery on everything), not "unset".
        $this->assertSame(0, config('kayaa.free_shipping_over'));
    }

    public function test_without_saved_settings_the_env_defaults_stand(): void
    {
        config(['kayaa.shipping_fee' => 45000, 'kayaa.free_shipping_over' => 750000]);

        $boot = new ReflectionMethod(AppServiceProvider::class, 'bootStoreSettings');
        $boot->invoke(new AppServiceProvider($this->app));

        $this->assertSame(45000, config('kayaa.shipping_fee'));
        $this->assertSame(750000, config('kayaa.free_shipping_over'));
    }
}
