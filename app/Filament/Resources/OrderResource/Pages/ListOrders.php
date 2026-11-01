<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** One tab per stage of the job, each with a live count. */
    public function getTabs(): array
    {
        $tab = fn (string $label, ?string $status, string $color = 'gray') => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $status ? $query->where('status', $status) : $query)
            ->badge(Order::query()->when($status, fn ($q) => $q->where('status', $status))->count())
            ->badgeColor($color);

        return [
            'all' => $tab('All', null),
            'pending' => $tab('To confirm', 'pending', 'warning'),
            'confirmed' => $tab('To ship', 'confirmed', 'info'),
            'shipped' => $tab('Shipped', 'shipped', 'primary'),
            'delivered' => $tab('Delivered', 'delivered', 'success'),
            'cancelled' => $tab('Cancelled', 'cancelled', 'danger'),
        ];
    }
}
