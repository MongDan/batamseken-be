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
        $startDate = $request->query('start_date', now()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $transactions = Transaction::where('store_id', $storeId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'COMPLETED');
        $revenue = $transactions->sum('total');
        $count = $transactions->count();

        $margin = TransactionItem::whereHas('transaction', function ($query) use ($storeId, $startDate, $endDate) {
                $query->where('store_id', $storeId)
                      ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                      ->where('status', 'COMPLETED');
            })
            ->join('product_variants', 'transaction_items.variant_id', '=', 'product_variants.id')
            ->sum(DB::raw('transaction_items.qty * (transaction_items.unit_price - product_variants.buy_price)'));

        $topProducts = TransactionItem::whereHas('transaction', function ($query) use ($storeId, $startDate, $endDate) {
                $query->where('store_id', $storeId)
                      ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                      ->where('status', 'COMPLETED');
            })
            ->select('product_name_snapshot', DB::raw('SUM(qty) as total_sold'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // FITUR BARU: Margin per Produk
        $productMargins = TransactionItem::whereHas('transaction', function ($query) use ($storeId, $startDate, $endDate) {
                $query->where('store_id', $storeId)
                      ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
                      ->where('status', 'COMPLETED');
            })
            ->join('product_variants', 'transaction_items.variant_id', '=', 'product_variants.id')
            ->select(
                'transaction_items.product_name_snapshot',
                DB::raw('SUM(transaction_items.qty * (transaction_items.unit_price - product_variants.buy_price)) as total_margin')
            )
            ->groupBy('transaction_items.product_name_snapshot')
            ->orderByDesc('total_margin')
            ->get();

        $dailySales = Transaction::where('store_id', $storeId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'COMPLETED')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenue' => $revenue,
                'transactions' => $count,
                'margin' => $margin,
                'top_products' => $topProducts,
                'product_margins' => $productMargins,
                'daily_sales' => $dailySales,
            ]
        ]);
    }
}