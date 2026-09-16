<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $n = Order::where('status', 'pending')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('reference')->label('Order'),
                TextEntry::make('status')->badge()->color(fn (string $state) => self::statusColor($state)),
                TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, H:i'),
                TextEntry::make('payment_method')->label('Payment')->formatStateUsing(fn ($state) => $state === 'cod' ? 'Cash on delivery' : 'PayHere'),
                TextEntry::make('payment_status')->badge()->color(fn (string $state) => match ($state) {
                    'paid' => 'success', 'failed' => 'danger', 'refunded' => 'warning', default => 'gray',
                }),
                TextEntry::make('total')->money('LKR', divideBy: 100),
            ]),

            Section::make('Customer')->columns(2)->schema([
                TextEntry::make('customer_name')->label('Name')->state(fn (Order $r) => $r->customerName()),
                TextEntry::make('phone')->copyable(),
                TextEntry::make('email')->placeholder('—'),
                TextEntry::make('full_address')->label('Address')
                    ->state(fn (Order $r) => "{$r->address}, {$r->city}, {$r->district}"),
                TextEntry::make('note')->label('Delivery note')->placeholder('—')->columnSpanFull(),
            ]),

            Section::make('Items')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('product_name')->label('Product'),
                    TextEntry::make('variant_label')->label('Variant'),
                    TextEntry::make('qty')->label('Qty'),
                    TextEntry::make('line_total')->label('Total')->money('LKR', divideBy: 100),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('subtotal')->money('LKR', divideBy: 100),
                    TextEntry::make('shipping')->money('LKR', divideBy: 100),
                    TextEntry::make('total')->money('LKR', divideBy: 100)->weight('bold'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->weight('bold'),
                TextColumn::make('created_at')->label('Placed')->dateTime('d M, H:i')->sortable(),
                TextColumn::make('customer_name')->label('Customer')->state(fn (Order $r) => $r->customerName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('district'),
                TextColumn::make('total')->money('LKR', divideBy: 100)->sortable(),
                TextColumn::make('payment_method')->label('Pay')->formatStateUsing(fn ($s) => $s === 'cod' ? 'COD' : 'PayHere'),
                TextColumn::make('payment_status')->badge()->color(fn (string $state) => $state === 'paid' ? 'success' : 'gray'),
                TextColumn::make('status')->badge()->color(fn (string $state) => self::statusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(array_combine(Order::STATUSES, array_map('ucfirst', Order::STATUSES))),
                SelectFilter::make('payment_method')->options(['cod' => 'COD', 'payhere' => 'PayHere']),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /** Status transitions shown as header actions on the view page. */
    public static function statusActions(): array
    {
        return [
            Action::make('confirm')->label('Confirm')->icon(Heroicon::Check)->color('success')
                ->visible(fn (Order $r) => $r->status === 'pending')
                ->action(fn (Order $r) => $r->update(['status' => 'confirmed'])),
            Action::make('ship')->label('Mark shipped')->icon(Heroicon::Truck)
                ->visible(fn (Order $r) => in_array($r->status, ['pending', 'confirmed']))
                ->action(fn (Order $r) => $r->update(['status' => 'shipped', 'shipped_at' => now()])),
            Action::make('deliver')->label('Mark delivered')->icon(Heroicon::CheckCircle)->color('success')
                ->visible(fn (Order $r) => $r->status === 'shipped')
                ->action(function (Order $r) {
                    $r->update(['status' => 'delivered']);
                    if ($r->payment_method === 'cod' && $r->payment_status !== 'paid') {
                        $r->markPaid();
                    }
                }),
            Action::make('paid')->label('Mark paid')->icon(Heroicon::Banknotes)
                ->visible(fn (Order $r) => $r->payment_status !== 'paid' && $r->status !== 'cancelled')
                ->requiresConfirmation()
                ->action(fn (Order $r) => $r->markPaid()),
            Action::make('cancel')->label('Cancel order')->icon(Heroicon::XMark)->color('danger')
                ->visible(fn (Order $r) => ! in_array($r->status, ['delivered', 'cancelled']))
                ->requiresConfirmation()
                ->modalDescription('Stock for each item will be returned to the shelf.')
                ->action(function (Order $r) {
                    $r->loadMissing('items.variant');
                    foreach ($r->items as $item) {
                        $item->variant?->increment('stock', $item->qty);
                    }
                    $r->update(['status' => 'cancelled']);
                }),
        ];
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
