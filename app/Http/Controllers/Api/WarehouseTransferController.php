<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WarehouseTransferController extends Controller
{
     public function __construct(
        private WarehouseTransferService $transferService
    ) {}

    public function store(CreateWarehouseTransferRequest $request)
    {
        try {
            $transfer = $this->transferService->createTransfer($request->validated());
            return new WarehouseTransferResource($transfer);
        } catch (InsufficientStockException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function complete(WarehouseTransfer $transfer)
    {
        $this->authorize('complete', $transfer);

        $transfer->update(['status' => 'completed']);

        return new WarehouseTransferResource($transfer);
    }
}
