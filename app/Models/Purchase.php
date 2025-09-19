<?php

namespace App\Models;

use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number', 'supplier_id', 'warehouse_id', 'total_amount',
        'tax_amount', 'discount_amount', 'status', 'user_id', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];
      const STATUS_PENDING = 'pending';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
