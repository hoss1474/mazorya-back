<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'ویرایش فاکتور سفارش';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_invoice')
                ->label('خروجی فاکتور')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->action(function () {
                    // کد تولید PDF
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->columns(2);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('اطلاعات مشتری')
                ->schema([
                    Placeholder::make('company_name')
                        ->label('پروژه')
                        ->content(fn ($record) => $record?->company_name ?? '-'),  // ← مستقیماً از record

                    Placeholder::make('client_name')
                        ->label('نام کاربر')
                        ->content(fn ($record) => $record?->full_name ?? '-'),  // ← مستقیماً از record

                    Placeholder::make('client_email')
                        ->label('ایمیل')
                        ->content(fn ($record) => $record?->email ?? '-'),  // ← مستقیماً از record

                    Placeholder::make('website')
                        ->label('وبسایت')
                        ->content(fn ($record) => $record?->website ?? '-'),  // ← مستقیماً از record

                    Placeholder::make('client_phone')
                        ->label('موبایل')
                        ->content(fn ($record) => $record?->phone ?? '-'),  // ← مستقیماً از record
                ])->columns(3),

            Section::make('اطلاعات پروژه')
                ->schema([
                    DateTimePicker::make('created_start')
                        ->label('تاریخ استارت پروژه')
                        ->displayFormat('Y/m/d H:i')
                        ->jalali()
                        ->required(),

                    DateTimePicker::make('created_end')
                        ->label('تاریخ پایان سفارش')
                        ->displayFormat('Y/m/d H:i')
                        ->jalali(),

                    ToggleButtons::make('status')
                        ->label('درصد پیشرفت سفارش')
                        ->options([
                            '0' => '۰%',
                            '25' => '۲۵%',
                            '50' => '۵۰%',
                            '75' => '۷۵%',
                            '100' => '۱۰۰%',
                        ])
                        ->colors([
                            '0' => 'gray',
                            '25' => 'warning',
                            '50' => 'info',
                            '75' => 'primary',
                            '100' => 'success',
                        ])
                        ->default('0')
                        ->inline()
                        ->grouped()
                        ->required(),

                    TextInput::make('amount')
                        ->label('مبلغ کل')
                        ->numeric()
                        ->prefix('تومان')
                        ->required(),

                    Select::make('amount_status_1')
                        ->label('پیش پرداخت')
                        ->options([
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                        ])
                        ->default('pending')
                        ->native(false),

                    Select::make('amount_status_2')
                        ->label('قسط دوم')
                        ->options([
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                        ])
                        ->default('pending')
                        ->native(false),

                    Select::make('amount_status_3')
                        ->label('قسط سوم')
                        ->options([
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                        ])
                        ->default('pending')
                        ->native(false),

                    Select::make('amount_status_4')
                        ->label('قسط چهارم')
                        ->options([
                            'pending' => 'در انتظار',
                            'paid' => 'پرداخت شده',
                        ])
                        ->default('pending')
                        ->native(false),
                ])->columns(4),

            Section::make('پروژه')
                ->schema([
                    TextInput::make('company_name')
                        ->label('نام پروژه')
                        ->required(),

                    Select::make('project_type')
                        ->label('نوع پروژه')
                        ->options([
                            'web' => 'طراحی سایت',
                            'app' => 'اپلیکیشن موبایل',
                            'seo' => 'سئو',
                            'graphic' => 'طراحی گرافیک',
                        ])
                        ->required(),

                    TextInput::make('project_progress')
                        ->label('درصد پیشرفت پروژه')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->required(),
                ])->columns(3),

            Section::make('توضیحات')
                ->schema([
                    Textarea::make('description')
                        ->label('توضیحات تکمیلی')
                        ->rows(3),
                ])->columns(1),
        ];
    }
}
