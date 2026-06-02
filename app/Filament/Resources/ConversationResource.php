<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn; // اضافه شدن TextColumn

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    // اصلاح آیکون به نسخه ۳
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'گفتگوها';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('visitor.name')
                    ->label('مشتری')
                    ->description(fn ($record) => $record->visitor?->email) // استفاده از null-safe برای جلوگیری از ارور
                    ->searchable(),

                // اصلاح BadgeColumn به TextColumn با متد badge()
                TextColumn::make('unread_messages_count')
                    ->label('پیام جدید')
                    ->badge() // 👈 در فیلامنت ۳ این جایگزین BadgeColumn شده
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

                // اصلاح ستون وضعیت
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'باز',
                        'closed' => 'بسته',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('updated_at')
                    ->label('آخرین فعالیت')
                    ->dateTime('H:i')
                    ->description(fn ($record) => $record->updated_at->diffForHumans())
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('فیلتر وضعیت')
                    ->options([
                        'open' => 'فقط بازها',
                        'closed' => 'فقط بسته‌ها',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مشاهده'),

                Tables\Actions\Action::make('close_conversation')
                    ->label('بستن چت')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'open')
                    ->action(function ($record) {
                        $record->update(['status' => 'closed']);

                        \Filament\Notifications\Notification::make()
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
            'view'  => Pages\ViewConversation::route('/{record}'), // مسیر استاندارد فیلامنت ۳
        ];
    }
}
