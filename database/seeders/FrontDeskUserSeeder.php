<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FrontDeskUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default front desk user.
        // Change the password after seeding via the settings page.
        User::firstOrCreate(
            ['email' => 'frontdesk@sgcci.in'],
            [
                'name' => 'Front Desk',
                'email' => 'frontdesk@sgcci.in',
                'password' => Hash::make('FrontDesk@2025!'),
                'role' => 'front_desk',
            ]
        );

        $this->command->info('Front desk user created: frontdesk@sgcci.in / FrontDesk@2025!');
        $this->command->warn('Please change the password after first login.');
    }
}
