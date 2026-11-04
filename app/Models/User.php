<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- WAJIB ADA

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids; // <-- WAJIB ADA

    protected $fillable = [
        'store_id', 'role', 'name', 'email', 'pin_hash', 'password',
    ];

    protected $hidden = [
        'password', 'pin_hash', 'remember_token',
    ];

    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}