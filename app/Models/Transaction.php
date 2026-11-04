<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids; // Pastikan ini ada

    protected $fillable = [
        'store_id', 
        'invoice_no', 
        'customer_id', 
        'user_id',
        'payment_method', 
        'subtotal', 
        'total',
        'cash_received', 
        'change', 
        'qris_proof_url',
        'note', 
        'status',
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

        public function returns()
    {
        return $this->hasMany(ProductReturn::class);
    }
}