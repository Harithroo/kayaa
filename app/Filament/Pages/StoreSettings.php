<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
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

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.store-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'low_stock_threshold' => (int) config('kayaa.low_stock_threshold', 5),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
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

        Setting::putMany([
            'low_stock_threshold' => (string) (int) $data['low_stock_threshold'],
        ]);

        Notification::make()
            ->success()
            ->title('Store settings saved')
            ->send();
    }
}
