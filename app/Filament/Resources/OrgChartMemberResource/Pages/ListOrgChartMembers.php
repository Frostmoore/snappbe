<?php

namespace App\Filament\Resources\OrgChartMemberResource\Pages;

use App\Filament\Resources\OrgChartMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrgChartMembers extends ListRecords
{
    protected static string $resource = OrgChartMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
