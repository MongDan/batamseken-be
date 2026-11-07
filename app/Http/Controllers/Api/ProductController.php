<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('variants')->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $products]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
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

            $product = Product::create([
                'store_id' => $request->user()->store_id,
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'is_active' => true,
            ]);

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
            return response()->json(['status' => 'success', 'message' => 'Produk berhasil ditambahkan', 'data' => $product->load('variants')], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // FITUR BARU: EDIT PRODUK
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|uuid',
            'variants.*.sku' => 'required|string',
            'variants.*.size' => 'required|string',
            'variants.*.color' => 'required|string',
            'variants.*.material' => 'required|string',
            'variants.*.sell_price' => 'required|numeric',
            'variants.*.buy_price' => 'required|numeric',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product->update([
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
            ]);

            $existingVariantIds = [];
            foreach ($request->variants as $variantData) {
                if (isset($variantData['id']) && $variantData['id']) {
                    $variant = ProductVariant::find($variantData['id']);
                    if ($variant) {
                        $variant->update($variantData);
                        $existingVariantIds[] = $variant->id;
                    }
                } else {
                    $newVariant = $product->variants()->create($variantData);
                    $existingVariantIds[] = $newVariant->id;
                }
            }

            // Nonaktifkan varian yang tidak ada di request (dihapus user)
            $product->variants()->whereNotIn('id', $existingVariantIds)->update(['is_active' => false]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Produk berhasil diupdate', 'data' => $product->load('variants')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $product = Product::with('variants')->find($id);
        if (!$product) return response()->json(['status' => 'error', 'message' => 'Produk tidak ditemukan'], 404);

        try {
            $hasBeenSold = \App\Models\TransactionItem::whereHas('variant', function($q) use ($product) {
                $q->where('product_id', $product->id);
            })->exists();

            if ($hasBeenSold) {
                $product->update(['is_active' => false]);
                $product->variants()->update(['is_active' => false]);
                return response()->json(['status' => 'success', 'message' => 'Produk dinonaktifkan karena sudah memiliki riwayat transaksi']);
            }

            $product->delete(); 
            return response()->json(['status' => 'success', 'message' => 'Produk berhasil dihapus permanen']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}