<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define granular permissions for Asset Management
        $permissions = [
            // Asset Registry
            'view assets',
            'create assets',
            'edit assets',
            'delete assets',

            // Asset Modifications
            'view modifications',
            'create modifications',

            // Asset Sales
            'view sales',
            'create sales',

            // Asset Disposals
            'view disposals',
            'create disposals',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles and assign permissions
        
        // Admin Role (has full access)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // Staff Role (operational access)
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $staffRole->syncPermissions([
            'view assets',
            'create assets',
            'view modifications',
            'create modifications',
            'view sales',
            'create sales',
            'view disposals',
            'create disposals',
        ]);

        // 3. Create default test users if they don't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@assetmgmt.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['Admin']);

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@assetmgmt.com'],
            [
                'name' => 'Staff Member',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $staffUser->syncRoles(['Staff']);
    }
}

