<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\User;

class StoreAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Toko
        $store = Store::create([
            'id' => Str::uuid(),
            'name' => 'Batam Seken',
            'address' => 'Batam, Kepulauan Riau',
            'phone' => '081234567890',
        ]);

        // 2. Buat Akun Owner (Email & Password)
        User::create([
            'store_id' => $store->id,
            'role' => 'OWNER',
            'name' => 'Owner Batam Seken',
            'email' => 'owner@batamseken.com',
            'password' => Hash::make('password123'),
        ]);

        // 3. Buat Akun Admin (Kasir - PIN)
        User::create([
            'store_id' => $store->id,
            'role' => 'ADMIN',
            'name' => 'Kasir',
            'pin_hash' => Hash::make('123456'),
        ]);
    }
}