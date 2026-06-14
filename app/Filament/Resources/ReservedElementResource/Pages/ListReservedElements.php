<?php

namespace App\Filament\Resources\ReservedElementResource\Pages;

use App\Filament\Resources\ReservedElementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReservedElements extends ListRecords
{
    protected static string $resource = ReservedElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
