<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseTransferApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Warehouse $sourceWarehouse;
    private Warehouse $destinationWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create();
        $this->sourceWarehouse = Warehouse::factory()->create();
        $this->destinationWarehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->sourceWarehouse->id,
            'quantity' => 20
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_warehouse_transfer()
    {
        // Arrange
        $transferData = [
            'from_warehouse_id' => $this->sourceWarehouse->id,
            'to_warehouse_id' => $this->destinationWarehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5
                ]
            ],
            'notes' => 'Restocking destination warehouse'
        ];

        // Act
        $response = $this->postJson('/api/warehouse-transfers', $transferData);

        // Assert
        $response->assertStatus(201);

        // Check transfer record
        $this->assertDatabaseHas('warehouse_transfers', [
            'from_warehouse_id' => $this->sourceWarehouse->id,
            'to_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'completed'
        ]);

        // Check stock was moved
        $sourceStock = Stock::where('product_id', $this->product->id)
                           ->where('warehouse_id', $this->sourceWarehouse->id)
                           ->first();
        $this->assertEquals(15, $sourceStock->quantity);

        $destStock = Stock::where('product_id', $this->product->id)
                         ->where('warehouse_id', $this->destinationWarehouse->id)
                         ->first();
        $this->assertEquals(5, $destStock->quantity);
    }

    /** @test */
    public function it_prevents_transfer_with_insufficient_stock()
    {
        // Arrange
        $transferData = [
            'from_warehouse_id' => $this->sourceWarehouse->id,
            'to_warehouse_id' => $this->destinationWarehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 25 // More than available (20)
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/warehouse-transfers', $transferData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }
}

