<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $paymentMethods = [
            ['name' => 'Cash', 'code' => 'CASH', 'description' => 'Cash payment'],
            ['name' => 'Credit Card', 'code' => 'CC', 'description' => 'Credit card payment'],
            ['name' => 'Debit Card', 'code' => 'DC', 'description' => 'Debit card payment'],
            ['name' => 'Bank Transfer', 'code' => 'BT', 'description' => 'Bank transfer'],
            ['name' => 'Mobile Payment', 'code' => 'MP', 'description' => 'Mobile payment (bKash, Nagad, etc.)'],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }
    }
}
