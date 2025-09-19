<?php
namespace App\Application\UseCases\Purchases;

use App\Models\Purchase;

class CreatePurchaseUseCase
{
    public function __construct(
        private PurchaseRepositoryInterface $purchaseRepository
    ) {}

    public function execute(PurchaseData $purchaseData): Purchase
    {
        return $this->purchaseRepository->create($purchaseData->toArray());
    }
}
