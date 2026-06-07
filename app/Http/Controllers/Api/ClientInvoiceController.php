<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientInvoice;
use App\Models\ClientProject;
use Illuminate\Http\Request;

class ClientInvoiceController extends Controller
{
    // لیست همه فاکتورهای کاربر
    public function index()
    {
        $client = auth('api')->user();

        $invoices = ClientInvoice::whereHas('project', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $invoices
        ]);
    }

    // آپلود فاکتور (ادمین معمولاً استفاده می‌کنه)
    public function upload(Request $request)
    {
        $data = $request->validate([
            'client_project_id' => 'required|exists:client_projects,id',
            'invoice_number' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:pdf,jpg,png'
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/invoices'), $filename);

        $invoice = ClientInvoice::create([
            'client_project_id' => $data['client_project_id'],
            'invoice_number' => $data['invoice_number'] ?? null,
            'file_path' => $filename
        ]);

        return response()->json([
            'status' => true,
            'message' => 'فاکتور ثبت شد',
            'data' => $invoice
        ]);
    }
}
