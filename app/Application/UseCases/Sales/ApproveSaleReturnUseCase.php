<?php

namespace App\Application\UseCases\Sales;

use App\Models\SaleReturn;
use App\Domain\Inventory\Services\StockService;

class ApproveSaleReturnUseCase
{
    public function __construct(
        private SaleReturnRepositoryInterface $saleReturnRepository,
        private StockService $stockService
    ) {}

    public function execute(SaleReturn $saleReturn, int $approverId): SaleReturn
    {
        if ($saleReturn->status !== SaleReturn::STATUS_PENDING) {
            throw new InvalidStatusException('Return is not in pending status');
        }

        // Update return status
        $saleReturn->update([
            'status' => SaleReturn::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now()
        ]);

        // Restore stock for each returned item
        foreach ($saleReturn->items as $item) {
            $this->stockService->addStockToWarehouse(
                $item->product_id,
                $item->quantity,
                $saleReturn->sale->warehouse_id
            );
        }

        // Create stock movement records
        foreach ($saleReturn->items as $item) {
            StockMovement::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $saleReturn->sale->warehouse_id,
                'type' => 'return',
                'quantity' => $item->quantity,
                'reference_type' => 'sale_return',
                'reference_id' => $saleReturn->id,
                'user_id' => $approverId
            ]);
        }

        return $saleReturn->fresh(['items.product']);
    }
}
