<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Delete all existing users first
        User::query()->delete();

        // Create new users with strong random passwords for testing
        User::factory()->create([
            'name' => 'Chitrang',
            'email' => 'chitrang@sgcci.in',
            'role' => 'admin',
            'password' => bcrypt('xK9#mP2$vL8@nQ5&wR7!'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::factory()->create([
            'name' => 'Viraj',
            'email' => 'viraj@sgcci.in',
            'role' => 'super_admin',
            'password' => bcrypt('tD4%jF6^hB3*pW9&cY2!'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::factory()->create([
            'name' => 'Mehul',
            'email' => 'mehul@sgcci.in',
            'role' => 'super_admin',
            'password' => bcrypt('zX7!qA5#rN8@bM3$gK6%'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        User::factory()->create([
            'name' => 'Bhavana',
            'email' => 'bhavana@sgcci.in',
            'role' => 'admin',
            'password' => bcrypt('sE2^vT9&yH4*uL7@wP5!'),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->call(ExhibitionSeeder::class);
    }
}
