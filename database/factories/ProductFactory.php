<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'cost' => $this->faker->randomFloat(2, 5, 500),
            'category_id' => Category::factory(),
            'is_active' => true,
        ];
    }
}
