<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'total_amount' => $this->total_amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'status' => $this->status,
            'sale_date' => $this->sale_date->format('Y-m-d H:i:s'),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'returns' => SaleReturnResource::collection($this->whenLoaded('returns')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
