<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use Laravel\Sanctum\Sanctum;
use App\Models\PaymentMethod;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private Product $product;
    private Warehouse $warehouse;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create(['price' => 100]);
        $this->warehouse = Warehouse::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();

        Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_sale_with_valid_data()
    {
        // Arrange
        $saleData = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 100
                ]
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 200
                ]
            ],
            'discount_amount' => 0,
            'tax_amount' => 0
        ];

        // Act
        $response = $this->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('sales', [
            'warehouse_id' => $this->warehouse->id,
            'total_amount' => 200,
            'status' => 'completed'
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200
        ]);

        // Verify stock was reduced
        $stock = Stock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(48, $stock->quantity);
    }

    /** @test */
    public function it_fails_to_create_sale_with_insufficient_stock()
    {
        // Arrange
        $saleData = [
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 100, // More than available stock (50)
                    'unit_price' => 100
                ]
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->paymentMethod->id,
                    'amount' => 10000
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/sales', []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['warehouse_id', 'items', 'payments']);
    }
}
