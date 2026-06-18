<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\PostResource\Concerns\HandlesPostPush;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use HandlesPostPush;

    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractPushConfig($data);
    }

    protected function afterCreate(): void
    {
        $this->maybeSendPostPush($this->record);
    }
}
