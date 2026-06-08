<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\WaitingListController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\ClientProjectController;
use App\Http\Controllers\Api\ClientMessageController;
use App\Http\Controllers\Api\ClientProjectPaymentController;
use App\Http\Controllers\Api\ClientInvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Read-Only)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [ClientApiController::class, 'login']);
Route::post('/auth/register', [ClientApiController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Protected Routes (JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::post('/auth/logout', [ClientApiController::class, 'logout']);
    Route::post('/auth/refresh', [ClientApiController::class, 'refresh']);

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'changePassword']);
    Route::post('/user/avatar', [ProfileController::class, 'uploadAvatar']);

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */
    Route::get('/user/projects', [ClientProjectController::class, 'index']);
    Route::get('/user/projects/{id}', [ClientProjectController::class, 'show']);


    /*
    |--------------------------------------------------------------------------
    | Payments (Project Installments)
    |--------------------------------------------------------------------------
    */
    Route::get('/user/projects/{id}/payments', [ClientProjectPaymentController::class, 'index']);
    Route::get('/user/projects/{projectId}/payments/{paymentId}', [ClientProjectPaymentController::class, 'show']);


    /*
    |--------------------------------------------------------------------------
    | Messages (Chat / Ticket)
    |--------------------------------------------------------------------------
    */
    Route::get('/user/messages', [ClientMessageController::class, 'index']);
    Route::post('/user/messages', [ClientMessageController::class, 'store']);
    Route::patch('/user/messages/{id}/read', [ClientMessageController::class, 'markAsRead']);


    /*
    |--------------------------------------------------------------------------
    | Invoices
    |--------------------------------------------------------------------------
    */
    Route::get('/user/invoices', [ClientInvoiceController::class, 'index']);
    Route::post('/user/invoices/upload', [ClientInvoiceController::class, 'upload']);
});


Route::post('/forgot-password', [ClientApiController::class, 'forgotPassword']);
Route::post('/reset-password', [ClientApiController::class, 'resetPassword']);

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

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

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
