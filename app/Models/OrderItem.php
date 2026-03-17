<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id', // New
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'base_price', // New
        'cost_price',
        'total_price',
        'subtotal', // Use total_price or subtotal depending on schema, migration didn't add subtotal but existing had total_price
    ];
    


    protected $casts = [
        'unit_price' => 'decimal:2',
        'base_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function inventoryUnits()
    {
        return $this->hasMany(InventoryUnit::class);
    }
}
