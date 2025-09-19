<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'type', 'quantity',
        'reference_type', 'reference_id', 'user_id', 'notes'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const TYPE_SALE = 'sale';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_RETURN = 'return';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_ADJUSTMENT = 'adjustment';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
