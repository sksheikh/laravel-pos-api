<?php
// Application Layer - Use Cases
namespace App\Application\UseCases\Sales;

use App\Models\Sale;
use App\Domain\Sales\ValueObjects\SaleData;
use App\Domain\Inventory\Services\StockService;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;

class CreateSaleUseCase
{
    public function __construct(
        private SaleRepositoryInterface $saleRepository,
        private StockService $stockService
    ) {}

    public function execute(SaleData $saleData): Sale
    {
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
        $sale = $this->saleRepository->create($saleData->toArray());
        // dd($sale->toArray());
        // Reduce actual stock after successful sale
        foreach ($saleData->items as $item) {
            $this->stockService->reduceStock(
                $item['product_id'],
                $item['quantity'],
                $saleData->warehouseId
            );
        }

        return $sale;
    }
}
