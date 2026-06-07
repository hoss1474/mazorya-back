<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
// 🧾 نمایش اطلاعات پروفایل کلاینت لاگین‌شده
    public function show()
    {
        $client = auth('api')->user();

        // اگر کاربر لاگین نکرده
        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // پردازش آواتار
        $avatarUrl = null;
        if ($client->avatar) {
            $avatarUrl = asset('uploads/' . $client->avatar);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $client->id,
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'email' => $client->email,
                'phone' => $client->phone,
                'company_name' => $client->company_name ?? null, // اگه فیلد داری
                'website' => $client->website ?? null, // اگه فیلد داری
                'avatar' => $avatarUrl,
                'status' => $client->status ?? 'active', // اگه فیلد status داری
                'created_at' => $client->created_at ? $client->created_at->toISOString() : null,

            ]
        ]);
    }
//    public function userOrders()
//    {
//        try {
//            $auth = getAuthenticatedClient(); // فرض: یک آبجکت با اطلاعات کلاینت لاگین‌شده
//            $clientId = $auth->id;
//
//            $orders = Order::where('user_id', $clientId)
//                ->where('status', 'paid')
//                ->select('id', 'tracking_code', 'address_id', 'price')
//                ->with([
//                    'address:id,client_id,title,address,post_code',
//                    'orderItems:id,order_id,product_id,color_product_id,price,quantity',
//                    'orderItems.colorProduct:id,product_id,color',
//                    'orderItems.product:id,title',
//                ])
//                ->get();
//
//            return ApiResponse::success("لیست سفارشات کاربر", ListUserOrdersResource::collection($orders));
//
//        } catch (\Exception $exception) {
//            \Log::error('ProfileController@userOrders', [
//                'message' => $exception->getMessage(),
//                'line' => $exception->getLine(),
//            ]);
//
//            return ApiResponse::failed(Response::HTTP_UNPROCESSABLE_ENTITY, 'خطا در دریافت سفارشات', (object)[]);
//        }
//    }

    public function update(Request $request)
    {
        $client = auth('api')->user();

        // اعتبارسنجی ورودی‌ها
        $data = $request->validate([
            'full_name' => 'nullable|string|max:200',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20|unique:clients,phone,' . $client->id,
            'email' => 'nullable|email|unique:clients,email,' . $client->id,
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $updateData = [];

        // مدیریت full_name (تقسیم به first_name و last_name)
        if (isset($data['full_name'])) {
            $nameParts = explode(' ', trim($data['full_name']), 2);
            $updateData['first_name'] = $nameParts[0];
            $updateData['last_name'] = $nameParts[1] ?? '';
        }

        // فیلدهای جداگانه name (اگر جداگانه اومده بودن)
        if (isset($data['first_name'])) $updateData['first_name'] = $data['first_name'];
        if (isset($data['last_name'])) $updateData['last_name'] = $data['last_name'];

        // سایر فیلدها
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['company_name'])) $updateData['company_name'] = $data['company_name'];
        if (isset($data['website'])) $updateData['website'] = $data['website'];

        // آپدیت پسورد
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        // مدیریت آواتار
        if ($request->hasFile('profile_image')) {
            // حذف آواتار قبلی
            if ($client->profile_image && file_exists(public_path('uploads/' . $client->profile_image))) {
                unlink(public_path('uploads/' . $client->profile_image));
            }

            $file = $request->file('profile_image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $updateData['profile_image'] = $filename;
        }
        // حذف آواتار
        elseif ($request->input('remove_avatar') == true) {
            if ($client->profile_image && file_exists(public_path('uploads/' . $client->profile_image))) {
                unlink(public_path('uploads/' . $client->profile_image));
            }
            $updateData['profile_image'] = null;
        }

        // آپدیت نهایی
        if (!empty($updateData)) {
            $client->update($updateData);
            $client->refresh();
        }

        // ساخت full_name برای خروجی
        $fullName = trim($client->first_name . ' ' . $client->last_name);

        return response()->json([
            'status' => true,
            'message' => 'پروفایل با موفقیت بروزرسانی شد.',
            'data' => [
                'id' => $client->id,
                'full_name' => $fullName ?: null,
                'email' => $client->email,
                'phone' => $client->phone,
                'company_name' => $client->company_name,
                'website' => $client->website,
                'avatar' => $client->profile_image ? asset('uploads/' . $client->profile_image) : null,
                'status' => $client->status ?? 'active',
                'created_at' => $client->created_at ? $client->created_at->toISOString() : null,
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        // بررسی رمز فعلی
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'رمز عبور فعلی اشتباه است'
            ], 400);
        }

        // تغییر رمز
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد'
        ]);
    }
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $user = Auth::user();

        // حذف آواتار قبلی اگر وجود داشت
        if ($user->avatar && file_exists(public_path($user->avatar))) {
            unlink(public_path($user->avatar));
        }

        // ذخیره آواتار جدید
        $file = $request->file('avatar');
        $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
        $path = $file->move(public_path('uploads/avatars'), $fileName);

        $avatarPath = '/uploads/avatars/' . $fileName;

        // ذخیره مسیر در دیتابیس
        $user->avatar = $avatarPath;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'آواتار با موفقیت آپلود شد',
            'data' => [
                'avatar_url' => $avatarPath
            ]
        ]);
    }

}
