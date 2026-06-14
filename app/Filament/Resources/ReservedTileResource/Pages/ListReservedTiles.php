<?php

namespace App\Filament\Resources\ReservedTileResource\Pages;

use App\Filament\Resources\ReservedTileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReservedTiles extends ListRecords
{
    protected static string $resource = ReservedTileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
