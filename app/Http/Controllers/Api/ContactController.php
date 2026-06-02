<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Mail\NewContactMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class ContactController extends Controller
{
    /**
     * ثبت تماس جدید (عمومی + Throttle شده در روت)
     */
    public function store(Request $request)
    {
        // ۱. اعتبارسنجی دقیق
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'message' => 'required|string|max:5000',
            'lang'    => ['required', 'string', Rule::in(['en','fa','de','fr','es','ar'])],
        ]);

        try {
            // ۲. پاکسازی پیام از کدهای مخرب HTML (XSS Protection)
            $validated['message'] = strip_tags($validated['message']);
            $validated['name'] = strip_tags($validated['name']);

            // ۳. ذخیره تماس
            $contact = Contact::create($validated);

            // ۴. ارسال ایمیل تایید به کاربر (در بلاک جداگانه برای جلوگیری از کرش)
            try {
                // اطمینان حاصل کنید که NewContactMail از ShouldQueue استفاده می‌کند
                Mail::to($contact->email)->send(new NewContactMail($contact));
            } catch (Exception $e) {
                Log::warning('Contact Mail Delivery Failed: ' . $e->getMessage());
            }

            // ۵. ارسال نوتیفیکیشن به ادمین (تلگرام یا دیتابیس) - اگر سرویسش رو داری اینجا صدا بزن
            // TelegramService::sendMessage("پیام جدید از: " . $contact->name);

            return response()->json([
                'status'  => true,
                'message' => $validated['lang'] === 'fa' ? 'پیام شما با موفقیت دریافت شد.' : 'Message received successfully.',
                'data'    => [
                    'id'    => $contact->id,
                    'name'  => $contact->name,
                    'email' => $contact->email
                ],
            ], 201);

        } catch (Exception $e) {
            Log::error('Contact Store Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    /**
     * لیست تمام تماس‌ها (فقط برای ادمین - Sanctum)
     */
    public function index()
    {
        // استفاده از paginate حیاتی است
        $contacts = Contact::latest()->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $contacts
        ]);
    }

    /**
     * مشاهده جزئیات (فقط برای ادمین)
     */
    public function show($id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $contact
        ]);
    }

    /**
     * حذف تماس (فقط برای ادمین)
     */
    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
