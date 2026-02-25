<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    public const TYPE_RESELLER = 'reseller';
    public const TYPE_DIRECT_RESELLER = 'direct_reseller';

    protected $fillable = [
        'business_name',
        'name',
        'email',
        'mobile',
        'landline',
        'address',
        'city',
        'district',
        'province',
        'country',
        'due_amount',
        'return_fee',
        'reseller_type',
    ];

    public function scopeRegular($query)
    {
        return $query->where('reseller_type', self::TYPE_RESELLER);
    }

    public function scopeDirect($query)
    {
        return $query->where('reseller_type', self::TYPE_DIRECT_RESELLER);
    }

    public function targets()
    {
        return $this->hasMany(ResellerTarget::class);
    }

    public function payments()
    {
        return $this->hasMany(ResellerPayment::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function couriers()
    {
        return $this->belongsToMany(Courier::class)->withTimestamps();
    }
}
