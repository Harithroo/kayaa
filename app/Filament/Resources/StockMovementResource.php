<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Read-only log of every stock change. Rows are written by the app, never by hand. */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Stock history';

    protected static ?string $modelLabel = 'stock movement';

    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'user']))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('label')->label('Variant')->searchable()
                    ->description(fn (StockMovement $r) => $r->sku),
                TextColumn::make('delta')->label('Change')->badge()
                    ->formatStateUsing(fn ($state) => ((int) $state > 0 ? '+' : '').(int) $state)
                    ->color(fn ($state) => (int) $state > 0 ? 'success' : 'danger'),
                TextColumn::make('stock_after')->label('Stock after'),
                TextColumn::make('reason')->badge()
                    ->formatStateUsing(fn (string $state) => StockMovement::REASONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'order_placed' => 'warning',
                        'order_cancelled' => 'info',
                        'admin_edit' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('order.reference')->label('Order')->placeholder('—'),
                TextColumn::make('user.name')->label('By')->placeholder('—'),
                TextColumn::make('note')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('reason')->options(StockMovement::REASONS),
                SelectFilter::make('product_id')->label('Product')
                    ->relationship('product', 'name')->searchable()->preload(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockMovements::route('/')];
    }
}
