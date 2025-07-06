<?php

namespace App\Filament\Resources\SoilDataResource\Pages;

use App\Filament\Resources\SoilDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoilData extends EditRecord
{
    protected static string $resource = SoilDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
