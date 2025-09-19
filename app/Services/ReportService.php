<?php

namespace App\Services;

class ReportService
{
    public function salesReport(array $filters = []): array
    {
        $query = Sale::query()
            ->with(['items.product', 'customer', 'warehouse'])
            ->when($filters['start_date'] ?? null, fn($q) =>
                $q->whereDate('sale_date', '>=', $filters['start_date'])
            )
            ->when($filters['end_date'] ?? null, fn($q) =>
                $q->whereDate('sale_date', '<=', $filters['end_date'])
            )
            ->when($filters['warehouse_id'] ?? null, fn($q) =>
                $q->where('warehouse_id', $filters['warehouse_id'])
            );

        $sales = $query->get();

        return [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'average_sale' => $sales->avg('total_amount'),
            'sales' => $sales,
            'top_products' => $this->getTopSellingProducts($filters),
            'sales_by_day' => $this->getSalesByDay($filters)
        ];
    }

    public function inventoryReport(array $filters = []): array
    {
        $stocks = Stock::with(['product', 'warehouse'])
            ->when($filters['warehouse_id'] ?? null, fn($q) =>
                $q->where('warehouse_id', $filters['warehouse_id'])
            )
            ->when($filters['low_stock'] ?? false, fn($q) =>
                $q->where('quantity', '<=', 10)
            )
            ->get();

        return [
            'total_products' => $stocks->count(),
            'total_value' => $stocks->sum(fn($stock) =>
                $stock->quantity * $stock->product->cost
            ),
            'low_stock_items' => $stocks->where('quantity', '<=', 10)->count(),
            'stocks' => $stocks
        ];
    }

    private function getTopSellingProducts(array $filters): \Illuminate\Support\Collection
    {
        return SaleItem::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity) as total_sold')
            ->whereHas('sale', function($query) use ($filters) {
                $query->when($filters['start_date'] ?? null, fn($q) =>
                    $q->whereDate('sale_date', '>=', $filters['start_date'])
                )
                ->when($filters['end_date'] ?? null, fn($q) =>
                    $q->whereDate('sale_date', '<=', $filters['end_date'])
                );
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }
}
