<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Store numbers the shop owner can change without a developer.
 *
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Store settings';

    protected static ?string $title = 'Store settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.store-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'low_stock_threshold' => (int) config('kayaa.low_stock_threshold', 5),
            'shipping_fee' => intdiv((int) config('kayaa.shipping_fee'), 100),
            'free_shipping_over' => intdiv((int) config('kayaa.free_shipping_over'), 100),
            'seo_title_suffix' => (string) config('seo.title_suffix', 'Kayaa'),
            'seo_default_og_image' => (string) config('seo.default_og_image', ''),
            'org_name' => (string) config('seo.org.name', 'Kayaa'),
            'org_logo_path' => (string) config('seo.org.logo_path', ''),
            'org_city' => (string) config('seo.org.city', ''),
            'org_region' => (string) config('seo.org.region', ''),
            'org_instagram' => (string) config('seo.org.instagram', ''),
            'org_facebook' => (string) config('seo.org.facebook', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Delivery charges')
                    ->description('Applied at checkout and quoted across the storefront (cart, delivery page, terms). Existing orders keep the fee they were placed with.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('shipping_fee')
                            ->label('Delivery fee')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->prefix('Rs.')
                            ->helperText('Charged per order.'),
                        TextInput::make('free_shipping_over')
                            ->label('Free delivery from')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10000000)
                            ->prefix('Rs.')
                            ->helperText('Orders at or above this subtotal ship free. Set to 0 to make all delivery free.'),
                    ]),

                Section::make('Stock')
                    ->description('Controls the urgency note customers see on a product page.')
                    ->schema([
                        TextInput::make('low_stock_threshold')
                            ->label('Low stock warning at')
                            ->helperText('A size shows "Only N left" once its stock drops to this number or below. Set it to 0 to never show the note.')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(999)
                            ->suffix('items'),
                    ]),

                Section::make('Search engines & sharing')
                    ->description('Used across every page: the name after each page title, the image shown when a link is shared, and the business details Google reads off the home page.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('seo_title_suffix')
                            ->label('Title suffix')
                            ->required()
                            ->maxLength(30)
                            ->helperText('Appended to every page title, e.g. "Bodysuits — Baby Clothes Sri Lanka | Kayaa".'),
                        TextInput::make('org_name')
                            ->label('Business name')
                            ->required()
                            ->maxLength(60)
                            ->helperText('The registered or trading name, as it should appear in search results.'),
                        FileUpload::make('seo_default_og_image')
                            ->label('Default share image')
                            ->image()
                            ->disk('public')
                            ->directory('seo')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['1200:630'])
                            ->maxSize(2048)
                            ->helperText('Shown when a page with no image of its own is shared to WhatsApp or Instagram. 1200 × 630.'),
                        FileUpload::make('org_logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('seo')
                            ->maxSize(1024)
                            ->helperText('Square, on a plain background. Google may show it beside the store name.'),
                        TextInput::make('org_city')
                            ->label('City')
                            ->maxLength(60)
                            ->placeholder('Colombo'),
                        TextInput::make('org_region')
                            ->label('Province')
                            ->maxLength(60)
                            ->placeholder('Western Province'),
                        TextInput::make('org_instagram')
                            ->label('Instagram URL')
                            ->url()
                            ->maxLength(200)
                            ->placeholder('https://www.instagram.com/...')
                            ->helperText('Tells Google this profile and the store are the same business.'),
                        TextInput::make('org_facebook')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(200)
                            ->placeholder('https://www.facebook.com/...'),
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
            'low_stock_threshold' => (string) (int) $data['low_stock_threshold'],
            'shipping_fee' => (string) (int) $data['shipping_fee'],
            'free_shipping_over' => (string) (int) $data['free_shipping_over'],
        ];

        // Stored as plain strings; an empty one falls back to config/seo.php.
        // A FileUpload can hand back a single-item array rather than a path,
        // so flatten before casting.
        foreach ([
            'seo_title_suffix', 'seo_default_og_image', 'org_name', 'org_logo_path',
            'org_city', 'org_region', 'org_instagram', 'org_facebook',
        ] as $key) {
            $value = $data[$key] ?? '';

            if (is_array($value)) {
                $value = reset($value) ?: '';
            }

            $values[$key] = trim((string) $value);
        }

        Setting::putMany($values);

        Notification::make()
            ->success()
            ->title('Store settings saved')
            ->send();
    }
}
