<?php

namespace App\Filament\Resources\SizeScaleResource\Pages;

use App\Filament\Resources\SizeScaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSizeScales extends ListRecords
{
    protected static string $resource = SizeScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
