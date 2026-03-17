<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'unit_value',
        'sku',
        'selling_price',
        'limit_price',
        'alert_quantity',
        'quantity',
        'image',
    ];

    protected $with = ['unit'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventoryUnits()
    {
        return $this->hasMany(InventoryUnit::class);
    }
}
