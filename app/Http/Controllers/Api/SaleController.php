<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Http\Requests\CreateSaleRequest;
use App\Domain\Sales\ValueObjects\SaleData;
use App\Application\UseCases\Sales\CreateSaleUseCase;
use App\Domain\Inventory\Exceptions\InsufficientStockException;

class SaleController extends Controller
{
    public function __construct(private CreateSaleUseCase $createSaleUseCase) {}

    public function store(CreateSaleRequest $request)
    {
        try {
            $saleData = SaleData::fromRequest($request);
            $sale = $this->createSaleUseCase->execute($saleData);

            return new SaleResource($sale);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
