<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** The customer's order history, newest first, each row linking to the order. */
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->weight('bold'),
                TextColumn::make('created_at')->label('Placed')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money((int) $state))->sortable(),
                TextColumn::make('payment_status')->label('Payment')->badge()
                    ->color(fn (string $state) => OrderResource::paymentColor($state)),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => OrderResource::statusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }
}
