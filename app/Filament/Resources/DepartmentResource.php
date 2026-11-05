<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(40),
            TextInput::make('slug')->required()->maxLength(40)->unique(ignoreRecord: true)
                ->helperText('URL prefix, e.g. "baby" → /baby/bodysuits'),
            TextInput::make('position')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->weight('bold')->size(TextSize::Medium),
                        TextColumn::make('slug')->size(TextSize::Small)->color('gray')
                            ->fontFamily(FontFamily::Mono),
                    ])->space(1)->grow(),
                    Split::make([
                        TextColumn::make('is_active')->label('Active')->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Hidden')
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->grow(false),
                        TextColumn::make('categories_count')->counts('categories')->label('Categories')
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedSquares2x2)
                            ->formatStateUsing(fn ($state) => (int) $state.' '.str('category')->plural((int) $state))
                            ->grow(false),
                    ])->grow(false),
                ])->from('md'),
            ])
            ->defaultSort('position')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
