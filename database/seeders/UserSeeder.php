<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@app.com',
                'password' => bcrypt('password'),
                'phone' => '1234567890',
                'is_active' => true,
                'roles' => ['super_admin']
            ],
            [
                'name' => 'Manager User',
                'email' => 'manageruser@app.com',
                'password' => bcrypt('password'),
                'phone' => '1234567891',
                'is_active' => true,
                'roles' => ['manager']
            ],
            [
                'name' => 'Cashier User',
                'email' => 'cashieruser@app.com',
                'password' => bcrypt('password'),
                'phone' => '1234567892',
                'is_active' => true,
                'roles' => ['cashier']
            ],
            [
                'name' => 'Stock Keeper',
                'email' => 'stockkeeperuser@app.com',
                'password' => bcrypt('password'),
                'phone' => '1234567893',
                'is_active' => true,
                'roles' => ['stock_keeper']
            ]
        ];

        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);
            $user = \App\Models\User::create($userData);
            $user->assignRole($roles);
        }
    }
}
