<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\PostResource\Concerns\HandlesPostPush;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use HandlesPostPush;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractPushConfig($data);
    }

    protected function afterSave(): void
    {
        $this->maybeSendPostPush($this->record);
    }
}
