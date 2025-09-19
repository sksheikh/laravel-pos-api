<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'sku', 'description', 'price', 'cost', 'category_id'
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
