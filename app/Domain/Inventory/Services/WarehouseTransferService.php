<?php
namespace App\Domain\Inventory\Services;

use App\Models\Stock;

class WarehouseTransferService
{
    public function __construct(private StockService $stockService) {}

    public function createTransfer(array $data): WarehouseTransfer
    {
        // Validate source warehouse has sufficient stock
        $this->stockService->validateStockAvailability(
            $data['product_id'],
            $data['quantity'],
            $data['from_warehouse_id']
        );

        $transfer = WarehouseTransfer::create($data);

        // Reduce stock from source warehouse
        $this->stockService->reduceStock(
            $data['product_id'],
            $data['quantity'],
            $data['from_warehouse_id']
        );

        // Add stock to destination warehouse
        $this->addStockToWarehouse(
            $data['product_id'],
            $data['quantity'],
            $data['to_warehouse_id']
        );

        return $transfer;
    }

    private function addStockToWarehouse(int $productId, int $quantity, int $warehouseId): void
    {
        $stock = Stock::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );

        $stock->increment('quantity', $quantity);
    }
}
