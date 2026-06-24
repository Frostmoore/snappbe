<?php

namespace App\Filament\Resources\OrgChartSectionResource\Pages;

use App\Filament\Resources\OrgChartSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrgChartSections extends ListRecords
{
    protected static string $resource = OrgChartSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
