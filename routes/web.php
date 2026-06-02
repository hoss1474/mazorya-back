
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});

// گوگل وب‌هوک - با لایه امنیتی هدر
Route::post('/google-calendar/webhook', function (Request $request) {

    // لایه امنیتی ۱: فقط اگر هدر مخصوص گوگل وجود داشت پردازش کن
    if (!$request->hasHeader('X-Goog-Channel-ID')) {
        return response('Unauthorized', 403);
    }

    // لایه امنیتی ۲: جلوگیری از اجرای همزمان (Race Condition)
    $lockKey = 'gc_sync_lock';
    if (Cache::has($lockKey)) {
        return response('Locked', 200);
    }

    // قفل کردن برای ۱۰ ثانیه
    Cache::put($lockKey, true, 10);

    // اجرای دستور همگام‌سازی
    Artisan::call('app:sync-google-calendar');

    return response('OK', 200);

})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);








Route::get('/project/{lang}/{slug}', function ($lang, $slug) {
    // چون گفتی فایل توی پوشه main-laravel هست:
    $path = base_path('project-details.html');

    if (File::exists($path)) {
        return response()->file($path);
    }

    // اگر باز هم پیدا نکرد، این مسیر مستقیم رو چک کن:
    $directPath = '/home/u573287159/domains/cardifygroup.com/api/main-laravel/project-details.html';

    if (File::exists($directPath)) {
        return response()->file($directPath);
    }

    return response("I am looking for the file here: " . $directPath . " <br> Please make sure the file is exactly there.", 404);
});

Route::get('/test-url', function() {
    return "Laravel is working!";
});
