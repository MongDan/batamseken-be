<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    use HasUuids;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'reason',
    ];
}