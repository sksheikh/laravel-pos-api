<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Events\StockLevelLow;
use Illuminate\Console\Command;

class LowStockCheckCommand extends Command
{
   protected $signature = 'pos:check-low-stock {--threshold=10}';
    protected $description = 'Check for low stock items and send notifications';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');

        $lowStockItems = Stock::with(['product', 'warehouse'])
            ->where('quantity', '<=', $threshold)
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('No low stock items found.');
            return 0;
        }

        $this->warn("Found {$lowStockItems->count()} low stock items:");

        $tableData = $lowStockItems->map(function ($stock) {
            return [
                $stock->product->name,
                $stock->warehouse->name,
                $stock->quantity,
                $stock->product->sku
            ];
        })->toArray();

        $this->table(
            ['Product', 'Warehouse', 'Stock', 'SKU'],
            $tableData
        );

        // Send notifications
        foreach ($lowStockItems as $stock) {
            event(new StockLevelLow(
                $stock->product,
                $stock->warehouse,
                $stock->quantity,
                $threshold
            ));
        }

        $this->info('Low stock notifications sent.');
        return 0;
    }
}
