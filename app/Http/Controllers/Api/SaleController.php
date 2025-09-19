<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Domain\Sales\ValueObjects\SaleData;
use App\Http\Requests\Sale\CreateSaleRequest;
use App\Application\UseCases\Sales\CreateSaleUseCase;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Services\SaleService;

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
}
