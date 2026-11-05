<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The people who can sign in to /admin (users with is_admin = true). Customers
 * are managed on the Customers screen. Whether an admin may be deleted is
 * decided by User::canBeDeleted(), so the button and the model agree.
 */
class AdminUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?string $navigationLabel = 'Admin team';

    protected static ?string $modelLabel = 'admin';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(80),
            TextInput::make('email')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
            TextInput::make('password')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                // On edit, leaving it blank keeps the current password.
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('name')->searchable()->weight('bold')->size(TextSize::Medium)
                        ->formatStateUsing(fn (User $r, $state) => $r->is(auth()->user()) ? "{$state} (you)" : $state)
                        ->grow(),
                    Split::make([
                        TextColumn::make('email')->searchable()->copyable()
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedEnvelope)
                            ->grow(false),
                        TextColumn::make('created_at')->label('Added')->date('d M Y')->sortable()
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedCalendar)
                            ->grow(false),
                    ])->grow(false),
                ])->from('md'),
            ])
            ->defaultSort('created_at')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (User $record) => $record->canBeDeleted()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}
