<?php

namespace App\Filament\Resources\OrgChartSectionResource\Pages;

use App\Filament\Resources\OrgChartSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrgChartSection extends EditRecord
{
    protected static string $resource = OrgChartSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
