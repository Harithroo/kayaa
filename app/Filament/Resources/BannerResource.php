<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Where it shows')->columns(2)->schema([
                Select::make('placement')->options(Banner::PLACEMENTS)->required()->default('home_promo')->live()
                    ->helperText('Top strip: one line above the header on every page. Home promo: the dark block on the home page.'),
                Select::make('style')->options(Banner::STYLES)->default('ink')
                    ->visible(fn (Get $get) => $get('placement') === 'home_promo'),
                Toggle::make('is_active')->label('Switched on')->default(true),
                TextInput::make('position')->numeric()->default(0)->helperText('Lower shows first when several are live.'),
                DateTimePicker::make('starts_at')->label('Show from')->seconds(false)->native(false)
                    ->helperText('Leave empty to show immediately.'),
                DateTimePicker::make('ends_at')->label('Hide after')->seconds(false)->native(false)
                    ->helperText('Leave empty to show until switched off.'),
            ]),
            Section::make('Content')->columns(2)->schema([
                TextInput::make('eyebrow')->maxLength(60)->placeholder('Mid-season')
                    ->visible(fn (Get $get) => $get('placement') === 'home_promo'),
                TextInput::make('title')->label(fn (Get $get) => $get('placement') === 'topbar' ? 'Text' : 'Heading')
                    ->required()->maxLength(120)->columnSpanFull()
                    ->placeholder(fn (Get $get) => $get('placement') === 'topbar' ? 'Island-wide delivery in 2–4 days · Free over Rs. 7,500' : 'Up to 30% off sleepwear'),
                TextInput::make('body')->maxLength(200)->columnSpanFull()->placeholder('Sleep bags, sleepsuits and vests. While stocks last.')
                    ->visible(fn (Get $get) => $get('placement') === 'home_promo'),
                TextInput::make('cta_label')->label('Button label')->maxLength(40)->placeholder('Shop the sale'),
                TextInput::make('cta_url')->label('Button / link URL')->maxLength(255)->placeholder('/shop?sale=1')
                    ->helperText('Relative (/shop?sale=1) or full (https://…).'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('placement')->badge()->formatStateUsing(fn ($s) => Banner::PLACEMENTS[$s] ?? $s),
                TextColumn::make('title')->searchable()->limit(50),
                IconColumn::make('live')->label('Live now')->boolean()->state(fn (Banner $r) => $r->isLive()),
                TextColumn::make('starts_at')->dateTime('d M, H:i')->placeholder('—'),
                TextColumn::make('ends_at')->dateTime('d M, H:i')->placeholder('—'),
                TextColumn::make('position')->sortable(),
            ])
            ->defaultSort('position')
            ->filters([SelectFilter::make('placement')->options(Banner::PLACEMENTS)])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
