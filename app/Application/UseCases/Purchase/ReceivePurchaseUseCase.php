<?php

namespace App\Application\UseCases\Purchase;

use App\Models\Purchase;
use App\Domain\Inventory\Services\StockService;

class ReceivePurchaseUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository,
        private StockService $stockService
    ) {}

    public function execute(Purchase $purchase): Purchase
    {
        if ($purchase->status !== Purchase::STATUS_PENDING) {
            throw new InvalidStatusException('Purchase is not in pending status');
        }

        // Update purchase status
        $purchase->update(['status' => Purchase::STATUS_RECEIVED]);

        // Add stock for each purchase item
        foreach ($purchase->items as $item) {
            $this->stockService->addStockToWarehouse(
                $item->product_id,
                $item->quantity,
                $purchase->warehouse_id
            );
        }

        // Create stock movement records
        foreach ($purchase->items as $item) {
            StockMovement::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $purchase->warehouse_id,
                'type' => 'purchase',
                'quantity' => $item->quantity,
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'user_id' => auth()->id()
            ]);
        }

        return $purchase->fresh(['items.product']);
    }
}
