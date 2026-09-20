<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\StoreContact;
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
 * @property-read Schema $form
 */
class ContactSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Contact details';

    protected static ?string $title = 'Contact details';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.contact-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'contact_email' => config('kayaa.email'),
            'contact_phone' => config('kayaa.phone'),
            'contact_whatsapp' => config('kayaa.whatsapp'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Shown across the site')
                    ->description('Used in the footer, on the contact page and anywhere customers are asked to get in touch.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(190)
                            ->columnSpanFull()
                            ->placeholder('hello@kayaa.lk'),
                        TextInput::make('contact_phone')
                            ->label('Phone number (for calls)')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('077 000 0000')
                            ->helperText('Leave empty to hide the call link.'),
                        TextInput::make('contact_whatsapp')
                            ->label('WhatsApp number')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('077 000 0000')
                            ->helperText('Can be the same as the phone number. Leave empty to hide the WhatsApp card.'),
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
            'contact_email' => trim((string) $data['contact_email']),
            'contact_phone' => trim((string) $data['contact_phone']),
            'contact_whatsapp' => trim((string) $data['contact_whatsapp']),
        ]);

        Notification::make()
            ->success()
            ->title('Contact details saved')
            ->body($this->linkPreview())
            ->send();
    }

    private function linkPreview(): string
    {
        // config() is still holding the pre-save values for this request.
        config([
            'kayaa.phone' => $this->data['contact_phone'] ?? '',
            'kayaa.whatsapp' => $this->data['contact_whatsapp'] ?? '',
        ]);

        return collect([StoreContact::phoneHref(), StoreContact::whatsappHref()])
            ->filter()
            ->implode(' · ') ?: 'No phone numbers set.';
    }
}
