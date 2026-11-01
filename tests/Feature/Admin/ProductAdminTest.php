<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\BuildsShopData;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use BuildsShopData, RefreshDatabase;

    public function test_a_product_that_was_ordered_cannot_be_deleted(): void
    {
        $variant = $this->variant();
        $this->order([], [$variant]);
        $product = $variant->product;

        $this->assertTrue($product->hasOrders());
        $this->assertFalse($product->delete());
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_an_unordered_product_can_be_deleted(): void
    {
        $product = $this->variant()->product;

        $this->assertFalse($product->hasOrders());
        $this->assertTrue($product->delete());
    }

    public function test_bulk_delete_removes_unordered_products_and_keeps_ordered_ones(): void
    {
        $this->actingAsAdmin();
        $sold = $this->variant()->product;
        $this->order([], [$sold->variants->first()]);
        $unsold = $this->product();

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$sold->getKey(), $unsold->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseHas('products', ['id' => $sold->id]);
        $this->assertDatabaseMissing('products', ['id' => $unsold->id]);
    }

    public function test_bulk_status_actions(): void
    {
        $this->actingAsAdmin();
        $a = $this->product(['status' => 'draft']);
        $b = $this->product(['status' => 'draft']);

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$a->getKey(), $b->getKey()])
            ->callAction(TestAction::make('publish')->table()->bulk());

        $this->assertSame('active', $a->fresh()->status);
        $this->assertSame('active', $b->fresh()->status);

        Livewire::test(ListProducts::class)
            ->selectTableRecords([$a->getKey()])
            ->callAction(TestAction::make('archive')->table()->bulk());

        $this->assertSame('archived', $a->fresh()->status);
        $this->assertSame('active', $b->fresh()->status);
    }

    public function test_duplicate_makes_a_draft_with_its_own_slug_skus_and_photo_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/original.jpg', 'fake-image-bytes');

        $variant = $this->variant(['stock' => 7, 'price' => 180000], productOverrides: ['name' => 'Linen Romper', 'is_featured' => true]);
        $original = $variant->product;
        ProductImage::create(['product_id' => $original->id, 'path' => 'products/original.jpg', 'position' => 0]);

        $copy = $original->fresh()->duplicate();

        $this->assertSame('draft', $copy->status);
        $this->assertFalse($copy->is_featured);
        $this->assertSame('Linen Romper (copy)', $copy->name);
        $this->assertNotSame($original->slug, $copy->slug);

        $copyVariant = $copy->variants()->firstOrFail();
        $this->assertNotSame($variant->sku, $copyVariant->sku);
        $this->assertSame(7, $copyVariant->stock);
        $this->assertSame(180000, $copyVariant->price);

        $copyImage = $copy->images()->firstOrFail();
        $this->assertNotSame('products/original.jpg', $copyImage->path);
        Storage::disk('public')->assertExists($copyImage->path);
        Storage::disk('public')->assertExists('products/original.jpg');

        // Duplicating twice must not collide on slug or SKU.
        $second = $original->fresh()->duplicate();
        $this->assertNotSame($copy->slug, $second->slug);
    }

    public function test_duplicate_action_on_the_list(): void
    {
        $this->actingAsAdmin();
        $product = $this->variant()->product;

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('duplicate')->table($product))
            ->assertHasNoActionErrors();

        $this->assertSame(2, Product::count());
    }

    public function test_low_stock_filter(): void
    {
        $this->actingAsAdmin();
        Setting::putMany(['low_stock_threshold' => '5']);
        config(['kayaa.low_stock_threshold' => 5]);

        $low = $this->variant(['stock' => 2])->product;
        $fine = $this->variant(['stock' => 50])->product;

        Livewire::test(ListProducts::class)
            ->filterTable('low_stock')
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$fine]);
    }

    public function test_edit_page_offers_archive_instead_of_delete_for_sold_products(): void
    {
        $this->actingAsAdmin();
        $variant = $this->variant();
        $this->order([], [$variant]);
        $product = $variant->product;

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionHidden('delete')
            ->assertActionVisible('archive')
            ->callAction('archive');

        $this->assertSame('archived', $product->fresh()->status);
    }

    public function test_edit_page_allows_delete_for_unsold_products(): void
    {
        $this->actingAsAdmin();
        $product = $this->variant()->product;

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionVisible('delete')
            ->assertActionHidden('archive');
    }
}
