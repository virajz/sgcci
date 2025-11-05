<?php

namespace Database\Seeders;

use App\Models\StaffMember;
use Illuminate\Database\Seeder;

class StaffMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffMembers = [
            [
                'name' => 'Staff Member 1',
                'phone_code' => '+91',
                'phone_number' => '7211173115',
                'is_active' => true,
            ],
            [
                'name' => 'Staff Member 2',
                'phone_code' => '+91',
                'phone_number' => '7211173103',
                'is_active' => true,
            ],
            [
                'name' => 'Staff Member 3',
                'phone_code' => '+91',
                'phone_number' => '9023677452',
                'is_active' => true,
            ],
        ];

        foreach ($staffMembers as $staff) {
            StaffMember::updateOrCreate(
                ['phone_number' => $staff['phone_number']],
                $staff
            );
        }
    }
}
