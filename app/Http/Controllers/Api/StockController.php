<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Domain\Inventory\Services\StockService;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $stocks = Stock::with(['product', 'warehouse'])
                      ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
                      ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                      ->when($request->low_stock, fn($q) => $q->where('quantity', '<=', 10))
                      ->paginate();

        return StockResource::collection($stocks);
    }

    public function adjust(StockAdjustmentRequest $request)
    {
        $this->stockService->adjustStock(
            $request->product_id,
            $request->warehouse_id,
            $request->adjustment,
            $request->reason
        );

        return response()->json(['message' => 'Stock adjusted successfully']);
    }
}
