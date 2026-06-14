<?php

namespace App\Filament\Resources\ProvincialSectionResource\Pages;

use App\Filament\Resources\ProvincialSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvincialSection extends EditRecord
{
    protected static string $resource = ProvincialSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
