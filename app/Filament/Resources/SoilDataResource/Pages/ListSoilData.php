<?php

namespace App\Filament\Resources\SoilDataResource\Pages;

use App\Filament\Resources\SoilDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoilData extends ListRecords
{
    protected static string $resource = SoilDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
