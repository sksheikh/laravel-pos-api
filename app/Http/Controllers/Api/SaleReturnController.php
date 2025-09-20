<?php

namespace App\Http\Controllers\Api;

use App\Models\SaleReturn;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleReturnResource;
use App\Http\Requests\Sale\CreateSaleReturnRequest;

class SaleReturnController extends Controller
{
    public function __construct(
        private CreateSaleReturnUseCase $createSaleReturnUseCase,
        private ApproveSaleReturnUseCase $approveSaleReturnUseCase
    ) {}

    public function store(CreateSaleReturnRequest $request)
    {
        $saleReturn = $this->createSaleReturnUseCase->execute(
            SaleReturnData::fromRequest($request)
        );

        return new SaleReturnResource($saleReturn);
    }

    public function approve(SaleReturn $saleReturn)
    {
        $this->authorize('approve', $saleReturn);

        $approvedReturn = $this->approveSaleReturnUseCase->execute(
            $saleReturn,
            auth()->id()
        );

        return new SaleReturnResource($approvedReturn);
    }

    public function reject(SaleReturn $saleReturn, RejectSaleReturnRequest $request)
    {
        $this->authorize('approve', $saleReturn);

        $saleReturn->update([
            'status' => SaleReturn::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason
        ]);

        return new SaleReturnResource($saleReturn);
    }
}
