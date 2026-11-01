<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

/**
 * Best sellers over the last 30 days, by units. Cancelled orders do not count.
 *
 * Rows are groups, not records, so this feeds the table plain arrays instead of
 * an Eloquent query (Filament would add `ORDER BY order_items.id` to a grouped
 * query, which MySQL's strict mode rejects).
 */
class TopSellers extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top sellers, last 30 days')
            ->records(fn (): array => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->where('orders.created_at', '>=', now()->subDays(30)->startOfDay())
                ->selectRaw('order_items.product_name, SUM(order_items.qty) as units, SUM(order_items.line_total) as revenue')
                ->groupBy('order_items.product_name')
                ->orderByDesc('units')
                ->limit(8)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all())
            ->columns([
                TextColumn::make('product_name')->label('Product')->limit(28),
                TextColumn::make('units')->label('Sold')->numeric(),
                TextColumn::make('revenue')->formatStateUsing(fn ($state) => money((int) $state)),
            ])
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription('Orders from the last 30 days will show up here.')
            ->paginated(false);
    }
}
