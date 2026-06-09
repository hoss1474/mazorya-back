<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientProjectResource\Pages;
use App\Models\ClientProject;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;


class ClientProjectResource extends Resource
{
    protected static ?string $model = ClientProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'پروژه‌ها';
    protected static ?string $pluralLabel = 'پروژه‌ها';
    protected static ?string $navigationGroup = 'مالی';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                TextInput::make('project_name')
                    ->label('نام پروژه')
                    ->readOnly(),

                Select::make('project_type')
                    ->label('نوع پروژه')
                    ->options([
                        'web' => 'سایت',
                        'app' => 'اپلیکیشن',
                        'seo' => 'سئو',
                        'graphic' => 'گرافیک',
                    ])
                    ->required(),

                TextInput::make('project_progress')
                    ->label('درصد پیشرفت')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),



                TextInput::make('amount')
                    ->label('مبلغ کل')
                    ->numeric()
                    ->prefix('تومان')
                    ->required(),

                DateTimePicker::make('created_start')
                    ->label('شروع'),

                DateTimePicker::make('created_end')
                    ->label('پایان'),

                Textarea::make('description')
                    ->label('توضیحات'),

                FileUpload::make('file_path')
                    ->label('تصویر فاکتور')
                    ->disk('api_public')
                    ->directory('file_path')
                    ->image()
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/gif'
                    ])
                    ->maxSize(1048),


                Repeater::make('payments')
                    ->relationship('payments')
                    ->label('اقساط')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان')
                            ->disabled(),

                        TextInput::make('amount')
                            ->label('مبلغ')
                            ->numeric()
                            ->prefix('تومان')
                            ->disabled(),

                        Select::make('status')
                            ->label('وضعیت')
                            ->options([
                                'pending' => 'در انتظار',
                                'paid' => 'پرداخت شده',
                            ]),

                        DateTimePicker::make('paid_at')
                            ->label('تاریخ پرداخت'),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_name')
                    ->label('نام پروژه')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.last_name')
                    ->label('مشتری')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('مبلغ کل')
                    ->prefix('تومان'),


                TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'در انتظار',
                        'processing' => 'در حال انجام',
                        'completed' => 'تکمیل شده',
                        'cancelled' => 'لغو شده',
                        default => $state,
                    })
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'در انتظار',
                        'processing' => 'در حال انجام',
                        'completed' => 'تکمیل شده',
                        'cancelled' => 'لغو شده',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientProjects::route('/'),
            'create' => Pages\CreateClientProject::route('/create'),
            'edit' => Pages\EditClientProject::route('/{record}/edit'),
        ];
    }
}
