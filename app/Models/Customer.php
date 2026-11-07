<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- Tambahkan ini
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids; // <--- Tambahkan ini

        public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected $fillable = ['store_id', 'name', 'address', 'phone'];
}