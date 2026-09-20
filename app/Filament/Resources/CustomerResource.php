<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
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

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'customer';

    protected static ?int $navigationSort = 9;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', false)->withCount('orders');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('email')->copyable(),
            TextEntry::make('phone')->placeholder('—'),
            TextEntry::make('created_at')->label('Joined')->dateTime('d M Y, H:i'),
            TextEntry::make('orders_count')->label('Orders placed'),
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
                TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([DeleteAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
        ];
    }
}
