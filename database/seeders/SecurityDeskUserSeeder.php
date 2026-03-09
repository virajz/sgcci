<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecurityDeskUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default security desk user.
        // Change the password after seeding via the settings page.
        User::firstOrCreate(
            ['email' => 'security@sgcci.in'],
            [
                'name' => 'Security Desk',
                'email' => 'security@sgcci.in',
                'password' => Hash::make('Security@2025!'),
                'role' => 'security_desk',
            ]
        );

        $this->command->info('Security desk user created: security@sgcci.in / Security@2025!');
        $this->command->warn('Please change the password after first login.');
    }
}
