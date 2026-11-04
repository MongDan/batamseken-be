<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasUuids; // <--- INI WAJIB ADA

    protected $fillable = [
        'name', 'address', 'phone', 'qris_image_url',
    ];
}