<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateConversation extends CreateRecord
{
    protected static string $resource = ConversationResource::class;
}
