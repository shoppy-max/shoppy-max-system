<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_note',
        'reference_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
