<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleReturnResource extends JsonResource
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
            'return_number' => $this->return_number,
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'reason' => $this->reason,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'approved_by' => new UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'return_date' => $this->return_date->format('Y-m-d H:i:s'),
            'items' => SaleReturnItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
