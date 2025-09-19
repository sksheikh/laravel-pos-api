<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'sale_number', 'customer_id', 'total_amount', 'tax_amount',
        'discount_amount', 'status', 'user_id', 'notes'
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
}
