<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
       public function dashboard(Request $request)
    {
        $storeId = $request->user()->store_id;
        
        // Ambil input tanggal, default hari ini jika kosong
        $startDate = $request->query('start_date', now()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        // 1. Omzet & Jumlah Transaksi
        $transactions = Transaction::where('store_id', $storeId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'COMPLETED');
            
        $revenue = $transactions->sum('total');
        $count = $transactions->count();

        // 2. Margin Keuntungan
        $margin = TransactionItem::whereHas('transaction', function ($query) use ($storeId, $startDate, $endDate) {
                $query->where('store_id', $storeId)
                      ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                      ->where('status', 'COMPLETED');
            })
            ->join('product_variants', 'transaction_items.variant_id', '=', 'product_variants.id')
            ->sum(DB::raw('transaction_items.qty * (transaction_items.unit_price - product_variants.buy_price)'));

        return response()->json([
            'status' => 'success',
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenue' => $revenue,
                'transactions' => $count,
                'margin' => $margin
            ]
        ]);
    }
}