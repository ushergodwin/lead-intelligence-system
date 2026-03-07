<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'leadintel@razertechnology.com'],
            [
                'name'              => 'Admin',
                'password'          => Hash::make('Ym5DNDA&FmL#'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['super_admin']);

        $this->command->info('Admin user created: leadintel@razertechnology.com / Ym5DNDA&FmL#');
    }
}
