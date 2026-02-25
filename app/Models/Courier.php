<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'rates',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rates' => 'array',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function resellers()
    {
        return $this->belongsToMany(Reseller::class)->withTimestamps();
    }
}
