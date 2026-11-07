<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\SiteMode;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Open the shop, or put a coming-soon or under-maintenance notice in front of
 * the whole storefront. The notice is served by
 * App\Http\Middleware\SiteAvailability; /admin always stays reachable.
 *
 * @property-read Schema $form
 */
class SiteAvailability extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Site availability';

    protected static ?string $title = 'Site availability';

    protected static string|\UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.site-availability';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        return SiteMode::isLive() ? null : SiteMode::label();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return SiteMode::isLive() ? null : 'warning';
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_mode' => SiteMode::current(),
            'site_mode_admin_bypass' => SiteMode::adminBypassEnabled(),
            'coming_soon_heading' => config('kayaa.coming_soon.heading'),
            'coming_soon_message' => config('kayaa.coming_soon.message'),
            'coming_soon_date' => config('kayaa.coming_soon.date'),
            'maintenance_heading' => config('kayaa.maintenance.heading'),
            'maintenance_message' => config('kayaa.maintenance.message'),
            'maintenance_back_at' => config('kayaa.maintenance.back_at'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('What customers see')
                    ->description('Applies to the whole storefront: home, shop, product pages, cart, checkout and the account pages. This admin area is never affected, so you can always switch back.')
                    ->schema([
                        Radio::make('site_mode')
                            ->label('Site mode')
                            ->required()
                            ->live()
                            ->options(SiteMode::LABELS)
                            ->descriptions([
                                SiteMode::LIVE => 'The shop is open. Customers can browse and order as normal.',
                                SiteMode::COMING_SOON => 'Every page shows the coming-soon notice below. Nothing can be browsed or ordered.',
                                SiteMode::MAINTENANCE => 'Every page shows the maintenance notice below and answers "temporarily unavailable", so search engines keep the shop in their index.',
                            ]),
                        Toggle::make('site_mode_admin_bypass')
                            ->label('Let signed-in admins keep browsing the shop')
                            ->helperText('On: you see the real storefront with a band at the top while customers see the notice. Off: you see exactly what a customer sees.')
                            ->visible(fn (Get $get) => $get('site_mode') !== SiteMode::LIVE),
                    ]),

                Section::make('Coming soon notice')
                    ->description('Shown on every storefront page while the mode above is "Coming soon".')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->collapsed(fn (Get $get) => $get('site_mode') !== SiteMode::COMING_SOON)
                    ->collapsible()
                    ->schema([
                        TextInput::make('coming_soon_heading')
                            ->label('Heading')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('Something lovely is on the way'),
                        Textarea::make('coming_soon_message')
                            ->label('Message')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('One or two sentences. The contact details from Contact details are added underneath automatically.'),
                        DateTimePicker::make('coming_soon_date')
                            ->label('Opening date')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Optional. Shown as "Opening 12 December 2026". Leave empty to say nothing about timing.'),
                    ]),

                Section::make('Under maintenance notice')
                    ->description('Shown on every storefront page while the mode above is "Under maintenance".')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->collapsed(fn (Get $get) => $get('site_mode') !== SiteMode::MAINTENANCE)
                    ->collapsible()
                    ->schema([
                        TextInput::make('maintenance_heading')
                            ->label('Heading')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('We are back shortly'),
                        Textarea::make('maintenance_message')
                            ->label('Message')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Reassure customers that carts and orders are safe.'),
                        DateTimePicker::make('maintenance_back_at')
                            ->label('Back online by')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Optional. Shown on the notice and sent to search engines as the time to come back.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getFormContentComponent()]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save changes')
                        ->submit('save'),
                ])->key('form-actions'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $values = [
            'site_mode' => (string) $data['site_mode'],
            'coming_soon_heading' => trim((string) $data['coming_soon_heading']),
            'coming_soon_message' => trim((string) $data['coming_soon_message']),
            'coming_soon_date' => $this->dateOrNull($data['coming_soon_date'] ?? null),
            'maintenance_heading' => trim((string) $data['maintenance_heading']),
            'maintenance_message' => trim((string) $data['maintenance_message']),
            'maintenance_back_at' => $this->dateOrNull($data['maintenance_back_at'] ?? null),
        ];

        // The toggle is only on screen while a notice is up, so a save made in
        // Live mode leaves the admin's choice as it was instead of clearing it.
        if (array_key_exists('site_mode_admin_bypass', $data)) {
            $values['site_mode_admin_bypass'] = $data['site_mode_admin_bypass'] ? '1' : '0';
        }

        Setting::putMany($values);

        // The storefront reads config, which this request filled before the save.
        config(['kayaa.site_mode' => (string) $data['site_mode']]);

        Notification::make()
            ->success()
            ->title('Site availability saved')
            ->body($this->statusLine((string) $data['site_mode']))
            ->send();
    }

    private function dateOrNull(mixed $value): ?string
    {
        return blank($value) ? null : (string) $value;
    }

    private function statusLine(string $mode): string
    {
        if ($mode === SiteMode::LIVE) {
            return 'The shop is open to customers.';
        }

        return SiteMode::label($mode).' is now showing on every storefront page.';
    }
}
