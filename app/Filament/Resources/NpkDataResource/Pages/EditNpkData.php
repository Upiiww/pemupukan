<?php

namespace App\Filament\Resources\NpkDataResource\Pages;

use App\Filament\Resources\NpkDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNpkData extends EditRecord
{
    protected static string $resource = NpkDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
