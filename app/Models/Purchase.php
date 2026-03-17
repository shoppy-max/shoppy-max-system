<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    public const STATUSES = [
        'pending',
        'checking',
        'verified',
        'complete',
    ];

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'purchase_date',
        'status',
        'created_by',
        'checked_by',
        'checked_at',
        'verified_by',
        'verified_at',
        'completed_by',
        'completed_at',
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
        'checked_at' => 'datetime',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
        'payments_data' => 'array',
        'sub_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getPaymentStatusAttribute(): string
    {
        $netTotal = (float) ($this->net_total ?? 0);
        $paidAmount = (float) ($this->paid_amount ?? 0);

        if ($netTotal <= 0 || $paidAmount >= $netTotal) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'due';
    }
}
