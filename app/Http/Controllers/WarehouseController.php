<?php

namespace App\Http\Controllers;

use App\Http\Requests\Warehouse\CreateWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

use function App\Helpers\errorResponse;
use function App\Helpers\successResponse;

class WarehouseController extends Controller
{
    public function __construct(private WarehouseService $warehouseService)
    {

    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       return $this->warehouseService->listWarehouses($request->all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateWarehouseRequest $request)
    {
        try {
            $warehouse = $this->warehouseService->createWarehouse($request->validated());
            return successResponse('Warehouse created successfully', $warehouse, 201);
        } catch (\Exception $e) {
            return errorResponse('Failed to create warehouse', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        return successResponse('Warehouse retrieved successfully', new WarehouseResource($warehouse));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, string $id)
    {
        try {
            $warehouse = $this->warehouseService->updateWarehouse($id, $request->validated());
            return successResponse('Warehouse updated successfully', $warehouse->fresh());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return errorResponse('Warehouse not found', 404);
        } catch (\Exception $e) {
            return errorResponse('Failed to update warehouse', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->warehouseService->deleteWarehouse($id);
    }
}
