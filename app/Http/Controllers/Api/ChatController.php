<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ✅ Events & Jobs
use App\Events\MessageSent;
use App\Jobs\SendMessageToTelegramJob;
use App\Jobs\CreateTelegramTopicJob;

class ChatController extends Controller
{
    /**
     * 1️⃣ ارسال پیام از سمت Visitor
     * امن شده در برابر اسپم و حملات تزریق کد
     */
    public function send(Request $request)
    {
        // ۱. اعتبار سنجی سخت‌گیرانه
        $request->validate([
            'message' => 'required|string|max:5000',
            'name'    => 'nullable|string|max:100',
            'email'   => 'required|email|max:255'
        ]);

        try {
            return DB::transaction(function () use ($request) {

                // ۲. فیلتر کردن متن پیام برای جلوگیری از XSS
                $cleanMessage = strip_tags($request->message);

                // ۳. پیدا کردن یا ساخت ویزیتور
                $visitor = Visitor::firstOrCreate(
                    ['email' => $request->email],
                    [
                        'name'       => strip_tags($request->name),
                        'ip'         => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                );

                // ۴. مدیریت مکالمه (پیدا کردن آخرین یا ساخت جدید)
                $conversation = Conversation::where('visitor_id', $visitor->id)
                    ->latest()
                    ->first();

                if (!$conversation || $conversation->status === 'closed') {
                    $conversation = Conversation::create([
                        'visitor_id' => $visitor->id,
                        'status'     => 'open',
                        'page_url'   => strip_tags($request->headers->get('referer', 'unknown'))
                    ]);
                } else {
                    $conversation->touch();
                }

                // ۵. قفل کردن دسترسی به این UUID در سشن این کاربر (امنیت ورژن ۴)
                session(['chat_uuid' => $conversation->uuid]);

                // ۶. ساخت تاپیک تلگرام (اگر وجود ندارد)
                if (!$conversation->telegram_thread_id) {
                    CreateTelegramTopicJob::dispatch($conversation, $visitor);
                }

                // ۷. ذخیره پیام
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'visitor_id'      => $visitor->id,
                    'sender'          => 'visitor',
                    'message'         => $cleanMessage
                ]);

                // ۸. ارسال نوتیفیکیشن دیتابیس به ادمین‌های فیلامنت
                $this->notifyAdmins($conversation, $visitor, $cleanMessage);

                // ۹. اطلاع‌رسانی Real-time و تلگرام
                event(new MessageSent($message));
                SendMessageToTelegramJob::dispatch($conversation, $visitor, $message);

                return response()->json([
                    'ok'   => true,
                    'uuid' => $conversation->uuid,
                    'message_id' => $message->id
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Chat Send Error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'خطای سرور'], 500);
        }
    }

    /**
     * 2️⃣ دریافت پیام‌ها برای Visitor
     * لایه امنیتی: چک کردن تطابق UUID با Session
     */
    public function messagesForVisitor($uuid)
    {
        // لایه امنیتی اصلی: جلوگیری از فضولی در چت دیگران
        if (session('chat_uuid') !== $uuid) {
            return response()->json(['message' => 'Unauthorized!'], 403);
        }

        $conversation = Conversation::where('uuid', $uuid)->firstOrFail();

        $messages = $conversation->messages()
            ->select('id', 'sender', 'message', 'created_at')
            ->oldest() // نمایش از قدیم به جدید برای UI چت
            ->get();

        return response()->json($messages);
    }

    /**
     * 3️⃣ پاسخ ادمین از طریق پنل یا API
     */
    public function reply(Request $request, $uuid)
    {
        $request->validate(['message' => 'required|string|max:5000']);

        $conversation = Conversation::where('uuid', $uuid)->firstOrFail();

        try {
            return DB::transaction(function () use ($conversation, $request) {
                $cleanMessage = strip_tags($request->message);

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'visitor_id'      => $conversation->visitor_id,
                    'sender'          => 'admin',
                    'message'         => $cleanMessage
                ]);

                $conversation->touch();

                event(new MessageSent($message));

                if ($conversation->telegram_thread_id) {
                    SendMessageToTelegramJob::dispatch($conversation, $conversation->visitor, $message);
                }

                return response()->json(['ok' => true]);
            });
        } catch (\Exception $e) {
            Log::error('Chat Reply Error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'خطا در ثبت پاسخ'], 500);
        }
    }

    /**
     * متد کمکی برای نوتیفیکیشن ادمین
     */
    private function notifyAdmins($conversation, $visitor, $message)
    {
        $admins = User::all();
        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'id' => Str::uuid(),
                'type' => 'Filament\Notifications\DatabaseNotification',
                'data' => [
                    'title' => '💬 پیام از: ' . ($visitor->name ?? $visitor->email),
                    'body' => Str::limit($message, 50),
                    'icon' => 'heroicon-o-chat',
                    'status' => 'success',
                    'actions' => [
                        ['name' => 'view', 'label' => 'باز کردن چت', 'url' => "/admin/conversations/{$conversation->uuid}/edit"]
                    ],
                ],
                'read_at' => null,
            ]);
        }
    }
}
