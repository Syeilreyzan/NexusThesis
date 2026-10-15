<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $permissions = [
            'view theses',
            'submit thesis',
            'review thesis',
            'manage users',
            'download thesis',
            'delete theses',
            'submit theses',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $lecturer = Role::firstOrCreate([
            'name' => 'lecturer',
            'guard_name' => 'web',
        ]);

        $student = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($permissions);

        $lecturer->syncPermissions([
            'view theses',
            'review thesis',
            'download thesis',
        ]);

        $student->syncPermissions([
            'view theses',
            'submit thesis',
            'submit theses',
            'download thesis',
            'delete theses',
        ]);
    }
}
