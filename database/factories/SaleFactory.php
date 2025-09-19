<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'sale_number' => 'SALE-' . date('Ymd') . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'total_amount' => $this->faker->randomFloat(2, 10, 10000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 100),
            'discount_amount' => $this->faker->randomFloat(2, 0, 100),
            'status' => 'completed',
            'user_id' => User::factory(),
            'sale_date' => now(),
        ];
    }
}
