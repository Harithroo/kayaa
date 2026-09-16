<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?Product $record) => $record?->slug ?: $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->maxLength(140)->unique(ignoreRecord: true)
                    ->helperText('Used in the URL: /products/{slug}'),
                Select::make('category_id')->label('Category')
                    ->relationship('category', 'name')->required()->searchable()->preload(),
                Select::make('size_scale_id')->label('Size scale')
                    ->relationship('sizeScale', 'name')->required()->default(1)
                    ->helperText('Baby age for now. Add a Women scale later without touching products.'),
                TextInput::make('collection_label')->label('Eyebrow label')
                    ->placeholder('Kayaa Essentials')->maxLength(60),
                Select::make('status')->options([
                    'draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived',
                ])->default('draft')->required(),
                Toggle::make('is_new')->label('Show "New" badge'),
                Toggle::make('is_featured')->label('Featured on home page'),
            ]),

            Section::make('Description')->schema([
                Textarea::make('description')->rows(4)
                    ->helperText('Fabric composition and what it is for. Plain text, shown at the top of "Fabric & care".'),
                Textarea::make('fabric_care')->label('Care instructions')->rows(4)
                    ->helperText('One instruction per line. Rendered as a bullet list.'),
            ]),

            Section::make('Variants')
                ->description('One row per size × colour. Prices in rupees; stock is the sellable quantity.')
                ->schema([
                    Repeater::make('variants')->relationship()->hiddenLabel()
                        ->schema([
                            Select::make('size_option_id')->label('Size')
                                ->relationship('sizeOption', 'label', fn ($query) => $query->orderBy('size_scale_id')->orderBy('position'))
                                ->required(),
                            Select::make('color_id')->label('Colour')
                                ->relationship('color', 'name')->searchable()->preload(),
                            TextInput::make('sku')->label('SKU')->required()->maxLength(40),
                            TextInput::make('price')->label('Price (Rs.)')->numeric()->required()->minValue(0)
                                ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                                ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                            TextInput::make('compare_at_price')->label('Was (Rs.)')->numeric()->minValue(0)
                                ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                                ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                            TextInput::make('stock')->numeric()->default(0)->minValue(0)->required(),
                            Toggle::make('is_active')->label('On sale')->default(true)->inline(false),
                        ])
                        ->columns(7)
                        ->defaultItems(1)
                        ->addActionLabel('Add variant')
                        ->itemLabel(fn (array $state) => $state['sku'] ?? null),
                ]),

            Section::make('Photos')
                ->description('4:5 portrait, consistent background. First photo is the card image. Optionally tie a photo to a colour.')
                ->schema([
                    Repeater::make('images')->relationship()->hiddenLabel()
                        ->schema([
                            FileUpload::make('path')->label('Photo')->image()
                                ->disk('public')->directory('products')
                                ->imageEditor()->imageEditorAspectRatios(['4:5'])
                                ->maxSize(2048)->required(),
                            TextInput::make('alt')->label('Alt text')->maxLength(160),
                            Select::make('color_id')->label('Colour')->relationship('color', 'name')->preload(),
                        ])
                        ->columns(3)
                        ->orderColumn('position')
                        ->reorderable()
                        ->addActionLabel('Add photo'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('variants_count')->counts('variants')->label('Variants'),
                TextColumn::make('variants_sum_stock')->sum('variants', 'stock')->label('Stock'),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'active' => 'success', 'draft' => 'gray', default => 'warning',
                }),
                IconColumn::make('is_new')->boolean()->label('New'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived',
                ]),
                SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
