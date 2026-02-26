<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'holder_name',
        'type',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function courierPayments()
    {
        return $this->hasMany(CourierPayment::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        $parts = [$this->name];

        if ($this->bank_name) {
            $parts[] = $this->bank_name;
        }

        if ($this->account_number) {
            $parts[] = 'A/C ' . $this->account_number;
        }

        return implode(' | ', $parts);
    }
}
