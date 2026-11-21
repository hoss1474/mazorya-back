<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'گفتگوها';

    protected static ?string $modelLabel = 'گفتگو';

    protected static ?string $pluralModelLabel = 'گفتگوها';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('visitor.name')
                    ->label('مشتری')
                    ->description(fn ($record) => $record->visitor?->email)
                    ->searchable(),

                TextColumn::make('unread_messages_count')
                    ->label('پیام جدید')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->messages()
                            ->where('sender', 'visitor')
                            ->where('is_read', false)
                            ->count();
                    })
                    ->color(fn ($state): string => match (true) {
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'open' => 'باز',
                        'closed' => 'بسته',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('آخرین فعالیت')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->updated_at?->diffForHumans())
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('فیلتر وضعیت')
                    ->options([
                        'open' => 'فقط بازها',
                        'closed' => 'فقط بسته‌ها',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده'),

                Action::make('close_conversation')
                    ->label('بستن چت')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('بستن گفتگو')
                    ->modalDescription('آیا از بستن این گفتگو اطمینان دارید؟')
                    ->visible(fn ($record) => $record->status === 'open')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'closed',
                        ]);

                        Notification::make()
                            ->title('گفتگو بسته شد')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'view' => Pages\ViewConversation::route('/{record}'),
        ];
    }
}
