<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $n = Order::where('status', 'pending')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ---------- global search ----------

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'phone', 'email', 'first_name', 'last_name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->reference.' · '.$record->customerName();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Total' => money($record->total),
            'Status' => ucfirst($record->status),
        ];
    }

    // ---------- view page ----------

    public static function infolist(Schema $schema): Schema
    {
        $dash = '—';

        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('reference')->label('Order'),
                TextEntry::make('status')->badge()->color(fn (string $state) => self::statusColor($state)),
                TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, H:i'),
                TextEntry::make('payment_method')->label('Payment')->formatStateUsing(fn ($state) => $state === 'cod' ? 'Cash on delivery' : 'Onepay'),
                TextEntry::make('payment_status')->badge()->color(fn (string $state) => self::paymentColor($state)),
                TextEntry::make('total')->formatStateUsing(fn ($state) => money((int) $state)),
            ]),

            Section::make('Customer')->columns(2)->schema([
                TextEntry::make('customer_name')->label('Name')->state(fn (Order $r) => $r->customerName()),
                TextEntry::make('phone')->copyable(),
                TextEntry::make('email')->placeholder($dash),
                TextEntry::make('full_address')->label('Address')
                    ->state(fn (Order $r) => "{$r->address}, {$r->city}, {$r->district}"),
                TextEntry::make('note')->label('Delivery note from customer')->placeholder($dash)->columnSpanFull(),
            ]),

            Section::make('Items')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(5)->schema([
                    TextEntry::make('product_name')->label('Product'),
                    TextEntry::make('variant_label')->label('Variant'),
                    TextEntry::make('sku')->label('SKU')->placeholder($dash),
                    TextEntry::make('qty')->label('Qty'),
                    TextEntry::make('line_total')->label('Total')->formatStateUsing(fn ($state) => money((int) $state)),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('subtotal')->formatStateUsing(fn ($state) => money((int) $state)),
                    TextEntry::make('shipping')->formatStateUsing(fn ($state) => money((int) $state)),
                    TextEntry::make('total')->formatStateUsing(fn ($state) => money((int) $state))->weight('bold'),
                ]),
            ]),

            Section::make('Fulfilment')->columns(3)->schema([
                TextEntry::make('courier')->placeholder('Not shipped yet'),
                TextEntry::make('tracking_number')->label('Tracking number')->placeholder($dash)->copyable(),
                TextEntry::make('shipped_email_sent_at')->label('Shipped email')
                    ->state(fn (Order $r) => $r->shipped_email_sent_at ? 'Sent '.$r->shipped_email_sent_at->format('d M, H:i') : ($r->email ? 'Not sent' : 'No email on order')),
                TextEntry::make('admin_note')->label('Internal note (staff only)')->placeholder('None — use “Add note” above.')->columnSpanFull(),
            ]),

            Section::make('Timeline')->columns(5)->schema([
                TextEntry::make('created_at')->label('Placed')->dateTime('d M, H:i'),
                TextEntry::make('paid_at')->label('Paid')->dateTime('d M, H:i')->placeholder($dash),
                TextEntry::make('shipped_at')->label('Shipped')->dateTime('d M, H:i')->placeholder($dash),
                TextEntry::make('delivered_at')->label('Delivered')->dateTime('d M, H:i')->placeholder($dash),
                TextEntry::make('cancelled_at')->label('Cancelled')->dateTime('d M, H:i')->placeholder($dash),
            ]),
        ]);
    }

    // ---------- list page ----------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->weight('bold')->copyable(),
                TextColumn::make('created_at')->label('Placed')->dateTime('d M, H:i')->sortable(),
                TextColumn::make('customer_name')->label('Customer')->state(fn (Order $r) => $r->customerName())
                    ->searchable(['first_name', 'last_name'])
                    ->description(fn (Order $r) => $r->district),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('total')->formatStateUsing(fn ($state) => money((int) $state))->sortable(),
                TextColumn::make('payment_method')->label('Pay')->formatStateUsing(fn ($s) => $s === 'cod' ? 'COD' : 'Onepay'),
                TextColumn::make('payment_status')->label('Payment')->badge()->color(fn (string $state) => self::paymentColor($state)),
                TextColumn::make('status')->badge()->color(fn (string $state) => self::statusColor($state)),
                TextColumn::make('tracking_number')->label('Tracking')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('payment_status')->label('Payment status')
                    ->options(array_combine(Order::PAYMENT_STATUSES, array_map('ucfirst', Order::PAYMENT_STATUSES))),
                SelectFilter::make('payment_method')->options(['cod' => 'COD', 'onepay' => 'Onepay']),
                SelectFilter::make('district')
                    ->options(fn () => array_combine(config('kayaa.districts'), config('kayaa.districts')))
                    ->searchable(),
                Filter::make('placed')
                    ->label('Placed between')
                    ->schema([
                        DatePicker::make('from')->label('Placed from'),
                        DatePicker::make('until')->label('Placed until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $out = [];
                        if ($data['from'] ?? null) {
                            $out[] = 'From '.date('d M Y', strtotime($data['from']));
                        }
                        if ($data['until'] ?? null) {
                            $out[] = 'Until '.date('d M Y', strtotime($data['until']));
                        }

                        return $out;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('whatsapp')->label('WhatsApp')->icon(Heroicon::ChatBubbleLeftEllipsis)->color('success')
                    ->visible(fn (Order $record) => $record->whatsappUrl() !== null)
                    ->url(fn (Order $record) => $record->whatsappUrl(), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm')->label('Confirm selected')->icon(Heroicon::Check)->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => self::bulkTransition($records, 'confirm'))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('ship')->label('Mark selected as shipped')->icon(Heroicon::Truck)
                        ->requiresConfirmation()
                        ->modalDescription('Customers with an email on their order are told it has shipped. Add courier and tracking numbers afterwards from each order if you need them.')
                        ->action(fn (Collection $records) => self::bulkTransition($records, 'ship'))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * Applies one transition to every selected order it makes sense for, and
     * says plainly how many were skipped rather than failing the lot.
     */
    protected static function bulkTransition(Collection $records, string $transition): void
    {
        $service = app(OrderService::class);
        $done = 0;

        foreach ($records as $order) {
            $done += $service->{$transition}($order) ? 1 : 0;
        }

        $skipped = $records->count() - $done;

        Notification::make()
            ->title($done.' '.str('order')->plural($done).' updated')
            ->body($skipped > 0 ? "{$skipped} skipped — not in a state this change applies to." : null)
            ->color($done > 0 ? 'success' : 'warning')
            ->send();
    }

    // ---------- single-order actions (header of the view page) ----------

    /** Status transitions shown as header actions on the view page. */
    public static function statusActions(): array
    {
        return [
            Action::make('confirm')->label('Confirm')->icon(Heroicon::Check)->color('success')
                ->visible(fn (Order $record) => $record->status === 'pending')
                ->action(fn (Order $record) => app(OrderService::class)->confirm($record)),

            Action::make('ship')->label('Mark shipped')->icon(Heroicon::Truck)
                ->visible(fn (Order $record) => in_array($record->status, ['pending', 'confirmed']))
                ->modalHeading('Mark as shipped')
                ->modalDescription(fn (Order $record) => $record->email
                    ? "{$record->email} will get a shipped email with these details."
                    : 'This order has no email address, so the customer will not be emailed.')
                ->schema([
                    TextInput::make('courier')->maxLength(60)->placeholder('Courier company'),
                    TextInput::make('tracking_number')->label('Tracking number')->maxLength(80),
                ])
                ->action(fn (Order $record, array $data) => app(OrderService::class)
                    ->ship($record, $data['courier'] ?? null, $data['tracking_number'] ?? null)),

            Action::make('deliver')->label('Mark delivered')->icon(Heroicon::CheckCircle)->color('success')
                ->visible(fn (Order $record) => $record->status === 'shipped')
                ->action(fn (Order $record) => app(OrderService::class)->deliver($record)),

            Action::make('paid')->label('Mark paid')->icon(Heroicon::Banknotes)
                ->visible(fn (Order $record) => $record->payment_status !== 'paid' && $record->status !== 'cancelled')
                ->requiresConfirmation()
                ->action(fn (Order $record) => $record->markPaid()),

            Action::make('cancel')->label('Cancel order')->icon(Heroicon::XMark)->color('danger')
                ->visible(fn (Order $record) => ! in_array($record->status, ['delivered', 'cancelled']))
                ->requiresConfirmation()
                ->modalDescription('Stock for each item will be returned to the shelf.')
                ->action(fn (Order $record) => app(OrderService::class)->cancel($record, 'Cancelled in the admin panel.')),
        ];
    }

    /** Utility actions that do not change the order's state. */
    public static function toolActions(): array
    {
        return [
            Action::make('packing_slip')->label('Print packing slip')->icon(Heroicon::Printer)->color('gray')
                ->url(fn (Order $record) => route('admin.orders.packing-slip', $record), shouldOpenInNewTab: true),

            Action::make('whatsapp')->label('WhatsApp')->icon(Heroicon::ChatBubbleLeftEllipsis)->color('gray')
                ->visible(fn (Order $record) => $record->whatsappUrl() !== null)
                ->url(fn (Order $record) => $record->whatsappUrl(), shouldOpenInNewTab: true),

            Action::make('note')->label('Add note')->icon(Heroicon::PencilSquare)->color('gray')
                ->modalHeading('Internal note')
                ->modalDescription('Only staff see this. The customer never does.')
                ->fillForm(fn (Order $record) => ['admin_note' => $record->admin_note])
                ->schema([Textarea::make('admin_note')->label('Note')->rows(4)->maxLength(2000)])
                ->action(fn (Order $record, array $data) => $record->update(['admin_note' => filled($data['admin_note'] ?? null) ? $data['admin_note'] : null])),
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

    public static function paymentColor(string $state): string
    {
        return match ($state) {
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'warning',
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
