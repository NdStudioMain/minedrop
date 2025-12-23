<?php

namespace App\Filament\Resources\PromocodesResource\Pages;

use App\Filament\Resources\PromocodesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromocodes extends ListRecords
{
    protected static string $resource = PromocodesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
