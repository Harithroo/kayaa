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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Categories';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

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

            Section::make('SEO')
                ->description('How this category appears in Google. Both fields are optional — left blank, the storefront writes one from the category name and description.')
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Search title')
                        ->maxLength(70)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'Around 60 characters reads best; 70 is the limit. '.mb_strlen((string) $state).'/70 used.')
                        ->placeholder(fn (?Category $record) => $record?->metaTitle() ?? 'Generated from the category name'),
                    Textarea::make('meta_description')
                        ->label('Search description')
                        ->maxLength(170)
                        ->rows(2)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'The pitch under the link in Google. Around 155 characters. '.mb_strlen((string) $state).'/170 used.')
                        ->placeholder(fn (?Category $record) => $record?->metaDescription() ?? 'Generated from the description above'),
                    Toggle::make('noindex')
                        ->label('Hide from search engines')
                        ->helperText('The category stays browsable on the site; it just will not appear in search results.')
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('name')->searchable()->weight('bold')->size(TextSize::Medium),
                        // Department and parent are one breadcrumb, not two
                        // labelled fields.
                        TextColumn::make('path')
                            ->state(fn (Category $r) => collect([$r->department?->name, $r->parent?->name])
                                ->filter()->implode(' › '))
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedTag)
                            ->placeholder('Top level'),
                    ])->space(1)->grow(),
                    // A Split with no from() stays horizontal at every width, so
                    // these short facts run across the card instead of taking a
                    // line each and leaving the right half of a phone empty.
                    Split::make([
                        TextColumn::make('is_active')->label('Active')->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Hidden')
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->grow(false),
                        TextColumn::make('products_count')->counts('products')->label('Products')
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedShoppingBag)
                            ->formatStateUsing(fn ($state) => (int) $state.' '.str('product')->plural((int) $state))
                            ->grow(false),
                        TextColumn::make('position')->sortable()
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedHashtag)
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
            'index' => Pages\ListCategorys::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
