<?php

namespace App\Filament\Resources\AccessLevelResource\Pages;

use App\Filament\Resources\AccessLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccessLevel extends EditRecord
{
    protected static string $resource = AccessLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
