<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use App\Domain\Sales\Models\SaleReturn;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleReturnsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $approver;
    private Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->approver = User::factory()->create();
        $this->sale = Sale::factory()->create();

        // Create permissions and roles
        Permission::create(['name' => 'approve_sale_returns']);
        $approverRole = Role::create(['name' => 'manager']);
        $approverRole->givePermissionTo('approve_sale_returns');
        $this->approver->assignRole('manager');

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_sale_return_with_pending_status()
    {
        // Arrange
        $returnData = [
            'sale_id' => $this->sale->id,
            'reason' => 'Defective product',
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                    'unit_price' => 100
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/sale-returns', $returnData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('sale_returns', [
            'sale_id' => $this->sale->id,
            'status' => 'pending',
            'reason' => 'Defective product'
        ]);
    }

    /** @test */
    public function it_requires_approval_permission_to_approve_return()
    {
        // Arrange
        $saleReturn = SaleReturn::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->patchJson("/api/sale-returns/{$saleReturn->id}/approve");

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_approve_sale_return()
    {
        // Arrange
        $saleReturn = SaleReturn::factory()->create(['status' => 'pending']);
        Sanctum::actingAs($this->approver);

        // Act
        $response = $this->patchJson("/api/sale-returns/{$saleReturn->id}/approve");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('sale_returns', [
            'id' => $saleReturn->id,
            'status' => 'approved',
            'approved_by' => $this->approver->id
        ]);
    }

    /** @test */
    public function approved_return_restores_stock()
    {
        // Arrange
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10
        ]);

        $saleReturn = SaleReturn::factory()->create(['status' => 'pending']);
        SaleReturnItem::factory()->create([
            'sale_return_id' => $saleReturn->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        Sanctum::actingAs($this->approver);

        // Act
        $response = $this->patchJson("/api/sale-returns/{$saleReturn->id}/approve");

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(12, $stock->fresh()->quantity);
    }
}
