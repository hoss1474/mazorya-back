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
use Filament\Schemas\Schema;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    // ==========================================
    // PAGE TITLE
    // ==========================================

    public function getTitle(): string
    {
        return 'مدیریت مشتری و پروژه‌ها';
    }

    // ==========================================
    // HEADER ACTIONS
    // ==========================================

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_invoice')
                ->label('خروجی فاکتور')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->tooltip('قابلیت خروجی PDF هنوز فعال نشده است')
                ->disabled(),
        ];
    }

    // ==========================================
    // EDIT FORM
    // ==========================================

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ==================================
                // CLIENT INFORMATION
                // ==================================

                TextInput::make('company_name')
                    ->label('نام شرکت')
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
                    ->label('موبایل')
                    ->tel()
                    ->required()
                    ->maxLength(11)
                    ->rule('regex:/^09[0-9]{9}$/'),

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
                    ->maxSize(3048)
                    ->columnSpanFull(),

                // ==================================
                // CLIENT PROJECTS
                // ==================================

                Repeater::make('projects')
                    ->label('پروژه‌ها')
                    ->relationship('projects')
                    ->schema([

                        TextInput::make('project_name')
                            ->label('نام پروژه')
                            ->required()
                            ->maxLength(255),

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
                            ->maxValue(100)
                            ->default(0),

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
                            ->minValue(0)
                            ->prefix('تومان'),

                        DateTimePicker::make('created_start')
                            ->label('شروع پروژه')
                            ->seconds(false),

                        DateTimePicker::make('created_end')
                            ->label('پایان پروژه')
                            ->seconds(false)
                            ->afterOrEqual('created_start'),

                        Textarea::make('description')
                            ->label('توضیحات')
                            ->rows(4)
                            ->columnSpanFull(),

                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addActionLabel('افزودن پروژه'),

            ])
            ->columns(2);
    }
}
