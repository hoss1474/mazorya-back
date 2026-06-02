<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
// 🧾 نمایش اطلاعات پروفایل کلاینت لاگین‌شده
    public function show()
    {
        $client = auth('api')->user();

        // اگر کاربر لاگین نکرده
        if (!$client) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'email' => $client->email,
            'phone' => $client->phone,

            'addresses' => $client->addresses
                ? $client->addresses->map(function ($addresses) {
                    return [
                        'id' => $addresses->id,
                        'title' => $addresses->title,
                        'address' => $addresses->address,
                        'post_code' => $addresses->post_code,
                        'city' => $addresses->city,
                    ];
                })
                : [], // یا collect()
            'avatar' => $client->avatar ? [[
                'alt' => 'avatar',
                'extension' => pathinfo($client->avatar, PATHINFO_EXTENSION),
                'data' => 'string',
                'name' => basename($client->avatar),
                'size' => 4256,
                'type' => 'avatar',
                'uri' => asset('uploads/' . $client->avatar),
            ]] : [],
        ]);
    }


    public function userOrders()
    {
        try {
            $auth = getAuthenticatedClient(); // فرض: یک آبجکت با اطلاعات کلاینت لاگین‌شده
            $clientId = $auth->id;

            $orders = Order::where('user_id', $clientId)
                ->where('status', 'paid')
                ->select('id', 'tracking_code', 'address_id', 'price')
                ->with([
                    'address:id,client_id,title,address,post_code',
                    'orderItems:id,order_id,product_id,color_product_id,price,quantity',
                    'orderItems.colorProduct:id,product_id,color',
                    'orderItems.product:id,title',
                ])
                ->get();

            return ApiResponse::success("لیست سفارشات کاربر", ListUserOrdersResource::collection($orders));

        } catch (\Exception $exception) {
            \Log::error('ProfileController@userOrders', [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);

            return ApiResponse::failed(Response::HTTP_UNPROCESSABLE_ENTITY, 'خطا در دریافت سفارشات', (object)[]);
        }
    }

    public function update(Request $request)
    {
        $client = auth('api')->user();

        $data = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:clients,email,' . $client->id,
            'password' => 'nullable|string|min:6|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'remove_avatar' => 'nullable|boolean', // برای حذف آواتار
        ]);

        // اطلاعات پایه
        $updateData = [];

        if (isset($data['first_name'])) $updateData['first_name'] = $data['first_name'];
        if (isset($data['last_name'])) $updateData['last_name'] = $data['last_name'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];

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
        // اگر درخواست حذف آواتار داده شده
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

        return response()->json([
            'success' => true,
            'message' => 'پروفایل با موفقیت بروزرسانی شد.',
            'data' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone, // فقط نمایشی، قابل تغییر نیست
                'profile_image' => $client->profile_image ? asset('uploads/' . $client->profile_image) : null,
            ]
        ]);
    }
}
