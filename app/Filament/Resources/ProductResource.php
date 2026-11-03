<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    // Search the whole admin for a product by name, URL slug or SKU.
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'variants.sku'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Category' => $record->category?->name ?? '—', 'Status' => ucfirst($record->status)];
    }

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

            Section::make('SEO')
                ->description('How this product appears in Google and when its link is shared. Every field is optional — left blank, the storefront writes one from the product itself.')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Search title')
                        ->maxLength(70)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'Shown as the blue link in Google. Around 60 characters reads best; 70 is the limit. '.mb_strlen((string) $state).'/70 used.')
                        // The placeholder is the value that actually ships when
                        // this is left blank, so the admin can see it rather
                        // than guess at it.
                        ->placeholder(fn (?Product $record) => $record?->metaTitle() ?? 'Generated from the product name and size range')
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label('Search description')
                        ->maxLength(170)
                        ->rows(2)
                        ->live(debounce: 500)
                        ->helperText(fn (?string $state) => 'Not a ranking factor — this is the sales pitch under the link. Around 155 characters. '.mb_strlen((string) $state).'/170 used.')
                        ->placeholder(fn (?Product $record) => $record?->metaDescription() ?? 'Generated from the product description')
                        ->columnSpanFull(),
                    Select::make('og_image_id')
                        ->label('Share image')
                        ->helperText('Used when the link is pasted into WhatsApp or Instagram. Defaults to the first photo.')
                        ->options(fn (?Product $record) => $record
                            ? $record->images()->get()->mapWithKeys(fn ($image) => [
                                $image->id => $image->alt ?: 'Photo '.($image->position + 1),
                            ])->all()
                            : [])
                        ->placeholder('First photo')
                        ->hidden(fn (?Product $record) => $record === null),
                    Toggle::make('noindex')
                        ->label('Hide from search engines')
                        ->helperText('The product stays on the site and stays buyable; it just will not appear in search results.')
                        ->inline(false),
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
                            TextInput::make('alt')->label('Alt text')->required()->maxLength(160)
                                ->helperText('Describe the garment as you would to someone who cannot see it, e.g. "Butter yellow ribbed romper, long sleeves". Read aloud by screen readers and read by Google.'),
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
        $low = fn () => max(0, ProductVariant::lowStockThreshold());

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['images', 'variants', 'category']))
            ->columns([
                ImageColumn::make('thumb')->label('')->disk('public')->square()->size(48)
                    ->state(fn (Product $r) => $r->images->first()?->path),
                TextColumn::make('name')->searchable()->sortable()
                    ->description(fn (Product $r) => $r->variants->pluck('sku')->take(2)->implode(', ')
                        .($r->variants->count() > 2 ? '…' : '')),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('price')->label('Price')
                    ->state(function (Product $r) {
                        $prices = $r->variants->where('is_active', true)->pluck('price');
                        if ($prices->isEmpty()) {
                            return '—';
                        }

                        return $prices->min() === $prices->max()
                            ? money($prices->min())
                            : money($prices->min()).' – '.money($prices->max());
                    }),
                TextColumn::make('variants_sum_stock')->sum('variants', 'stock')->label('Stock')->sortable()
                    ->badge()
                    ->color(fn ($state) => (int) $state === 0 ? 'danger' : ((int) $state <= $low() ? 'warning' : 'success'))
                    ->formatStateUsing(fn ($state) => (int) $state === 0 ? 'Sold out' : (string) (int) $state)
                    ->description(function (Product $r) use ($low) {
                        $n = $r->variants->where('is_active', true)->filter(fn ($v) => $v->stock <= $low())->count();

                        return $n > 0 ? "{$n} ".str('variant')->plural($n).' low or out' : null;
                    }),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'active' => 'success', 'draft' => 'gray', default => 'warning',
                }),
                IconColumn::make('is_featured')->boolean()->label('Featured')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_new')->boolean()->label('New')->toggleable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived',
                ]),
                SelectFilter::make('category')->relationship('category', 'name'),
                Filter::make('low_stock')->label('Low or sold out')->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('variants', fn ($v) => $v
                        ->where('is_active', true)
                        ->where('stock', '<=', $low()))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')->label('Duplicate')->icon(Heroicon::DocumentDuplicate)->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Makes a draft copy with its own SKUs and photos. Nothing goes live until you publish it.')
                    ->action(function (Product $record) {
                        $copy = $record->duplicate();

                        Notification::make()->success()->title('Draft copy created')
                            ->actions([Action::make('open')->label('Open copy')->url(static::getUrl('edit', ['record' => $copy]))])
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::statusBulkAction('publish', 'Publish selected', 'active', Heroicon::Eye, 'success'),
                    self::statusBulkAction('draft', 'Move to draft', 'draft', Heroicon::PencilSquare, 'gray'),
                    self::statusBulkAction('archive', 'Archive selected', 'archived', Heroicon::ArchiveBox, 'warning'),
                    BulkAction::make('delete')->label('Delete selected')->icon(Heroicon::Trash)->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Products that appear in past orders cannot be deleted — they are kept, and you can archive them instead.')
                        ->action(function (Collection $records) {
                            $deleted = 0;
                            $kept = 0;
                            foreach ($records as $product) {
                                $product->delete() ? $deleted++ : $kept++;
                            }

                            Notification::make()
                                ->title("{$deleted} ".str('product')->plural($deleted).' deleted')
                                ->body($kept > 0 ? "{$kept} kept because customers have ordered them. Archive those instead." : null)
                                ->color($kept > 0 ? 'warning' : 'success')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected static function statusBulkAction(string $name, string $label, string $status, Heroicon $icon, string $color): BulkAction
    {
        return BulkAction::make($name)->label($label)->icon($icon)->color($color)
            ->action(function (Collection $records) use ($status) {
                $records->each->update(['status' => $status]);

                Notification::make()->success()
                    ->title($records->count().' '.str('product')->plural($records->count()).' set to '.$status)
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
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
