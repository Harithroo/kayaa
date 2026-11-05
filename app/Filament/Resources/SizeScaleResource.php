<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SizeScaleResource\Pages;
use App\Models\SizeScale;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SizeScaleResource extends Resource
{
    protected static ?string $model = SizeScale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Size scales';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(40)->helperText('e.g. Baby age, Women alpha'),
            Select::make('type')->options([
                'age' => 'Age bands', 'alpha' => 'XS–XXL', 'numeric' => 'Numeric', 'one_size' => 'One size',
            ])->required()->default('age'),

            Section::make('Sizes')
                ->description('Position controls the order everywhere — filters, pickers and the size guide. Height and weight feed the size guide table.')
                ->schema([
                    Repeater::make('options')->relationship()->hiddenLabel()
                        ->schema([
                            TextInput::make('label')->required()->maxLength(20),
                            TextInput::make('position')->numeric()->required()->default(10),
                            TextInput::make('min_height_cm')->label('Min height (cm)')->numeric(),
                            TextInput::make('max_height_cm')->label('Max height (cm)')->numeric(),
                            TextInput::make('min_weight_kg')->label('Min weight (kg)')->numeric()->step(0.1),
                            TextInput::make('max_weight_kg')->label('Max weight (kg)')->numeric()->step(0.1),
                        ])
                        ->columns(6)
                        ->orderColumn('position')
                        ->reorderable()
                        ->addActionLabel('Add size'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('name')->weight('bold')->size(TextSize::Medium)->grow(),
                    TextColumn::make('type')->badge()->grow(false),
                    TextColumn::make('options_count')->counts('options')->label('Sizes')
                        ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedCube)
                        ->formatStateUsing(fn ($state) => (int) $state.' '.str('size')->plural((int) $state))
                        ->grow(false),
                    // Three short fields: one row at every width.
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSizeScales::route('/'),
            'create' => Pages\CreateSizeScale::route('/create'),
            'edit' => Pages\EditSizeScale::route('/{record}/edit'),
        ];
    }
}
