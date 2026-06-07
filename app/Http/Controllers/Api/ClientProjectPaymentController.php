<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use Illuminate\Http\Request;

class ClientProjectPaymentController extends Controller
{
    // لیست اقساط یک پروژه
    public function index($projectId)
    {
        $client = auth('api')->user();

        $project = ClientProject::where('client_id', $client->id)
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'پروژه پیدا نشد'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $project->payments
        ]);
    }

    // جزئیات یک قسط (اختیاری ولی کاربردی)
    public function show($projectId, $paymentId)
    {
        $client = auth('api')->user();

        $project = ClientProject::where('client_id', $client->id)
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'پروژه پیدا نشد'
            ], 404);
        }

        $payment = $project->payments()->where('id', $paymentId)->first();

        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'پرداخت پیدا نشد'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $payment
        ]);
    }
}
