<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use Illuminate\Http\Request;

class ClientProjectController extends Controller
{
    // لیست پروژه‌های کاربر
    public function index()
    {
        $client = auth('api')->user();

        $projects = ClientProject::where('client_id', $client->id)
            ->with(['payments'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $projects
        ]);
    }

    // جزئیات یک پروژه
    public function show($id)
    {
        $client = auth('api')->user();

        $project = ClientProject::where('client_id', $client->id)
            ->where('id', $id)
            ->with(['payments', 'invoices'])
            ->first();

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'پروژه پیدا نشد'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $project
        ]);
    }

    // فقط اقساط یک پروژه (برای فرانت جدا)
    public function payments($id)
    {
        $client = auth('api')->user();

        $project = ClientProject::where('client_id', $client->id)
            ->where('id', $id)
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

}
