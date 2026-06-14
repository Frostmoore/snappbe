<?php

namespace App\Filament\Resources\ReservedSectionResource\Pages;

use App\Filament\Resources\ReservedSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReservedSections extends ListRecords
{
    protected static string $resource = ReservedSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
