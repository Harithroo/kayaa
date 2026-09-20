<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Keep approved_at in step with the toggle. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['approved_at'] = $data['is_approved'] ? ($this->record->approved_at ?? now()) : null;

        return $data;
    }
}
