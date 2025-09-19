<?php

namespace App\Infrastructure\Repositories;

use App\Models\Sale;
use App\Domain\Sales\Repositories\SaleRepositoryInterface;

class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function create(array $data): Sale
    {
        return Sale::create($data);
    }

    public function findById(int $id): ?Sale
    {
        return Sale::with(['items.product', 'payments', 'returns'])->find($id);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $sale->update($data);
        return $sale->fresh();
    }

    public function delete(Sale $sale): bool
    {
        return $sale->delete();
    }
}
