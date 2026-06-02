<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $update = $request->all();
        $message = $update['message'] ?? null;

        if (!$message) return response()->json(['ok' => true]);

        $threadId = $message['message_thread_id'] ?? null;
        $text = $message['text'] ?? ($message['caption'] ?? ''); // استفاده از کپشن برای فایل‌ها
        $filePath = null;

        // ۱. پیدا کردن یا تشخیص مکالمه (همان منطق قبلی شما)
        $conversation = Conversation::where('telegram_thread_id', $threadId)->first();

        if (!$conversation && isset($message['reply_to_message'])) {
            $replyTo = $message['reply_to_message'];
            $lookupText = ($replyTo['text'] ?? '') . ($replyTo['forum_topic_created']['name'] ?? '');
            if (preg_match('/CID[:|-](\d+)/', $lookupText, $matches)) {
                $conversation = Conversation::find($matches[1]);
                if ($conversation) {
                    $conversation->update(['telegram_thread_id' => $threadId]);
                }
            }
        }

        if (!$conversation) return response()->json(['ok' => true]);

        // ۲. پردازش فایل صوتی (Voice) یا فایل (Document)
        $fileId = null;
        if (isset($message['voice'])) {
            $fileId = $message['voice']['file_id'];
            $text = $text ?: '🎤 پیام صوتی از تلگرام';
        } elseif (isset($message['document'])) {
            $fileId = $message['document']['file_id'];
            $text = $text ?: '📎 فایل پیوست از تلگرام';
        } elseif (isset($message['photo'])) {
            // تلگرام چندین سایز عکس می‌فرستد، آخرین سایز باکیفیت‌ترین است
            $fileId = end($message['photo'])['file_id'];
            $text = $text ?: '🖼 تصویر از تلگرام';
        }

        // ۳. دانلود فایل از تلگرام و ذخیره در دیسک شما
        if ($fileId) {
            try {
                $telegram = new Api(config('services.telegram.bot_token'));
                $file = $telegram->getFile(['file_id' => $fileId]);
                $remotePath = $file->getFilePath();

                $url = "https://api.telegram.org/file/bot" . config('services.telegram.bot_token') . "/" . $remotePath;

                $extension = pathinfo($remotePath, PATHINFO_EXTENSION);
                $newFileName = 'chat-attachments/' . uniqid() . '.' . $extension;

                // دانلود محتوا
                $fileContent = Http::get($url)->body();

                // ذخیره در دیسک api_public
                Storage::disk('api_public')->put($newFileName, $fileContent);
                $filePath = $newFileName;

            } catch (\Exception $e) {
                Log::error('Telegram Download Error: ' . $e->getMessage());
            }
        }


        // ۴. ذخیره در دیتابیس
        if ($text || $filePath) {
            $newMessage = Message::create([
                'conversation_id' => $conversation->id,
                'visitor_id'      => $conversation->visitor_id,
                'sender'          => 'admin',
                'message'         => $text,
                'file_path'       => $filePath,
            ]);

            $conversation->touch();

            // ⚡ این بخش را اضافه کنید تا پیام همان لحظه در سایت ظاهر شود
            event(new \App\Events\MessageSent($newMessage));
        }

        return response()->json(['ok' => true]);
    }
}
