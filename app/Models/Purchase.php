<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'purchase_date',
        'currency',
        'sub_total',
        'discount_type',
        'discount_value',
        'discount_amount',
        'net_total',
        'paid_amount',
        'payments_data',
        'payment_method',
        'payment_reference',
        'payment_account',
        'payment_note',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'payments_data' => 'array',
        'sub_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
