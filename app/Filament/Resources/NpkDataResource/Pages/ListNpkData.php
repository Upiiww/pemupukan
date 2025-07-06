<?php

namespace App\Filament\Resources\NpkDataResource\Pages;

use App\Filament\Resources\NpkDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNpkData extends ListRecords
{
    protected static string $resource = NpkDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
