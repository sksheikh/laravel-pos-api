<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
         // Clear tables (delete instead of truncate, because of FK constraints)
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        Role::query()->delete();
        Permission::query()->delete();

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
            Permission::create([
                'name' => $permission,
                'guard_name' => 'sanctum', // যেহেতু আপনি sanctum ব্যবহার করছেন
            ]);
        }

        // Create Roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->givePermissionTo(Permission::all());

        $manager = Role::create(['name' => 'manager', 'guard_name' => 'sanctum']);
        $manager->givePermissionTo([
            'view_sales', 'create_sales', 'update_sales',
            'approve_sale_returns', 'approve_purchase_returns',
            'create_purchases', 'receive_purchases',
            'create_warehouse_transfers', 'complete_warehouse_transfers',
            'adjust_stock', 'view_stock_reports'
        ]);

        $cashier = Role::create(['name' => 'cashier', 'guard_name' => 'sanctum']);
        $cashier->givePermissionTo([
            'create_sales', 'view_sales',
            'create_sale_returns'
        ]);

        $stockKeeper = Role::create(['name' => 'stock_keeper', 'guard_name' => 'sanctum']);
        $stockKeeper->givePermissionTo([
            'create_purchases', 'receive_purchases',
            'create_warehouse_transfers',
            'adjust_stock', 'view_stock_reports'
        ]);
    }
}
