<?php

// Integration Test: Complete Sale Flow
namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\SaleReturn;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_sale_flow_with_multiple_products_and_payments()
    {
        // Arrange
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create();

        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 50]);

        Stock::factory()->create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10
        ]);

        Stock::factory()->create([
            'product_id' => $product2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20
        ]);

        $cashPayment = PaymentMethod::factory()->create(['name' => 'Cash', 'code' => 'CASH']);
        $cardPayment = PaymentMethod::factory()->create(['name' => 'Card', 'code' => 'CARD']);

        Sanctum::actingAs($user);

        // Act - Create Sale
        $saleData = [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'unit_price' => 100
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'unit_price' => 50
                ]
            ],
            'payments' => [
                [
                    'payment_method_id' => $cashPayment->id,
                    'amount' => 200
                ],
                [
                    'payment_method_id' => $cardPayment->id,
                    'amount' => 150
                ]
            ],
            'discount_amount' => 0,
            'tax_amount' => 0
        ];

        $response = $this->postJson('/api/sales', $saleData);

        // Assert Sale Creation
        $response->assertStatus(201);
        $sale = Sale::latest()->first();

        $this->assertEquals(350, $sale->total_amount);
        $this->assertEquals(2, $sale->items->count());
        $this->assertEquals(2, $sale->payments->count());

        // Assert Stock Reduction
        $product1Stock = Stock::where('product_id', $product1->id)
                             ->where('warehouse_id', $warehouse->id)
                             ->first();
        $this->assertEquals(8, $product1Stock->quantity);

        $product2Stock = Stock::where('product_id', $product2->id)
                             ->where('warehouse_id', $warehouse->id)
                             ->first();
        $this->assertEquals(17, $product2Stock->quantity);

        // Act - Create Partial Return
        $returnData = [
            'sale_id' => $sale->id,
            'reason' => 'Customer changed mind',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 1,
                    'unit_price' => 100
                ]
            ]
        ];

        $returnResponse = $this->postJson('/api/sale-returns', $returnData);
        $returnResponse->assertStatus(201);

        // Act - Approve Return (as manager)
        $manager = User::factory()->create();
        Permission::create(['name' => 'approve_sale_returns']);
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo('approve_sale_returns');
        $manager->assignRole('manager');

        Sanctum::actingAs($manager);

        $saleReturn = SaleReturn::latest()->first();
        $approveResponse = $this->patchJson("/api/sale-returns/{$saleReturn->id}/approve");

        // Assert Return Approval and Stock Restoration
        $approveResponse->assertStatus(200);
        $this->assertEquals('approved', $saleReturn->fresh()->status);
        $this->assertEquals(9, $product1Stock->fresh()->quantity); // Stock restored
    }
}
