<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class ProductController extends Controller
{
    // 1. LIHAT SEMUA PRODUK (Admin & Owner bisa akses)
    public function index()
    {
        // Ambil produk beserta variannya
        $products = Product::with('variants')->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    // 2. TAMBAH PRODUK BARU (Hanya Owner)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            // Validasi array varian
            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.size' => 'required|string',
            'variants.*.color' => 'required|string',
            'variants.*.material' => 'required|string',
            'variants.*.sell_price' => 'required|numeric',
            'variants.*.buy_price' => 'required|numeric',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Simpan Produk Induk
            $product = Product::create([
                'store_id' => $request->user()->store_id,
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'is_active' => true,
            ]);

            // Simpan Varian
            foreach ($request->variants as $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variant['sku'],
                    'size' => $variant['size'],
                    'color' => $variant['color'],
                    'material' => $variant['material'],
                    'sell_price' => $variant['sell_price'],
                    'buy_price' => $variant['buy_price'],
                    'stock' => $variant['stock'],
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan',
                'data' => $product->load('variants')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    // 3. LIHAT DETAIL 1 PRODUK
    public function show(string $id)
    {
        $product = Product::with('variants')->find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $product]);
    }

    // 4. EDIT PRODUK (Hanya Owner - Opsional bisa diupdate nanti)
    public function update(Request $request, string $id)
    {
        return response()->json(['message' => 'Fitur update akan dibuat nanti']);
    }

    // 5. HAPUS PRODUK (Hanya Owner - Hati-hati, ini hapus permanen)
    public function destroy(string $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);
        }

        $product->delete(); // Akan otomatis menghapus varian karena onDelete: Cascade di migration

        return response()->json(['status' => 'success', 'message' => 'Produk berhasil dihapus']);
    }
}