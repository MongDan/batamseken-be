<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids; // Pastikan ini ada

    protected $fillable = [
        'store_id', 
        'user_role', 
        'action', 
        'entity', 
        'entity_id', 
        'old_value', 
        'new_value', 
        'reason'
    ];
}