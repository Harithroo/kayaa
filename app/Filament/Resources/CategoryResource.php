<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Categories';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('department_id')->relationship('department', 'name')->required()->default(1),
            Select::make('parent_id')->label('Parent category')
                ->relationship('parent', 'name', fn ($query) => $query->whereNull('parent_id'))
                ->helperText('Leave empty for a top-level category shown in the navigation.'),
            TextInput::make('name')->required()->maxLength(60)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state, ?Category $record) => $record?->slug ?: $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->required()->maxLength(80),
            Textarea::make('description')->rows(3),
            TextInput::make('position')->numeric()->default(0)->helperText('Lower numbers come first.'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('department.name'),
                TextColumn::make('parent.name')->label('Parent')->placeholder('—'),
                TextColumn::make('products_count')->counts('products')->label('Products'),
                TextColumn::make('position')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('position')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategorys::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
