<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::where('store_id', $request->user()->store_id)
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan Action (misal: VOID_TRANSACTION)
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter berdasarkan tanggal
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }
}