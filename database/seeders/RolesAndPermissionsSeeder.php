<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'viewer']);

        // Assign super_admin to existing admin user
        $admin = User::where('email', 'leadintel@razertechnology.com')->first();
        if ($admin && ! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info('Roles created: super_admin, manager, viewer');
        if ($admin) {
            $this->command->info("Assigned super_admin to {$admin->email}");
        }
    }
}
