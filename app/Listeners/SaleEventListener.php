<?php

namespace App\Listeners;

use App\Models\Stock;
use App\Events\SaleCreated;
use App\Events\StockLevelLow;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SaleEventListener implements ShouldQueue
{
    public function handleSaleCreated(SaleCreated $event): void
    {
        // Generate receipt
        GenerateReceiptJob::dispatch($event->sale);

        // Check for low stock levels
        foreach ($event->sale->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                         ->where('warehouse_id', $event->sale->warehouse_id)
                         ->first();

            if ($stock && $stock->quantity <= 10) {
                event(new StockLevelLow(
                    $item->product,
                    $event->sale->warehouse,
                    $stock->quantity
                ));
            }
        }
    }

    public function handleLowStock(StockLevelLow $event): void
    {
        SendLowStockNotificationJob::dispatch($event);
    }
}
