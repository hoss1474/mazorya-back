<?php

namespace App\Filament\Resources;

use App\Exports\UsersExport;
use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;

use BackedEnum;
use UnitEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

use Filament\Tables\Columns\TextColumn;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

use Maatwebsite\Excel\Facades\Excel;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-users';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $pluralModelLabel = 'کاربران';

    protected static string|UnitEnum|null $navigationGroup =
        'مدیریت کاربران';

    protected static ?string $slug = 'clients';

    protected static ?string $modelLabel = 'کاربر';

    // ==========================================
    // FORM
    // ==========================================

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('company_name')
                    ->label('پروژه')
                    ->maxLength(255),

                TextInput::make('website')
                    ->label('وبسایت')
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label('نام')
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('phone')
                    ->label('شماره موبایل')
                    ->tel()
                    ->required()
                    ->maxLength(11)
                    ->rule('regex:/^09[0-9]{9}$/'),

                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->dehydrated(
                        fn (?string $state): bool =>
                        filled($state)
                    )
                    ->dehydrateStateUsing(
                        fn (string $state): string =>
                        Hash::make($state)
                    )
                    ->required(
                        fn (string $operation): bool =>
                            $operation === 'create'
                    )
                    ->maxLength(255),

                FileUpload::make('avatar')
                    ->label('تصویر پروفایل')
                    ->disk('api_public')
                    ->directory('user-profiles')
                    ->image()
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                    ])
                    ->maxSize(3048),

            ]);
    }

    // ==========================================
    // TABLE
    // ==========================================

    public static function table(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('company_name')
                    ->label('پروژه')
                    ->sortable()
                    ->searchable()
                    ->url(
                        fn (Client $record): string =>
                        static::getUrl('edit', [
                            'record' => $record,
                        ])
                    )
                    ->color('info'),

                TextColumn::make('last_name')
                    ->label('نام')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->label('ایمیل')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('website')
                    ->label('وبسایت')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('شماره موبایل')
                    ->sortable()
                    ->searchable(),

            ])

            ->defaultSort('id', 'desc')

            ->filters([])

            // ==================================
            // RECORD ACTIONS
            // ==================================

            ->recordActions([

                EditAction::make()
                    ->label('ویرایش')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),

            ])

            // ==================================
            // BULK ACTIONS
            // ==================================

            ->toolbarActions([

                BulkActionGroup::make([

                    BulkAction::make('export_selected')
                        ->label('خروجی Excel کاربران انتخاب‌شده')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(
                            function (Collection $records) {

                                $ids = $records
                                    ->pluck('id')
                                    ->toArray();

                                return Excel::download(
                                    new UsersExport($ids),
                                    'users_selected.xlsx'
                                );
                            }
                        ),

                    DeleteBulkAction::make()
                        ->label('حذف کاربران انتخاب‌شده')
                        ->requiresConfirmation(),

                ]),

            ])

            // ==================================
            // HEADER ACTIONS
            // ==================================

            ->headerActions([

                Action::make('export_all')
                    ->label('خروجی Excel همه کاربران')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(
                        function () {

                            return Excel::download(
                                new UsersExport('all'),
                                'users_all.xlsx'
                            );
                        }
                    ),

            ]);
    }

    // ==========================================
    // PAGES
    // ==========================================

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListClients::route('/'),

            'create' => Pages\CreateClient::route('/create'),

            'edit' => Pages\EditClient::route('/{record}/edit'),

        ];
    }
}
