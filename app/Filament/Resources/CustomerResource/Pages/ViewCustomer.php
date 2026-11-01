<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Same rule as sign-in: the guest order's email AND phone must match.
            Action::make('link_guest_orders')->label('Link guest orders')->icon(Heroicon::Link)
                ->badge(fn (User $record) => (string) $record->claimableGuestOrders()->count())
                ->visible(fn (User $record) => $record->claimableGuestOrders()->exists())
                ->requiresConfirmation()
                ->modalHeading('Link guest orders')
                ->modalDescription(function (User $record) {
                    $n = $record->claimableGuestOrders()->count();

                    return "{$n} guest ".str('order')->plural($n)." placed with {$record->email} and {$record->phone} "
                        .($n === 1 ? 'is' : 'are').' not linked to any account. Linking adds them to this customer\'s history and totals.';
                })
                ->action(function (User $record) {
                    $n = $record->claimGuestOrders();

                    Notification::make()->success()
                        ->title("{$n} ".str('order')->plural($n).' linked')
                        ->send();

                    // The totals on this page were computed before the link.
                    $this->redirect(CustomerResource::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}
