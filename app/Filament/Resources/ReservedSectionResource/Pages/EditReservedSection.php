<?php

namespace App\Filament\Resources\ReservedSectionResource\Pages;

use App\Filament\Resources\ReservedSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReservedSection extends EditRecord
{
    protected static string $resource = ReservedSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
