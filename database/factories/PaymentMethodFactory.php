<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer']),
            'code' => $this->faker->unique()->regexify('[A-Z]{4}'),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
