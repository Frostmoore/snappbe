<?php

namespace App\Filament\Resources\ReservedElementResource\Pages;

use App\Filament\Resources\ReservedElementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReservedElement extends EditRecord
{
    protected static string $resource = ReservedElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
