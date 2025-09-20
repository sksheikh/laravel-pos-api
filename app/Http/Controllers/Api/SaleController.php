<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\DTOs\SaleData;
use App\Services\SaleService;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Http\Requests\Sale\CreateSaleRequest;
use App\Exceptions\InsufficientStockException;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    public function store(CreateSaleRequest $request)
    {
        try {
            $saleData = SaleData::fromRequest($request);
            $sale = $this->saleService->createSale($saleData);
            return new SaleResource($sale);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Sale $sale)
    {
        return new SaleResource($sale->load(['items', 'payments', 'returns']));
    }
}
