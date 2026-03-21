<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'barcode_data', // Keep for now, though likely moved to variant logic later
        'category_id',
        'sub_category_id',
        'description',
        'image',
        'warranty_period',
        'warranty_period_type',
    ];

    protected $appends = ['total_quantity', 'price_display', 'limit_price_display'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->variants->sum('quantity');
    }

    public function getPriceDisplayAttribute()
    {
        if ($this->variants->isEmpty()) {
            return 'N/A';
        }

        $min = $this->variants->min('selling_price');
        $max = $this->variants->max('selling_price');

        if ($min == $max) {
            return number_format($min, 2);
        }

        return number_format($min, 2) . ' - ' . number_format($max, 2);
    }

    public function getLimitPriceDisplayAttribute()
    {
        if ($this->variants->isEmpty()) {
            return 'N/A';
        }

        $limits = $this->variants
            ->pluck('limit_price')
            ->filter(fn ($value) => $value !== null);

        if ($limits->isEmpty()) {
            return 'N/A';
        }

        $min = $limits->min();
        $max = $limits->max();

        if ($min == $max) {
            return number_format($min, 2);
        }

        return number_format($min, 2) . ' - ' . number_format($max, 2);
    }

    public function getStockStatusAttribute()
    {
        if ($this->variants->isEmpty()) return 'Out of Stock';

        $totalQty = $this->total_quantity;
        if ($totalQty == 0) return 'Out of Stock';

        // Check if any variant is low on stock
        $hasLowStock = $this->variants->contains(function ($variant) {
            return $variant->quantity <= $variant->alert_quantity;
        });

        if ($hasLowStock) return 'Low Stock';

        return 'In Stock'; 
    }
}
