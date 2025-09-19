<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseController extends Controller
{
    public function __construct(
        private CreatePurchaseUseCase $createPurchaseUseCase,
        private ReceivePurchaseUseCase $receivePurchaseUseCase
    ) {}

    public function store(CreatePurchaseRequest $request)
    {
        $purchase = $this->createPurchaseUseCase->execute(
            PurchaseData::fromRequest($request)
        );

        return new PurchaseResource($purchase);
    }

    public function receive(Purchase $purchase)
    {
        $this->authorize('receive', $purchase);

        $receivedPurchase = $this->receivePurchaseUseCase->execute($purchase);

        return new PurchaseResource($receivedPurchase);
    }
}
