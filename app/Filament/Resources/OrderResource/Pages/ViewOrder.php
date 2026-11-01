<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...OrderResource::statusActions(),
            ActionGroup::make(OrderResource::toolActions())
                ->label('More')
                ->icon(Heroicon::EllipsisVertical)
                ->button()
                ->color('gray'),
        ];
    }
}
