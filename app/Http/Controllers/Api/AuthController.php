<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login_type' => 'required|in:admin,owner',
            'pin' => 'required_if:login_type,admin',
            'email' => 'required_if:login_type,owner',
            'password' => 'required_if:login_type,owner',
        ]);

        // 1. Setup Rate Limiting Key (Berdasarkan IP atau Email)
        $throttleKey = $request->login_type === 'admin' 
            ? 'admin-login|' . $request->ip() 
            : 'owner-login|' . $request->email . '|' . $request->ip();

        // 2. Cek apakah sudah terkunci (maksimal 5 percobaan)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'pin' => ["Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik."],
            ]);
        }

        $user = null;

        if ($request->login_type === 'admin') {
            $user = User::where('role', 'ADMIN')->whereNotNull('pin_hash')->first();
            
            if (!$user || !Hash::check($request->pin, $user->pin_hash)) {
                // Catat percobaan gagal, kunci 300 detik (5 menit)
                RateLimiter::hit($throttleKey, 300); 
                throw ValidationException::withMessages([
                    'pin' => ['PIN yang dimasukkan salah.'],
                ]);
            }
        } else {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($throttleKey, 300);
                throw ValidationException::withMessages([
                    'email' => ['Email atau password salah.'],
                ]);
            }
        }

        // Jika berhasil, bersihkan hitungan rate limit
        RateLimiter::clear($throttleKey);

        // 3. Fitur 1 Perangkat untuk Admin (Hapus session lama)
        if ($user->role === 'ADMIN') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        // Login-kan user & Regenerate Session ID
        Auth::login($user); 
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
}