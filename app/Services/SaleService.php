<?php

namespace App\Services;

use App\Models\Sale;
use App\DTOs\SaleData;
use Illuminate\Support\Facades\DB;
use App\Services\Inventory\StockService;

class SaleService
{
    public function __construct(protected StockService $stockService) {}
    // Sale related business logic can be added here
    public function createSale(SaleData $saleData): Sale
    {
        return DB::transaction(function () use ($saleData) {
            // Validate stock availability
            foreach ($saleData->items as $item) {
                $this->stockService->validateStockAvailability(
                    $item['product_id'],
                    $item['quantity'],
                    $saleData->warehouseId
                );
            }

            // Reserve stock
            foreach ($saleData->items as $item) {
                $this->stockService->reserveStock(
                    $item['product_id'],
                    $item['quantity'],
                    $saleData->warehouseId
                );
            }

            // Create sale
            $sale = Sale::create($saleData->toArray());
            $sale->items()->createMany($saleData->items);
            $sale->payments()->createMany($saleData->payments);

    
            // Reduce actual stock after successful sale
            foreach ($saleData->items as $item) {
                $this->stockService->reduceStock(
                    $item['product_id'],
                    $item['quantity'],
                    $saleData->warehouseId
                );
            }

            return $sale;
        });
    }
}
