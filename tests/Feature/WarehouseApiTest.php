<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup if needed
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_warehouse()
    {
        // Arrange
        $warehouseData = [
            'name' => 'Main Warehouse',
            'address' => '123 Warehouse St, City, Country',
            'is_active' => true
        ];

        // Act
        $response = $this->postJson('/api/warehouses', $warehouseData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('warehouses', $warehouseData);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_a_warehouse()
    {
        // Arrange
        $warehouseData = [
            // 'name' => 'Main Warehouse',
            // 'address' => '123 Warehouse St, City, Country',
        ];

        // Act
        $response = $this->postJson('/api/warehouses', $warehouseData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'address']);
    }

    /** @test */
    public function it_can_retrieve_a_list_of_warehouses()
    {
        // Arrange
        $warehouses = \App\Models\Warehouse::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/warehouses');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.warehouses');
    }

    /** @test */
    public function it_can_filter_warehouses_by_active_status()
    {
        // Arrange
        \App\Models\Warehouse::factory()->create(['is_active' => true]);
        \App\Models\Warehouse::factory()->create(['is_active' => false]);

        // Act
        $response = $this->getJson('/api/warehouses?is_active=true');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.warehouses');
    }

    /** @test */
    public function it_can_search_warehouses_by_name_or_address()
    {
        // Arrange
        \App\Models\Warehouse::factory()->create(['name' => 'Central Warehouse', 'address' => '456 Central St']);
        \App\Models\Warehouse::factory()->create(['name' => 'East Warehouse', 'address' => '789 East St']);

        // Act
        $response = $this->getJson('/api/warehouses?search=warehouse');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.warehouses');
    }

    /** @test */
    public function it_can_paginate_warehouses()
    {
        // Arrange
        \App\Models\Warehouse::factory()->count(30)->create();

        // Act
        $response = $this->getJson('/api/warehouses?per_page=10');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data.warehouses');
    }

    /** @test */
    public function it_handles_server_errors_gracefully()
    {
        // Simulate a server error by mocking the WarehouseService to throw an exception
        $this->mock(\App\Services\WarehouseService::class, function ($mock) {
            $mock->shouldReceive('listWarehouses')
                 ->andThrow(new \Exception('Server error'));
        });
        // Act
        $response = $this->getJson('/api/warehouses');
        // Assert
        $response->assertStatus(500);
        // $response->assertJsonFragment(['message' => 'Failed to retrieve warehouses']);
    }

    /** @test */
    public function it_can_show_a_single_warehouse()
    {
        // Arrange
        $warehouse = \App\Models\Warehouse::factory()->create();

        // Act
        $response = $this->getJson("/api/warehouses/{$warehouse->id}");

        // Assert
        $response->assertStatus(200);
    }
    // Additional tests for update and delete can be added here
    /** @test */
    public function it_can_update_a_warehouse()
    {
        // Arrange
        $warehouse = \App\Models\Warehouse::factory()->create();
        $updateData = [
            'name' => 'Updated Warehouse Name',
            'address' => 'Updated Address 123',
            'is_active' => false
        ];

        // Act
        $response = $this->putJson("/api/warehouses/{$warehouse->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('warehouses', $updateData);
    }

    /** @test */
    public function it_validates_required_fields_when_updating_a_warehouse()
    {
        // Arrange
        $warehouse = \App\Models\Warehouse::factory()->create();
        $updateData = [
            'name' => '', // Invalid name
            'address' => '', // Invalid address
        ];

        // Act
        $response = $this->putJson("/api/warehouses/{$warehouse->id}", $updateData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'address']);
    }

    /** @test */
    public function it_returns_404_when_updating_non_existent_warehouse()
    {
        // Arrange
        $nonExistentId = 9999;
        $updateData = [
            'name' => 'Non-existent Warehouse',
            'address' => 'No Address',
        ];

        // Act
        $response = $this->putJson("/api/warehouses/{$nonExistentId}", $updateData);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_delete_a_warehouse()
    {
        // Arrange
        $warehouse = \App\Models\Warehouse::factory()->create();

        // Act
        $response = $this->deleteJson("/api/warehouses/{$warehouse->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_non_existent_warehouse()
    {
        // Arrange
        $nonExistentId = 9999;

        // Act
        $response = $this->deleteJson("/api/warehouses/{$nonExistentId}");

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_server_errors_gracefully_on_delete()
    {
        // Simulate a server error by mocking the WarehouseService to throw an exception
        $this->mock(\App\Services\WarehouseService::class, function ($mock) {
            $mock->shouldReceive('deleteWarehouse')
                 ->andThrow(new \Exception('Server error'));
        });
        // Act
        $response = $this->deleteJson('/api/warehouses/1');
        // Assert
        $response->assertStatus(500);
        // $response->assertJsonFragment(['message' => 'Failed to delete warehouse']);
    }

    //for check permission
    /** @test */
    public function it_checks_permissions_for_warehouse_endpoints()
    {
        // Arrange
        $this->user->revokePermissionTo('manage_warehouses');
        // Act
        $response = $this->getJson('/api/warehouses');
        // Assert
        $response->assertStatus(403);
    }

}
