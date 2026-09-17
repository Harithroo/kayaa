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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?int $navigationSort = 6;

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
                IconColumn::make('is_read')->label('')->boolean()->trueIcon(Heroicon::OutlinedEnvelopeOpen)->falseIcon(Heroicon::Envelope),
                TextColumn::make('created_at')->dateTime('d M, H:i')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('contact')->searchable(),
                TextColumn::make('order_reference')->placeholder('—'),
                TextColumn::make('message')->limit(60),
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
