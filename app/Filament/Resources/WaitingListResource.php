<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaitingListResource\Pages;
use App\Models\WaitingList;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WaitingListResource extends Resource
{
    protected static ?string $model = WaitingList::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'لیست انتظار';

    protected static ?string $modelLabel = 'ایمیل';

    protected static ?string $pluralLabel = 'لیست انتظار';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('email')
                ->label('ایمیل')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWaitingLists::route('/'),
            'create' => Pages\CreateWaitingList::route('/create'),
            'edit' => Pages\EditWaitingList::route('/{record}/edit'),
        ];
    }
}
