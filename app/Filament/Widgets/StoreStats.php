<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ReviewResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Review;
use App\Support\SalesReport;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The four numbers the shop owner wants on opening the admin. */
class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = SalesReport::today();
        $yesterday = SalesReport::yesterday();
        $month = SalesReport::thisMonth();
        $week = collect(SalesReport::daily(7))->pluck('revenue')->map(fn ($c) => (int) ($c / 100))->all();

        $toConfirm = Order::where('status', 'pending')->count();
        $toShip = Order::where('status', 'confirmed')->count();
        $reviews = Review::where('is_approved', false)->count();
        $messages = ContactMessage::where('is_read', false)->count();

        $change = SalesReport::change($today['revenue'], $yesterday['revenue']);

        return [
            Stat::make('Sales today', money($today['revenue']))
                ->description(trim($today['orders'].' '.str('order')->plural($today['orders']).($change ? " · {$change} vs yesterday" : '')))
                ->descriptionIcon($change && str_starts_with($change, '-') ? Heroicon::ArrowTrendingDown : Heroicon::ArrowTrendingUp)
                ->color($change && str_starts_with($change, '-') ? 'danger' : 'success')
                ->chart($week),

            Stat::make('Sales this month', money($month['revenue']))
                ->description($month['orders'].' '.str('order')->plural($month['orders']).' since the 1st')
                ->descriptionIcon(Heroicon::CalendarDays)
                ->color('primary'),

            Stat::make('Orders to action', $toConfirm + $toShip)
                ->description("{$toConfirm} to confirm · {$toShip} to ship")
                ->descriptionIcon(Heroicon::ClipboardDocumentList)
                ->color(($toConfirm + $toShip) > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Waiting on you', $reviews + $messages)
                ->description("{$reviews} ".str('review')->plural($reviews)." to approve · {$messages} unread ".str('message')->plural($messages))
                ->descriptionIcon(Heroicon::ChatBubbleLeftRight)
                ->color(($reviews + $messages) > 0 ? 'warning' : 'gray')
                ->url($messages > 0 || $reviews === 0 ? ContactMessageResource::getUrl('index') : ReviewResource::getUrl('index')),
        ];
    }
}
