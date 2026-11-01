<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Color;
use App\Models\Department;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SizeOption;
use App\Models\SizeScale;
use App\Models\User;
use Filament\Facades\Filament;

/** Small builders so each test reads as the scenario, not the plumbing. */
trait BuildsShopData
{
    private int $seq = 0;

    /** Signs in as an admin and points Filament at the admin panel. */
    protected function actingAsAdmin(): User
    {
        $admin = User::create([
            'name' => 'Shop Owner',
            'email' => 'owner'.++$this->seq.'@example.test',
            'password' => 'secret-pass-123',
            'is_admin' => true,
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        return $admin;
    }

    protected function customer(array $overrides = []): User
    {
        return User::create([
            'name' => 'Nimali Perera',
            'email' => 'cust'.++$this->seq.'@example.test',
            'password' => 'secret-pass-123',
            'is_admin' => false,
            ...$overrides,
        ]);
    }

    protected function variant(array $overrides = [], ?Product $product = null, array $productOverrides = []): ProductVariant
    {
        $n = ++$this->seq;

        $product ??= $this->product($productOverrides);

        $scale = $product->sizeScale;
        $size = SizeOption::create([
            'size_scale_id' => $scale->id,
            'label' => "Size {$n}",
            'position' => $n,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'size_option_id' => $size->id,
            'color_id' => Color::create(['name' => "Colour {$n}", 'slug' => "colour-{$n}", 'hex' => '#C3AFDA'])->id,
            'sku' => "SKU-{$n}",
            'price' => 250000,
            'stock' => 10,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    protected function product(array $overrides = []): Product
    {
        $n = ++$this->seq;

        $scale = SizeScale::firstOrCreate(['name' => 'Baby age'], ['type' => 'age']);
        $department = Department::firstOrCreate(['slug' => 'baby'], ['name' => 'Baby']);
        $category = Category::firstOrCreate(
            ['department_id' => $department->id, 'slug' => 'bodysuits'],
            ['name' => 'Bodysuits'],
        );

        return Product::create([
            'category_id' => $category->id,
            'size_scale_id' => $scale->id,
            'name' => "Cotton Romper {$n}",
            'slug' => "cotton-romper-{$n}",
            'status' => 'active',
            ...$overrides,
        ]);
    }

    /**
     * An order with one line per variant given (qty 1 each); a fresh variant if none.
     *
     * @param  array<int, ProductVariant>  $variants
     */
    protected function order(array $overrides = [], array $variants = []): Order
    {
        $variants = $variants ?: [$this->variant()];
        $subtotal = collect($variants)->sum('price');
        $shipping = 45000;

        // Timestamps are not mass-assignable; set them straight on the model.
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $order = Order::create([
            'reference' => Order::generateReference(),
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'first_name' => 'Nimali',
            'last_name' => 'Perera',
            'phone' => '0771234567',
            'email' => 'nimali@example.test',
            'address' => '12 Temple Road',
            'city' => 'Nugegoda',
            'district' => 'Colombo',
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            ...$overrides,
        ]);

        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        foreach ($variants as $variant) {
            $order->items()->create([
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_label' => $variant->label(),
                'sku' => $variant->sku,
                'unit_price' => $variant->price,
                'qty' => 1,
                'line_total' => $variant->price,
            ]);
        }

        return $order;
    }
}
