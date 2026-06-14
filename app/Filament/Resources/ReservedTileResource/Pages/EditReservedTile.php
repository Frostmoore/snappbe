<?php

namespace App\Filament\Resources\ReservedTileResource\Pages;

use App\Filament\Resources\ReservedTileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReservedTile extends EditRecord
{
    protected static string $resource = ReservedTileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
