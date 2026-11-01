<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A product customers have ordered keeps its history: no delete, archive instead.
            DeleteAction::make()
                ->visible(fn (Product $record) => ! $record->hasOrders()),

            Action::make('archive')->label('Archive (has orders)')->icon(Heroicon::ArchiveBox)->color('warning')
                ->visible(fn (Product $record) => $record->hasOrders() && $record->status !== 'archived')
                ->requiresConfirmation()
                ->modalDescription('Customers have ordered this product, so it cannot be deleted. Archiving hides it from the shop and keeps the order history intact.')
                ->action(function (Product $record) {
                    $record->update(['status' => 'archived']);
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
