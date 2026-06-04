<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // REGISTER


    public function register(Request $request)
    {
        try {

            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|unique:clients,email',
                'phone'      => 'required|string|unique:clients,phone',
                'password'   => 'required|min:8|confirmed',
            ], [
                'email.unique' => 'این ایمیل قبلاً ثبت شده است',
                'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است',
                'password.confirmed' => 'تکرار رمز عبور صحیح نیست',
            ]);

            $client = Client::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'is_active'  => true,
            ]);

            $token = auth('api')->login($client);

            return response()->json([
                'status' => true,
                'message' => 'ثبت‌نام موفق',
                'data' => [
                    'access_token' => $token,
                    'user' => $client
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در اعتبارسنجی',
                'errors' => $e->errors()
            ], 422);
        }
    }
    // LOGIN (email or phone)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'status' => false,
                'message' => 'ایمیل یا رمز اشتباه است'
            ], 422);
        }

        $token = auth('api')->login($client);

        return response()->json([
            'status' => true,
            'message' => 'ورود موفق',
            'data' => [
                'access_token' => $token,
                'user' => $client
            ]
        ]);
    }
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $client = Client::where('phone', $request->phone)->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'کاربر پیدا نشد'
            ], 404);
        }

        // اینجا OTP helper
        OtpHelper::sendOtp($request->phone);

        return response()->json([
            'status' => true,
            'message' => 'کد ارسال شد'
        ]);
    }

    public function verifyOtpLogin(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'code' => 'required'
        ]);

        $verify = OtpHelper::verifyOtp($request->phone, $request->code);

        if (!$verify['status']) {
            return response()->json([
                'status' => false,
                'message' => 'کد اشتباه است'
            ], 422);
        }

        $client = Client::where('phone', $request->phone)->first();

        $token = auth('api')->login($client);

        return response()->json([
            'status' => true,
            'message' => 'ورود موفق',
            'data' => [
                'access_token' => $token,
                'user' => $client
            ]
        ]);
    }

    // PROFILE
    public function me()
    {
        return response()->json([
            'status' => true,
            'data' => auth('api')->user()
        ]);
    }

    // LOGOUT
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => true,
            'message' => 'خروج موفق'
        ]);
    }

    // REFRESH TOKEN
    public function refresh()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'access_token' => auth('api')->refresh()
            ]
        ]);
    }
}
