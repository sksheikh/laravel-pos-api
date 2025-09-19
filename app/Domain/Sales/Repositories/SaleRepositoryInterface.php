<?php

namespace App\Domain\Sales\Repositories;

use App\Models\Sale;

interface SaleRepositoryInterface
{
    public function create(array $data): Sale;
    public function findById(int $id): ?Sale;
    public function update(Sale $sale, array $data): Sale;
    public function delete(Sale $sale): bool;
}
