<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\WaitingListController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Read-Only)
|--------------------------------------------------------------------------
*/



// ================= AUTH =================
Route::post('/register', [AuthController::class, 'register']);
//Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
//Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-otp', [AuthController::class, 'loginWithOtp']);
Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);


// روت‌های عمومی برای نمایش اطلاعات (بدون نیاز به لاگین)
Route::get('/projects', [ProjectController::class, 'index']);
// برای API
Route::get('/{lang}/projects/{slug}', [ProjectController::class, 'show']);


Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/{lang}/blogs/{slug}', [BlogController::class, 'show']);

Route::get('/Services', [ServicesController::class, 'index']);
Route::get('/{lang}/Services/{slug}', [ServicesController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Rate Limited Routes (Public Submissions)
|--------------------------------------------------------------------------
*/

// روت‌هایی که کاربر دیتا می‌فرستد (محدود شده به ۵ درخواست در دقیقه برای هر نفر)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/messages', [ContactController::class, 'store']);
    Route::post('/waiting-list', [WaitingListController::class, 'store']);
    Route::post('/chat/send', [ChatController::class, 'send']);
});

/*
|--------------------------------------------------------------------------
| Visitor Chat Routes
|--------------------------------------------------------------------------
*/

// مشاهده پیام‌ها توسط بازدیدکننده (نیاز به منطق چک کردن سشن در کنترلر دارد)
Route::get('/chat/messages-for-visitor/{conversation:uuid}', [ChatController::class, 'messagesForVisitor']);

/*
|--------------------------------------------------------------------------
| Webhooks
|--------------------------------------------------------------------------
*/

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| Protected Admin Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // اطلاعات کاربر لاگین شده
    Route::get('/user', fn (Request $request) => $request->user());

    // مدیریت پیام‌های تماس (فقط ادمین)
    Route::prefix('messages')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::get('/{id}', [ContactController::class, 'show']);
    });

    // مدیریت لیست انتظار (فقط ادمین)
    Route::prefix('waiting-list')->group(function () {
        Route::get('/', [WaitingListController::class, 'index']);
        Route::get('/{id}', [WaitingListController::class, 'show']);
        Route::delete('/{id}', [WaitingListController::class, 'destroy']);
    });

    // مدیریت چت‌ها (پنل ادمین)
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'conversations']);
        Route::get('/messages/{conversation:uuid}', [ChatController::class, 'messages']);
        Route::post('/reply/{conversation:uuid}', [ChatController::class, 'reply']);
    });
});
