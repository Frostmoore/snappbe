<?php

namespace App\Filament\Resources\ProvincialSectionResource\Pages;

use App\Filament\Resources\ProvincialSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvincialSections extends ListRecords
{
    protected static string $resource = ProvincialSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
