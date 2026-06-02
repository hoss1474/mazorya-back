<?php

namespace App\Providers;

use App\Filament\Widgets\PanelUsers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Filament\Facades\Filament;
use App\Filament\Widgets\EventsCalendarWidget;
use App\Filament\Widgets\RecentMessagesWidget;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {

    }
}
