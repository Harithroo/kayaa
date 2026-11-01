<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest orders')
            ->query(Order::query()->latest()->limit(8))
            ->columns([
                TextColumn::make('reference')->weight('bold'),
                TextColumn::make('created_at')->label('Placed')->since(),
                TextColumn::make('customer_name')->label('Customer')
                    ->state(fn (Order $r) => $r->customerName())
                    ->description(fn (Order $r) => $r->district),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money((int) $state)),
                TextColumn::make('payment_status')->label('Payment')->badge()
                    ->color(fn (string $state) => OrderResource::paymentColor($state)),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => OrderResource::statusColor($state)),
            ])
            ->recordUrl(fn (Order $r) => OrderResource::getUrl('view', ['record' => $r]))
            ->paginated(false);
    }
}
