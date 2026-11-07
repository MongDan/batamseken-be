<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data berdasarkan store_id user yang login
        $query = AuditLog::where('store_id', $request->user()->store_id)
            ->orderBy('created_at', 'desc');

        // Gunakan filled() agar hanya memfilter jika nilainya TIDAK KOSONG
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Gunakan filled() untuk filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        return response()->json([
            'status' => 'success', 
            'data' => $query->get()
        ]);
    }
}