<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientMessage;
use Illuminate\Http\Request;

class ClientMessageController extends Controller
{
    // لیست پیام‌های کاربر
    public function index()
    {
        $client = auth('api')->user();

        $messages = ClientMessage::where('client_id', $client->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $messages
        ]);
    }

    // ارسال پیام جدید
    public function store(Request $request)
    {
        $client = auth('api')->user();

        $data = $request->validate([
            'message' => 'required|string'
        ]);

        $message = ClientMessage::create([
            'client_id' => $client->id,
            'sender' => 'client',
            'message' => $data['message'],
            'is_read' => false
        ]);

        return response()->json([
            'status' => true,
            'message' => 'پیام ارسال شد',
            'data' => $message
        ]);
    }

    // خواندن پیام (mark as read)
    public function markAsRead($id)
    {
        $client = auth('api')->user();

        $message = ClientMessage::where('client_id', $client->id)
            ->where('id', $id)
            ->first();

        if (!$message) {
            return response()->json([
                'status' => false,
                'message' => 'پیام پیدا نشد'
            ], 404);
        }

        $message->update([
            'is_read' => true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'خوانده شد'
        ]);
    }
}
