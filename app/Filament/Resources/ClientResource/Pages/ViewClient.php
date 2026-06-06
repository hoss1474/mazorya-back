<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'مشاهده فاکتور سفارش';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('اطلاعات مشتری')
                    ->schema([
                        TextEntry::make('company_name')
                            ->label('نام پروژه')
                            ->size(TextEntrySize::Large),

                        TextEntry::make('full_name')
                            ->label('نام کاربر'),

                        TextEntry::make('email')
                            ->label('ایمیل'),

                        TextEntry::make('website')
                            ->label('وبسایت'),

                        TextEntry::make('phone')
                            ->label('موبایل'),
                    ])->columns(3),

                Section::make('اطلاعات پروژه')
                    ->schema([
                        TextEntry::make('created_start')
                            ->label('تاریخ استارت سفارش')
                            ->dateTime('Y/m/d H:i'),

                        TextEntry::make('created_end')
                            ->label('تاریخ پایان سفارش')
                            ->dateTime('Y/m/d H:i'),

                        TextEntry::make('status')
                            ->label('درصد پیشرفت سفارش')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                '0' => 'gray',
                                '25' => 'warning',
                                '50' => 'info',
                                '75' => 'primary',
                                '100' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state . '%'),

                        TextEntry::make('amount')
                            ->label('مبلغ کل')
                            ->money('IRR')
                            ->formatStateUsing(fn ($state) => number_format($state) . ' تومان'),

                        TextEntry::make('amount_status_1')
                            ->label('پیش پرداخت')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state === 'paid' ? 'پرداخت شده' : 'در انتظار'),

                        TextEntry::make('amount_status_2')
                            ->label('قسط دوم')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state === 'paid' ? 'پرداخت شده' : 'در انتظار'),

                        TextEntry::make('amount_status_3')
                            ->label('قسط سوم')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state === 'paid' ? 'پرداخت شده' : 'در انتظار'),

                        TextEntry::make('amount_status_4')
                            ->label('قسط چهارم')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => $state === 'paid' ? 'پرداخت شده' : 'در انتظار'),
                    ])->columns(4),

                Section::make('پروژه')
                    ->schema([
                        TextEntry::make('project_name')
                            ->label('نام پروژه'),

                        TextEntry::make('project_type')
                            ->label('نوع پروژه')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'web' => 'طراحی سایت',
                                'app' => 'اپلیکیشن موبایل',
                                'seo' => 'سئو',
                                'graphic' => 'طراحی گرافیک',
                                default => '-',
                            }),

                        TextEntry::make('project_progress')
                            ->label('درصد پیشرفت پروژه')
                            ->suffix('%')
                            ->progressBar()
                            ->color('success'),
                    ])->columns(3),

                Section::make('توضیحات')
                    ->schema([
                        TextEntry::make('city')
                            ->label('شهر'),

                        TextEntry::make('description')
                            ->label('توضیحات تکمیلی')
                            ->columnSpanFull()
                            ->html()
                            ->limit(500),
                    ])->columns(2),
            ]);
    }
}
