<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
     protected $fillable = [
        'sale_id', 'return_number', 'reason', 'total_amount',
        'status', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
