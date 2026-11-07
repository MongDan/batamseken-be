<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\ProductVariant;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    // 1. LIHAT SEMUA RIWAYAT TRANSAKSI
    public function index()
    {
        $transactions = Transaction::with(['items', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    // 2. BUAT TRANSAKSI BARU (POS)
    public function store(Request $request)
    {
        $request->validate([
            'customer.name' => 'required|string',
            'customer.address' => 'required|string',
            'customer.phone' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.serial_number' => 'nullable|string',
            'payment_method' => 'required|in:CASH,QRIS',
            'cash_received' => 'required_if:payment_method,CASH|numeric|min:0',
            'qris_proof_url' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Simpan/Update Pelanggan
            $customer = Customer::firstOrCreate(
                ['phone' => $request->customer['phone'], 'store_id' => $request->user()->store_id],
                [
                    'name' => $request->customer['name'],
                    'address' => $request->customer['address'],
                ]
            );

            // Generate Nomor Faktur
            $datePrefix = 'BS-' . Carbon::now()->format('Ymd') . '-';
            $lastTrx = Transaction::where('invoice_no', 'like', $datePrefix . '%')
                ->orderBy('invoice_no', 'desc')
                ->first();
            
            $newNumber = $lastTrx ? str_pad(intval(substr($lastTrx->invoice_no, -4)) + 1, 4, '0', STR_PAD_LEFT) : '0001';
            $invoiceNo = $datePrefix . $newNumber;

            // Hitung Total & Cek Stok
            $subtotal = 0;
            $transactionItems = [];

            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);

                if ($variant->stock < $item['qty']) {
                    throw new \Exception("Stok tidak mencukupi untuk SKU: {$variant->sku}. Sisa: {$variant->stock}");
                }

                $itemSubtotal = $variant->sell_price * $item['qty'];
                $subtotal += $itemSubtotal;

                $transactionItems[] = [
                    'variant_id' => $variant->id,
                    'product_name_snapshot' => $variant->product->name,
                    'variant_snapshot' => "{$variant->size} / {$variant->color} / {$variant->material}",
                    'qty' => $item['qty'],
                    'unit_price' => $variant->sell_price,
                    'subtotal' => $itemSubtotal,
                    'serial_number' => $item['serial_number'] ?? null,
                ];

                // Potong Stok Otomatis
                $variant->decrement('stock', $item['qty']);
            }

            // Hitung Kembalian
            $change = 0;
            if ($request->payment_method === 'CASH') {
                if ($request->cash_received < $subtotal) {
                    throw new \Exception("Uang yang diterima kurang dari total belanja.");
                }
                $change = $request->cash_received - $subtotal;
            }

            // Simpan Transaksi
            $transaction = Transaction::create([
                'store_id' => $request->user()->store_id,
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'user_id' => $request->user()->id,
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'cash_received' => $request->cash_received ?? 0,
                'change' => $change,
                'qris_proof_url' => $request->qris_proof_url,
                'note' => $request->note,
                'status' => 'COMPLETED',
            ]);

            // Simpan Detail Transaksi
            foreach ($transactionItems as $trxItem) {
                $trxItem['transaction_id'] = $transaction->id;
                TransactionItem::create($trxItem);
            }

            // --- TAMBAHAN BARU: CATAT KE AUDIT LOG ---
            \App\Models\AuditLog::create([
                'store_id' => $request->user()->store_id,
                'user_role' => $request->user()->role,
                'action' => 'CREATE_TRANSACTION',
                'entity' => 'Transaction',
                'entity_id' => $transaction->id,
                'reason' => 'Transaksi baru berhasil dibuat dengan No. Faktur: ' . $invoiceNo,
            ]);
            // -----------------------------------------

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil',
                'data' => $transaction->load('items', 'customer')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 400);
        }
    }

    // 3. DETAIL 1 TRANSAKSI
    public function show(string $id)
    {
        $transaction = Transaction::with(['items', 'customer'])->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    // 4. VOID TRANSAKSI (Admin & Owner)
    public function void(string $id)
    {
        $transaction = Transaction::with('items')->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaction->status !== 'COMPLETED') {
            return response()->json(['status' => 'error', 'message' => 'Hanya transaksi selesai yang bisa di-void'], 400);
        }

        try {
            DB::beginTransaction();

            // Kembalikan Stok
            foreach ($transaction->items as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $variant->increment('stock', $item->qty);
                }
            }

            // Update Status
            $transaction->update([
                'status' => 'VOID',
                'voided_at' => Carbon::now(),
            ]);

            // Catat di Audit Log
            \App\Models\AuditLog::create([
                'store_id' => $transaction->store_id,
                'user_role' => $request_user_role = auth()->user()->role,
                'action' => 'VOID_TRANSACTION',
                'entity' => 'Transaction',
                'entity_id' => $transaction->id,
                'reason' => 'Transaksi dibatalkan (Void)',
            ]);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Transaksi berhasil di-void dan stok dikembalikan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 5. HAPUS PERMANEN (Hanya Owner)
    public function destroy(Request $request, string $id)
    {
        $request->validate(['reason' => 'required|string']);

        $transaction = Transaction::with('items')->find($id);

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            // Kembalikan Stok (jika sebelumnya belum di-void)
            if ($transaction->status === 'COMPLETED') {
                foreach ($transaction->items as $item) {
                    $variant = ProductVariant::find($item->variant_id);
                    if ($variant) {
                        $variant->increment('stock', $item->qty);
                    }
                }
            }

            // Update Status
            $transaction->update([
                'status' => 'DELETED',
                'deleted_at' => Carbon::now(),
            ]);

            // Catat di Audit Log
            \App\Models\AuditLog::create([
                'store_id' => $transaction->store_id,
                'user_role' => auth()->user()->role,
                'action' => 'DELETE_TRANSACTION',
                'entity' => 'Transaction',
                'entity_id' => $transaction->id,
                'reason' => $request->reason,
            ]);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Transaksi berhasil dihapus permanen']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 6. RETUR BARANG (Admin & Owner)
    public function returnItem(Request $request, string $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1',
            'reason' => 'required|string'
        ]);

        $transaction = Transaction::with('items')->find($id);
        if (!$transaction) return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan'], 404);
        if ($transaction->status !== 'COMPLETED') return response()->json(['status' => 'error', 'message' => 'Hanya transaksi selesai yang bisa diretur'], 400);

        try {
            DB::beginTransaction();

            foreach ($request->items as $returnItem) {
                // Cari item asli di transaksi
                $originalItem = $transaction->items->where('variant_id', $returnItem['variant_id'])->first();
                
                if (!$originalItem) throw new \Exception("Barang tidak ada di transaksi ini.");
                if ($returnItem['qty'] > $originalItem->qty) throw new \Exception("Jumlah retur melebihi jumlah yang dibeli.");

                // Kembalikan Stok
                $variant = ProductVariant::find($returnItem['variant_id']);
                if ($variant) $variant->increment('stock', $returnItem['qty']);

                // Catat di tabel ProductReturn
                \App\Models\ProductReturn::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => $request->user()->id,
                    'reason' => $request->reason . " (Qty: {$returnItem['qty']})",
                ]);

                // Catat di Audit Log
                \App\Models\AuditLog::create([
                    'store_id' => $transaction->store_id,
                    'user_role' => auth()->user()->role,
                    'action' => 'RETURN_ITEM',
                    'entity' => 'Transaction',
                    'entity_id' => $transaction->id,
                    'reason' => "Retur qty {$returnItem['qty']} untuk varian ID {$returnItem['variant_id']}. Alasan: {$request->reason}",
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Barang berhasil diretur dan stok dikembalikan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}