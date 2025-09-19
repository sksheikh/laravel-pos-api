<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Warehouse',
            'address' => $this->faker->address(),
            'manager_id' => User::factory(),
            'is_active' => true,
        ];
    }
}
