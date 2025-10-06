<?php

namespace Database\Seeders;

use App\Models\Exhibition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExhibitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Exhibition::factory()->create([
            'title' => 'Auto Expo',
            'description' => 'An exhibition showcasing the latest in automotive technology and design.',
            'start_date' => '2026-03-13',
            'end_date' => '2026-03-16',
        ]);
    }
}
