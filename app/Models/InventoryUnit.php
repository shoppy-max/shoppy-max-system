<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryUnit extends Model
{
    public const STATUS_PENDING_RECEIPT = 'pending_receipt';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_ARCHIVED = 'archived';

    public const ACTIVE_STOCK_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_ALLOCATED,
        self::STATUS_DELIVERED,
    ];

    protected $fillable = [
        'product_variant_id',
        'purchase_id',
        'purchase_item_id',
        'order_id',
        'order_item_id',
        'unit_code',
        'status',
        'sku_snapshot',
        'product_name_snapshot',
        'variant_label_snapshot',
        'available_at',
        'allocated_at',
        'delivered_at',
        'archived_at',
        'last_event_at',
    ];

    protected $casts = [
        'available_at' => 'datetime',
        'allocated_at' => 'datetime',
        'delivered_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InventoryUnitEvent::class);
    }
}
