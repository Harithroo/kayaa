<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Sellable variants that are out, or at/under the threshold set in Store settings. */
class LowStock extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Running low')
            ->description('Active products only. Change the warning level in Store settings.')
            ->query(
                ProductVariant::query()
                    ->with(['product', 'sizeOption', 'color'])
                    ->where('is_active', true)
                    ->where('stock', '<=', max(0, ProductVariant::lowStockThreshold()))
                    ->whereHas('product', fn ($q) => $q->where('status', 'active'))
                    ->orderBy('stock')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('product.name')->label('Product')->limit(26)
                    ->description(fn (ProductVariant $v) => $v->label()),
                TextColumn::make('stock')->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn (int $state) => $state === 0 ? 'Sold out' : "{$state} left"),
            ])
            ->recordUrl(fn (ProductVariant $v) => ProductResource::getUrl('edit', ['record' => $v->product_id]))
            ->emptyStateHeading('Everything is stocked')
            ->emptyStateDescription('Nothing is at or below the warning level.')
            ->paginated(false);
    }
}
