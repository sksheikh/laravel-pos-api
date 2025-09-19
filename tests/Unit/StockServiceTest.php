<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Inventory\Exceptions\InsufficientStockException;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    /** @test */
    public function it_validates_sufficient_stock_availability()
    {
        // Arrange
        Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 2
        ]);

        // Act & Assert
        $this->stockService->validateStockAvailability($this->product->id, 8, $this->warehouse->id);
        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    /** @test */
    public function it_throws_exception_for_insufficient_stock()
    {
        // Arrange
        Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 2
        ]);

        // Act & Assert
        $this->expectException(InsufficientStockException::class);
        $this->stockService->validateStockAvailability($this->product->id, 10, $this->warehouse->id);
    }

    /** @test */
    public function it_reserves_stock_correctly()
    {
        // Arrange
        $stock = Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 0
        ]);

        // Act
        $this->stockService->reserveStock($this->product->id, 5, $this->warehouse->id);

        // Assert
        $this->assertEquals(5, $stock->fresh()->reserved_quantity);
    }

    /** @test */
    public function it_reduces_stock_correctly()
    {
        // Arrange
        $stock = Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'reserved_quantity' => 5
        ]);

        // Act
        $this->stockService->reduceStock($this->product->id, 5, $this->warehouse->id);

        // Assert
        $stock = $stock->fresh();
        $this->assertEquals(5, $stock->quantity);
        $this->assertEquals(0, $stock->reserved_quantity);
    }

    /** @test */
    public function it_adjusts_stock_and_logs_adjustment()
    {
        // Arrange
        $this->actingAs(User::factory()->create());
        $stock = Stock::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10
        ]);

        // Act
        $this->stockService->adjustStock($this->product->id, $this->warehouse->id, 5, 'Damaged goods return');

        // Assert
        $this->assertEquals(15, $stock->fresh()->quantity);
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'adjustment' => 5,
            'reason' => 'Damaged goods return'
        ]);
    }
}
