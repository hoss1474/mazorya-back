<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'مدیریت مشتری و پروژه‌ها';
    }

    /*
    |--------------------------------------------------------------------------
    | Header Action
    |--------------------------------------------------------------------------
    */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_invoice')
                ->label('خروجی فاکتور')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->action(function () {
                    // PDF later
                }),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */
    public function form(Form $form): Form
    {
        return $form->schema([

            /*
            |--------------------------------------------------------------------------
            | اطلاعات مشتری
            |--------------------------------------------------------------------------
            */
            TextInput::make('company_name')
                ->label('نام شرکت'),

            TextInput::make('full_name')
                ->label('نام'),

            TextInput::make('email')
                ->label('ایمیل'),

            TextInput::make('phone')
                ->label('موبایل'),

            FileUpload::make('avatar')
                ->label('تصویر پروفایل')
                ->disk('api_public')
                ->directory('user-profiles')
                ->image()
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/jpg',
                    'image/gif'
                ])
                ->maxSize(3048),


            /*
            |--------------------------------------------------------------------------
            | پروژه‌ها
            |--------------------------------------------------------------------------
            */
            Repeater::make('projects')
                ->relationship('projects')
                ->label('پروژه‌ها')
                ->schema([

                    TextInput::make('project_name')
                        ->label('نام پروژه')
                        ->required(),

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

                    Select::make('status')
                        ->label('وضعیت پروژه')
                        ->options([
                            'pending' => 'در انتظار',
                            'processing' => 'در حال انجام',
                            'completed' => 'تکمیل شده',
                            'cancelled' => 'لغو شده',
                        ])
                        ->default('pending'),

                    TextInput::make('amount')
                        ->label('مبلغ کل')
                        ->numeric()
                        ->prefix('تومان'),

                    DateTimePicker::make('created_start')
                        ->label('شروع'),

                    DateTimePicker::make('created_end')
                        ->label('پایان'),

                    Textarea::make('description')
                        ->label('توضیحات'),



                ])
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('افزودن پروژه'),
        ]);
    }

}
