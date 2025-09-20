<?php

namespace App\Services;

use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Support\Arr;
use PHPUnit\Metadata\Metadata;

use function App\Helpers\metaPagination;
use function App\Helpers\successResponse;

class WarehouseService
{
    public function listWarehouses(array $filters = [])
    {
        $query = Warehouse::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('address', 'like', "%{$searchTerm}%");
            });
        }

        $warehouses = $query->paginate($filters['per_page'] ?? 15);

        $response = [
            'warehouses' => WarehouseResource::collection($warehouses),
            'meta' => metaPagination($warehouses)
        ];
        return successResponse('Warehouses retrieved successfully', $response);
    }

    /**
     * Create a new class instance.
     */
    public function createWarehouse(array $data): Warehouse
    {
        $warehouse = Warehouse::create($data);
        return $warehouse;
    }

    public function updateWarehouse(string $id, array $data): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($data);
        return $warehouse;
    }
    public function deleteWarehouse(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return successResponse('Warehouse deleted successfully', null, 204);
    }
}
