<?php

namespace App\Filament\Resources\PromocodesResource\Pages;

use App\Filament\Resources\PromocodesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromocodes extends EditRecord
{
    protected static string $resource = PromocodesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
