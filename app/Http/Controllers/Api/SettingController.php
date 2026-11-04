<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    // 1. LIHAT DATA TOKO (Admin & Owner)
    public function show(Request $request)
    {
        $store = $request->user()->store;
        return response()->json(['status' => 'success', 'data' => $store]);
    }

    // 2. UPDATE DATA TOKO (Hanya Owner)
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string',
        ]);

        $store = $request->user()->store;
        $store->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Data toko berhasil diupdate', 'data' => $store]);
    }

    // 3. UPLOAD GAMBAR QRIS (Hanya Owner)
    public function uploadQris(Request $request)
    {
        $request->validate([
            'qris_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $store = $request->user()->store;

        if ($store->qris_image_url) {
            $oldPath = str_replace('/storage/', '', $store->qris_image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('qris_image')->store('qris', 'public');
        $url = Storage::url($path);

        $store->update(['qris_image_url' => $url]);

        return response()->json([
            'status' => 'success',
            'message' => 'Gambar QRIS berhasil diunggah',
            'qris_url' => url($url)
        ]);
    }

    // 4. GANTI PIN ADMIN (Hanya Owner)
    public function updateAdminPin(Request $request)
    {
        $request->validate([
            'new_pin' => 'required|string|size:6|regex:/^[0-9]+$/', // Wajib 6 digit angka
        ]);

        // Cari user Admin di toko yang sama
        $admin = User::where('store_id', $request->user()->store_id)
            ->where('role', 'ADMIN')
            ->first();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Akun Admin tidak ditemukan'], 404);
        }

        // Update PIN
        $admin->update([
            'pin_hash' => Hash::make($request->new_pin)
        ]);

        // Hapus semua sesi Admin yang sedang aktif (Paksa login ulang dengan PIN baru)
        DB::table('sessions')->where('user_id', $admin->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'PIN Admin berhasil diubah. Admin harus login ulang.']);
    }
}