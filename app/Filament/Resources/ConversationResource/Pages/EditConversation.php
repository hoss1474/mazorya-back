<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConversation extends EditRecord
{
    protected static string $resource = ConversationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}
