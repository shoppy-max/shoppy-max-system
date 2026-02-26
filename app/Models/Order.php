<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_date',
        'order_type',
        'customer_id', // Linked customer
        'customer_name', // Snapshot (Legacy/Backup)
        'customer_phone',
        'customer_address',
        'city_id',
        'status',
        'delivery_status',
        'reseller_return_fee_applied',
        'return_fee_reseller_id',
        'payment_method',
        'payment_status',
        'total_amount',
        'paid_amount',
        'payments_data',
        'discount_amount',
        'total_cost',       // New
        'total_commission', // New
        'sales_note',
        'waybill_number',
        'courier_id',
        'courier_cost', // Legacy or used interchangeably
        'courier_charge', // New
        'call_status',
        'customer_city', 'customer_district', 'customer_province',
        'delivery_fee',
        'courier_payment_id',
        'packed_by',
        'dispatched_at',
        'cancelled_at',
        'delivered_at',
        'returned_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payments_data' => 'array',
        'discount_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'courier_cost' => 'decimal:2',
        'courier_charge' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'reseller_return_fee_applied' => 'decimal:2',
        'dispatched_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function packer()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }
    
    public function courierPayment()
    {
        return $this->belongsTo(CourierPayment::class);
    }
}
