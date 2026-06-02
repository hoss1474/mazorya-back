<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Helpers\OtpHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * اعتبارسنجی شماره موبایل با ریجکس
     */
    private function validatePhone($phone)
    {
        return preg_match('/^09[0-9]{9}$/', $phone);
    }

    /**
     * ثبت‌نام کاربر
     */
    public function register(Request $request)
    {
        // اعتبارسنجی با ریجکس اختصاصی برای شماره موبایل
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'unique:clients,phone'
            ],
            'password' => 'required|string|min:8|confirmed',
        ], [
            'phone.regex' => 'شماره موبایل باید با 09 شروع شده و 11 رقم باشد',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت نام کرده است',
            'password.confirmed' => 'رمز عبور با تکرار آن مطابقت ندارد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'statusCode' => 422,
                'errors' => $validator->errors(),
                'message' => 'خطا در اعتبارسنجی اطلاعات'
            ], 422);
        }

        // ایجاد کاربر
        $client = Client::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_active' => false,
            'phone_verified_at' => null,
        ]);

        // ارسال OTP (در صورت نیاز)
        // $otpResult = OtpHelper::sendOtp($request->phone);
        // if (!$otpResult['status']) {
        //     $client->delete();
        //     return response()->json([
        //         'status' => false,
        //         'statusCode' => 500,
        //         'message' => $otpResult['message'],
        //     ], 500);
        // }

        // فقط یک return برای ثبت‌نام موفق
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'ثبت‌نام با موفقیت انجام شد.',
            'data' => [

                'requires_verification' => true,
            ]
        ]);
    }
    /**
     * تایید کد OTP
     */
//    public function verifyOtp(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'phone' => 'required|regex:/^09[0-9]{9}$/',
//            'code' => 'required|regex:/^[0-9]{6}$/',
//        ], [
//            'phone.regex' => 'شماره موبایل معتبر نیست',
//            'code.regex' => 'کد تایید باید 6 رقم باشد',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json([
//                'status' => false,
//                'statusCode' => 422,
//                'errors' => $validator->errors(),
//                'message' => 'اطلاعات وارد شده معتبر نیست'
//            ], 422);
//        }
//
//        $verifyResult = OtpHelper::verifyOtp($request->phone, $request->code);
//
//        if (!$verifyResult['status']) {
//            return response()->json([
//                'status' => false,
//                'statusCode' => 400,
//                'message' => $verifyResult['message'],
//            ], 400);
//        }
//
//        $client = $verifyResult['client'];
//
//        // تولید توکن JWT
//        $token = JWTAuth::fromUser($client);
//
//        return response()->json([
//            'status' => true,
//            'statusCode' => 200,
//            'message' => 'حساب کاربری شما با موفقیت فعال شد.',
//            'data' => [
//                'access_token' => $token,
//                'token_type' => 'bearer',
//                'expires_in' => auth('api')->factory()->getTTL() * 60,
//                'user' => [
//                    'id' => $client->id,
//                    'first_name' => $client->first_name,
//                    'last_name' => $client->last_name,
//                    'email' => $client->email,
//                    'phone' => $client->phone,
//                    'is_active' => $client->is_active,
//                ]
//            ]
//        ]);
//    }

    /**
     * ارسال مجدد کد OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^09[0-9]{9}$/|exists:clients,phone',
        ], [
            'phone.regex' => 'شماره موبایل معتبر نیست',
            'phone.exists' => 'این شماره موبایل در سیستم ثبت نشده است',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'statusCode' => 422,
                'errors' => $validator->errors(),
                'message' => 'اطلاعات وارد شده معتبر نیست'
            ], 422);
        }

        $client = Client::where('phone', $request->phone)->first();

        // بررسی اینکه قبلاً تایید نشده باشد
        if ($client->phone_verified_at || $client->is_active) {
            return response()->json([
                'status' => false,
                'statusCode' => 400,
                'message' => 'این شماره قبلاً تایید شده است.',
            ], 400);
        }

        $otpResult = OtpHelper::sendOtp($request->phone);

        if (!$otpResult['status']) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $otpResult['message'],
            ], 500);
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'کد تایید مجدداً ارسال شد.',
        ]);
    }

    /**
     * ورود کاربر
     */


    /**
     * دریافت اطلاعات کاربر جاری
     */
    public function me()
    {
        $client = auth('api')->user();

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'اطلاعات کاربر',
            'data' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'profile_image' => $client->profile_image,
                'is_active' => $client->is_active,
                'phone_verified_at' => $client->phone_verified_at,
            ]
        ]);
    }

    /**
     * خروج از سیستم
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'با موفقیت خارج شدید.',
        ]);
    }

    /**
     * تمدید توکن
     */
    public function refresh()
    {
        $newToken = auth('api')->refresh();

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'توکن با موفقیت تمدید شد',
            'data' => [
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]
        ]);
    }
    /**
     * 1. ورود با ایمیل و رمز عبور
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'status' => false,
                'statusCode' => 422,
                'message' => 'ایمیل یا رمز عبور اشتباه است',
            ], 422);
        }

        // بررسی فعال بودن حساب
        if (!$client->is_active || !$client->phone_verified_at) {
            return response()->json([
                'status' => false,
                'statusCode' => 403,
                'message' => 'حساب کاربری شما فعال نیست. لطفاً ابتدا شماره موبایل خود را تایید کنید.',
            ], 403);
        }

        $token = auth('api')->login($client);

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                ]
            ]
        ]);
    }

    /**
     * 2. درخواست کد OTP برای ورود با شماره موبایل
     */
    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/',
        ]);

        // بررسی وجود کاربر با این شماره
        $client = Client::where('phone', $request->phone)->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'کاربری با این شماره موبایل یافت نشد. لطفاً ابتدا ثبت نام کنید.',
            ], 404);
        }

        // ارسال کد OTP
        $otpResult = OtpHelper::sendOtp($request->phone);

        if (!$otpResult['status']) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $otpResult['message'],
            ], 500);
        }

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'کد تأیید به شماره موبایل شما ارسال شد.',
            'data' => [
                'phone' => $request->phone,
                'expires_in' => 300 // 5 دقیقه
            ]
        ]);
    }

    /**
     * 3. تأیید کد OTP و ورود با موبایل
     */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^09[0-9]{9}$/',
            'code' => 'required|regex:/^[0-9]{6}$/',
        ]);

        // تأیید کد OTP
        $verifyResult = OtpHelper::verifyOtp($request->phone, $request->code);

        if (!$verifyResult['status']) {
            return response()->json([
                'status' => false,
                'statusCode' => 400,
                'message' => $verifyResult['message'],
            ], 400);
        }

        $client = $verifyResult['client'];

        if (!$client) {
            return response()->json([
                'status' => false,
                'statusCode' => 404,
                'message' => 'کاربر یافت نشد',
            ], 404);
        }

        // تولید توکن JWT
        $token = auth('api')->login($client);

        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                ]
            ]
        ]);
    }
}
