<?php

namespace App\Filament\Resources\ConversationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public  function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('sender')->colors(['primary' => 'visitor', 'success' => 'admin']),
                TextColumn::make('message')->wrap(),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
