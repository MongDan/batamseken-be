<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\TransactionItem;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    // 1. EKSPOR TRANSAKSI
    public function transactions(Request $request)
    {
        $storeId = $request->user()->store_id;
        
        $response = new StreamedResponse(function() use ($storeId) {
            $handle = fopen('php://output', 'w');
            // Header Kolom
            fputcsv($handle, ['No Faktur', 'Tanggal', 'Pelanggan', 'Metode Bayar', 'Total', 'Status', 'Catatan']);

            Transaction::where('store_id', $storeId)->with('customer')->chunk(100, function($transactions) use ($handle) {
                foreach ($transactions as $trx) {
                    fputcsv($handle, [
                        $trx->invoice_no,
                        $trx->created_at->format('Y-m-d H:i:s'),
                        $trx->customer->name ?? '-',
                        $trx->payment_method,
                        $trx->total,
                        $trx->status,
                        $trx->note
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="transaksi_' . date('Ymd') . '.csv"');
        
        return $response;
    }

    // 2. EKSPOR PRODUK & STOK
    public function products(Request $request)
    {
        $storeId = $request->user()->store_id;

        $response = new StreamedResponse(function() use ($storeId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama Produk', 'Kategori', 'SKU', 'Ukuran', 'Warna', 'Bahan', 'Harga Jual', 'Harga Beli', 'Stok']);

            Product::where('store_id', $storeId)->with('variants')->chunk(100, function($products) use ($handle) {
                foreach ($products as $product) {
                    foreach ($product->variants as $variant) {
                        fputcsv($handle, [
                            $product->name,
                            $product->category,
                            $variant->sku,
                            $variant->size,
                            $variant->color,
                            $variant->material,
                            $variant->sell_price,
                            $variant->buy_price,
                            $variant->stock
                        ]);
                    }
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="produk_' . date('Ymd') . '.csv"');
        
        return $response;
    }
}