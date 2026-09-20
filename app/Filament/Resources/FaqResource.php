<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Where it shows')->columns(2)->schema([
                Select::make('placement')->options(Faq::PLACEMENTS)->required()->default('product')->live(),
                Toggle::make('is_active')->label('Switched on')->default(true),
                Select::make('category_id')->label('Only this category')->relationship('category', 'name')
                    ->searchable()->preload()->placeholder('All categories')
                    ->visible(fn (Get $get) => $get('placement') === 'product')
                    ->helperText('Leave empty to show on every product page.'),
                Select::make('product_id')->label('Only this product')->relationship('product', 'name')
                    ->searchable()->preload()->placeholder('All products')
                    ->visible(fn (Get $get) => $get('placement') === 'product')
                    ->helperText('Overrides the category choice — pins this FAQ to one product.'),
                TextInput::make('position')->numeric()->default(0)
                    ->helperText('Lower shows first.'),
            ]),
            Section::make('Content')->schema([
                TextInput::make('question')->required()->maxLength(200)
                    ->placeholder('How do I pick the right size?'),
                Textarea::make('answer')->required()->rows(4)
                    ->placeholder('Sizes follow baby age. If your little one is between bands, size up.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')->label('')->boolean(),
                TextColumn::make('placement')->badge()->formatStateUsing(fn (string $state) => Faq::PLACEMENTS[$state] ?? $state),
                TextColumn::make('question')->searchable()->limit(60)->wrap(),
                TextColumn::make('target')->label('Shows on')->state(fn (Faq $r) => $r->targetLabel()),
                TextColumn::make('position')->sortable(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->filters([
                SelectFilter::make('placement')->options(Faq::PLACEMENTS),
                SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
