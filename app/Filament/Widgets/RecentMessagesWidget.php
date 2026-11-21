<?php

namespace App\Filament\Widgets;

use App\Models\Message;
use App\Models\Service;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecentMessagesWidget extends BaseWidget
{


    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $unreadCount = Message::query()
            ->where('sender', 'visitor')
            ->where('is_read', false)
            ->count();

        return [
            Stat::make('پیام‌های جدید چت', $unreadCount)
                ->description('تعداد کل پیام‌های پاسخ داده نشده')
                ->descriptionIcon(
                    $unreadCount > 0
                        ? 'heroicon-m-chat-bubble-left-right'
                        : 'heroicon-m-check-circle'
                )
                ->color($unreadCount > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $unreadCount > 0 ? 'animate-pulse' : '',
                ]),

            Stat::make('تعداد سرویس‌ها', Service::count())
                ->description('سرویس‌های فعال')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('تعداد پروژه‌ها', Project::count())
                ->description('پروژه‌های فعال')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),
        ];
    }
}
