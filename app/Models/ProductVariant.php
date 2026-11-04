<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 
        'sku', 
        'size', 
        'color', 
        'material', 
        'sell_price', 
        'buy_price', 
        'stock', 
        'is_active'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}