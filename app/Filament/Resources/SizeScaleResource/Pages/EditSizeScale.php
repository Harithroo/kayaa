<?php

namespace App\Filament\Resources\SizeScaleResource\Pages;

use App\Filament\Resources\SizeScaleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSizeScale extends EditRecord
{
    protected static string $resource = SizeScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
