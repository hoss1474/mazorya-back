<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaitingListResource\Pages;
use App\Models\WaitingList;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class WaitingListResource extends Resource
{
    protected static ?string $model = WaitingList::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'لیست انتظار';
    protected static ?string $modelLabel = 'ایمیل';
    protected static ?string $pluralLabel = 'لیست انتظار';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
