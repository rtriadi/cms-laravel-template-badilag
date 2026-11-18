<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var list<string> $permissions */
        $permissions = config('satker.permissions', []);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /** @var array<string, list<string>> $rolePermissions */
        $rolePermissions = [
            'super_admin' => $permissions,
            'admin_konten' => [
                'manage-menus',
                'manage-pages',
                'manage-collections',
                'manage-kepaniteraan',
                'manage-kesekretariatan',
                'manage-ppid-humas',
                'manage-sinova-links',
                'view-compliance-dashboard',
            ],
            'operator_kepaniteraan' => [
                'manage-kepaniteraan',
                'manage-sinova-links',
                'view-compliance-dashboard',
            ],
            'operator_kesekretariatan' => [
                'manage-kesekretariatan',
                'manage-sinova-links',
                'view-compliance-dashboard',
            ],
            'operator_ppid_humas' => [
                'manage-ppid-humas',
                'manage-sinova-links',
                'view-compliance-dashboard',
            ],
            'penilai_internal' => [
                'view-compliance-dashboard',
            ],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($assignedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
