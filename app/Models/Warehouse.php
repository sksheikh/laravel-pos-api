<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
     protected $fillable = ['name', 'address', 'manager_id'];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id');
    }
}
