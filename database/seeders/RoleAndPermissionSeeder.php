<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'create_sales',
            'view_sales',
            'update_sales',
            'delete_sales',

            'create_sale_returns',
            'approve_sale_returns',
            'reject_sale_returns',

            'create_purchases',
            'receive_purchases',
            'approve_purchase_returns',

            'create_warehouse_transfers',
            'complete_warehouse_transfers',

            'adjust_stock',
            'view_stock_reports',

            'manage_users',
            'manage_products',
            'manage_warehouses',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view_sales', 'create_sales', 'update_sales',
            'approve_sale_returns', 'approve_purchase_returns',
            'create_purchases', 'receive_purchases',
            'create_warehouse_transfers', 'complete_warehouse_transfers',
            'adjust_stock', 'view_stock_reports'
        ]);

        $cashier = Role::create(['name' => 'cashier']);
        $cashier->givePermissionTo([
            'create_sales', 'view_sales',
            'create_sale_returns'
        ]);

        $stockKeeper = Role::create(['name' => 'stock_keeper']);
        $stockKeeper->givePermissionTo([
            'create_purchases', 'receive_purchases',
            'create_warehouse_transfers',
            'adjust_stock', 'view_stock_reports'
        ]);
    }
}
