<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Customer accounts (admins are excluded — they are managed outside the panel). */
class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'customer';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_admin', false)
            ->withCount('orders')
            ->withCount(['orders as kept_orders_count' => fn (Builder $q) => $q->countsAsSale()])
            // Cancelled and refunded orders are not money the customer spent.
            ->withSum(['orders as total_spent' => fn (Builder $q) => $q->countsAsSale()], 'total')
            ->withMax('orders as last_order_at', 'created_at');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('phone')->placeholder('—')->copyable(),
                TextEntry::make('created_at')->label('Joined')->dateTime('d M Y, H:i'),
            ]),
            Section::make('Spending')->columns(4)->schema([
                TextEntry::make('orders_count')->label('Orders placed'),
                TextEntry::make('total_spent')->label('Total spent')->formatStateUsing(fn ($state) => money((int) $state))->placeholder('Rs. 0'),
                TextEntry::make('average_order')->label('Average order')
                    ->state(fn (User $r) => $r->kept_orders_count > 0
                        ? money((int) round($r->total_spent / $r->kept_orders_count))
                        : '—'),
                TextEntry::make('last_order_at')->label('Last order')->dateTime('d M Y')->placeholder('Never'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->searchable()->placeholder('—'),
                TextColumn::make('orders_count')->label('Orders')->sortable(),
                TextColumn::make('total_spent')->label('Spent')->formatStateUsing(fn ($state) => money((int) $state))->sortable()->placeholder('Rs. 0'),
                TextColumn::make('last_order_at')->label('Last order')->date('d M Y')->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                // Someone with orders keeps their history; only empty accounts can go.
                DeleteAction::make()->visible(fn (User $record) => $record->orders_count === 0),
            ]);
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
