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
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?int $navigationSort = 1;

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

    /**
     * The banner's schedule as one readable phrase.
     *
     * Both ends are optional and each means something on its own, so the four
     * combinations are spelled out rather than printed as two dates with a
     * dash between them.
     */
    private static function scheduleSummary(Banner $banner): string
    {
        $from = $banner->starts_at?->format('d M Y');
        $until = $banner->ends_at?->format('d M Y');

        return match (true) {
            $from !== null && $until !== null => "{$from} – {$until}",
            $from !== null => "From {$from}",
            $until !== null => "Until {$until}",
            default => 'Always on',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('title')->searchable()->limit(50)
                            ->weight('bold')->size(TextSize::Medium),
                        // One line for the whole schedule. Two columns gave two
                        // lines that only made sense read together, and each
                        // needed its own label to say which end it was.
                        TextColumn::make('window')
                            ->state(fn (Banner $r) => self::scheduleSummary($r))
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedClock),
                    ])->space(1)->grow(),
                    Split::make([
                        // A worded badge rather than a tick: with no column header
                        // above it, a bare icon in a card says nothing.
                        TextColumn::make('live')->label('Live now')->badge()
                            ->state(fn (Banner $r) => $r->isLive() ? 'Live now' : 'Not live')
                            ->color(fn (Banner $r) => $r->isLive() ? 'success' : 'gray')
                            ->grow(false),
                        TextColumn::make('placement')->badge()->color('gray')
                            ->formatStateUsing(fn ($state) => Banner::PLACEMENTS[$state] ?? $state)
                            ->grow(false),
                        // Kept as a column, not just a default sort, so it stays
                        // in the toolbar's Sort by list.
                        TextColumn::make('position')->sortable()
                            ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedHashtag)
                            ->grow(false),
                    ])->grow(false),
                ])->from('md'),
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
