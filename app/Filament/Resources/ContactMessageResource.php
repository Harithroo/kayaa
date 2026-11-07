<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Messages';

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::where('is_read', false)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('contact')->copyable(),
            TextEntry::make('order_reference')->label('Order')->placeholder('—'),
            TextEntry::make('created_at')->dateTime('d M Y, H:i'),
            TextEntry::make('message')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    IconColumn::make('is_read')->label('')->boolean()
                        ->trueIcon(Heroicon::OutlinedEnvelopeOpen)->falseIcon(Heroicon::Envelope)
                        ->grow(false),
                    Stack::make([
                        // Searches both columns, so folding `contact` into this
                        // card line does not lose the old contact search.
                        TextColumn::make('name')->searchable(['name', 'contact'])
                            ->weight('bold')->size(TextSize::Medium)
                            ->description(fn (ContactMessage $r) => $r->contact),
                        TextColumn::make('message')->limit(80)->wrap()
                            ->size(TextSize::Small)->color('gray'),
                        Split::make([
                            TextColumn::make('order_reference')
                                ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedHashtag)
                                ->placeholder('')
                                ->grow(false),
                            TextColumn::make('created_at')->dateTime('d M, H:i')->sortable()
                                ->size(TextSize::Small)->color('gray')->icon(Heroicon::OutlinedClock)
                                ->grow(false),
                        ]),
                    ])->space(1)->grow(),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $r) => $r->update(['is_read' => true])),
                Action::make('read')->label('Mark read')->visible(fn (ContactMessage $r) => ! $r->is_read)
                    ->action(fn (ContactMessage $r) => $r->update(['is_read' => true])),
                DeleteAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
        ];
    }
}
