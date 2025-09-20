<?php
namespace App\Services\Inventory;

use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Exceptions\InsufficientStockException;

class StockService
{
    public function validateStockAvailability(int $productId, int $quantity, int $warehouseId): void
    {
        $stock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

        if (!$stock || $stock->available_quantity < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for product ID: {$productId}"
            );
        }
    }

    public function reserveStock(int $productId, int $quantity, int $warehouseId): void
    {
        $stock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

        $stock->increment('reserved_quantity', $quantity);
    }

    public function reduceStock(int $productId, int $quantity, int $warehouseId): void
    {
        $stock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

        $stock->decrement('quantity', $quantity);
        $stock->decrement('reserved_quantity', $quantity);
    }

    public function adjustStock(int $productId, int $warehouseId, int $adjustment, string $reason): void
    {
        $stock = Stock::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        $stock->increment('quantity', $adjustment);

        // Log stock adjustment
        StockAdjustment::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'adjustment' => $adjustment,
            'reason' => $reason,
            'user_id' => auth()->id()
        ]);
    }
}
