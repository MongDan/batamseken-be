<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids; // <--- Tambahkan ini
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasUuids; // <--- Tambahkan ini

    protected $fillable = [
        'transaction_id', 'variant_id', 'product_name_snapshot',
        'variant_snapshot', 'qty', 'unit_price', 'subtotal', 'serial_number',
    ];
        public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}