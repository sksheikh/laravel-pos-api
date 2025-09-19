<?php

namespace App\Domain\Sales\ValueObjects;

use App\Models\Sale;

class SaleData
{
    public function __construct(
        public readonly ?int $customerId,
        public readonly int $warehouseId,
        public readonly array $items,
        public readonly array $payments,
        public readonly float $discountAmount = 0,
        public readonly float $taxAmount = 0,
        public readonly ?string $notes = null
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            $request->customer_id,
            $request->warehouse_id,
            $request->items,
            $request->payments,
            $request->discount_amount ?? 0,
            $request->tax_amount ?? 0,
            $request->notes
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'warehouse_id' => $this->warehouseId,
            'items' => $this->items,
            'payments' => $this->payments,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'notes' => $this->notes,
            'total_amount' => $this->calculateTotal(),
            'sale_number' => $this->generateSaleNumber(),
            'status' => 'completed',
            'user_id' => auth()->id(),
        ];
    }

    private function calculateTotal(): float
    {
        $subtotal = array_sum(array_map(
            fn($item) => $item['quantity'] * $item['unit_price'],
            $this->items
        ));

        return $subtotal - $this->discountAmount + $this->taxAmount;
    }

    private function generateSaleNumber(): string
    {
        return 'SALE-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 6, '0', STR_PAD_LEFT);
    }
}
